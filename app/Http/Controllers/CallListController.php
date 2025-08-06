<?php

namespace App\Http\Controllers;

// ===== モデル =====
use App\Models\CallHistory;
use App\Models\CallList;
use App\Models\CallListStreak;
use App\Models\CallStatusMaster;
use App\Models\DoNotCallList;

// ===== ファサード =====
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

// ===== その他 =====
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class CallListController extends Controller
{
    /**
     * 架電リスト一覧を表示します。
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // 1. 基本クエリの作成 (Eager Loading を含む)
        $user = Auth::user();
        $query = CallList::query()->with(['latestCallStatus', 'user', 'phoneNumbers']);

        // 2. 権限に基づく基本的な絞り込み
        if ($user->role === 'eigyo') {
            $query->where('user_id', $user->id);
        } elseif ($user->role !== 'admin') {
            // admin と eigyo 以外は何も表示しない
            $allStatuses = CallStatusMaster::orderBy('sort_order')->get();
            return view('call_list.index', [
                'callLists' => collect([]), // 空のコレクション
                'allStatuses' => $allStatuses,
                // 他のフィルター/ソート変数にデフォルト値を設定
                'sortBy' => 'updated_at',
                'sortDirection' => 'desc',
                'filterCompanyName' => null,
                'filterPhoneNumber' => null,
                'filterStatusIds' => [],
                'startDate' => null,
                'endDate' => null,
            ]);
        }



        // 3. フィルター処理
        $priorityListType = $request->query('priority_list');

        // "本日の架電リスト" が選択されているか、通常のフィルターかを判断
        if ($priorityListType && in_array($priorityListType, ['am', 'pm'])) {
            // 【優先リスト表示の場合】
            // _getPriorityListIds ヘルパーメソッドで対象IDリストを取得
            $priorityListIds = $this->_getPriorityListIds($priorityListType);

            if (empty($priorityListIds)) {
                // 対象が0件の場合、結果が0件になるようにする
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn((new CallList)->getTable() . '.id', $priorityListIds); // テーブル名を明示
            }
        } else {
            // 【通常のフィルター表示の場合】
            // バリデーションは不要 (クエリスコープ側でnullチェックするため)
            $query->filterByCompanyName($request->query('filter_company_name'))
                ->filterByPhoneNumber($request->query('filter_phone_number'))
                ->filterByStatusIds($request->query('filter_status_ids'))
                ->filterByDateRange($request->query('start_date'), $request->query('end_date'));
        }

        // 4. 並び替え処理
        $sortBy = $request->query('sort_by', 'updated_at');
        $sortDirection = $request->query('sort_direction', 'desc');
        $sortableColumns = [
            'id',
            'company_name',
            'representative_name',
            'latest_call_status_id',
            'call_histories_count',
            'updated_at',
            'created_at',
            'latest_actual_call_date'
        ];
        if (!in_array($sortBy, $sortableColumns)) $sortBy = 'updated_at';
        if (!in_array(strtolower($sortDirection), ['asc', 'desc'])) $sortDirection = 'desc';

        // 特殊なソートを適用
        if ($sortBy === 'latest_actual_call_date') {
            $mainTable = (new CallList())->getTable();
            $latestCalledAtSubquery = CallHistory::select('called_at')
                ->whereColumn('call_list_id', $mainTable . '.id')
                ->orderBy('called_at', 'desc')->orderBy('id', 'desc')->limit(1);

            $query->select($mainTable . '.*') // メインテーブルのカラムを全て選択
                ->selectSub($latestCalledAtSubquery, 'latest_called_at_sortable') // サブクエリ結果を追加
                ->orderBy('latest_called_at_sortable', $sortDirection);
        } elseif ($sortBy === 'call_histories_count') {
            $query->withCount('callHistories') // 関連する履歴の件数を 'call_histories_count'として追加
                ->orderBy('call_histories_count', $sortDirection);
        } else {
            // 通常のカラムでソート
            $query->orderBy($sortBy, $sortDirection);
        }

        // 5. ページネーション (または全件取得)
        // $callLists = $query->get(); // 全件取得する場合
        $callLists = $query->paginate(15)->withQueryString(); // ページネーションの場合

        // 6. ビューに渡すその他のデータ
        $allStatuses = CallStatusMaster::orderBy('sort_order')->get();

        if ($callLists->isNotEmpty()) {
            // 1. ページに表示されている架電リストから、電話番号と会社名を全て集める
            $phoneNumbers = $callLists->pluck('phone_number')->filter();
            $mobilePhoneNumbers = $callLists->pluck('mobile_phone_number')->filter();
            $allPhoneNumbers = $phoneNumbers->merge($mobilePhoneNumbers)->unique();
            $companyNames = $callLists->pluck('company_name')->filter()->unique();

            // 2. 集めた電話番号または会社名に一致する架電禁止リストの情報を一度に取得
            //    誰が追加したかのユーザー情報も一緒に読み込む (Eager Loading)
            $dncEntries = DoNotCallList::whereIn('phone_number', $allPhoneNumbers)
                ->orWhereIn('company_name', $companyNames)
                ->with('addedByUser:id,name') // ユーザーのIDと名前だけ取得
                ->get();

            // 3. すぐに検索できるよう、電話番号と会社名をキーにしたマップ（連想配列）を作成
            $dncPhoneMap = $dncEntries->keyBy('phone_number');
            $dncCompanyMap = $dncEntries->keyBy('company_name');

            // 4. 各架電リストに、対応する架電禁止情報があれば紐付ける
            $callLists->each(function ($callList) use ($dncPhoneMap, $dncCompanyMap) {
                // 新しいプロパティ $dnc_info を各 $callList オブジェクトに追加
                $callList->dnc_info =
                    $dncPhoneMap[$callList->phone_number] ??
                    $dncPhoneMap[$callList->mobile_phone_number] ??
                    $dncCompanyMap[$callList->company_name] ??
                    null;
            });
        }

        return view('call_list.index', [
            'callLists'       => $callLists,
            'allStatuses'     => $allStatuses,
            'sortBy'          => $sortBy,
            'sortDirection'   => $sortDirection,
            // フィルター入力値をビューに戻して、選択状態を維持
            'filterCompanyName' => $request->query('filter_company_name'),
            'filterPhoneNumber' => $request->query('filter_phone_number'),
            'filterStatusIds'   => $request->query('filter_status_ids') ?? [],
            'startDate'         => $request->query('start_date'),
            'endDate'           => $request->query('end_date'),
        ]);
    }

    /**
     * 新規架電リストの作成フォームを表示します。
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $statuses = CallStatusMaster::orderBy('sort_order')->get();
        return view('call_list.create', compact('statuses'));
    }

    /**
     * 新規架電リストをデータベースに保存します。
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'company_name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:50',
            // ... 他のバリデーションルール ...
            'latest_call_status_id' => 'required|exists:call_status_master,id',
            'latest_call_memo' => 'nullable|string',
        ]);

        // DNC (架電禁止リスト) のチェック
        $dncMessage = $this->checkDoNotCall($validatedData['phone_number'], $validatedData['company_name']);
        if ($dncMessage) {
            return redirect()->back()
                ->withErrors(['dnc_check' => $dncMessage])
                ->withInput();
        }

        // トランザクション内でリスト作成と履歴記録を実行
        DB::transaction(function () use ($validatedData) {
            $callList = CallList::create([
                'user_id' => Auth::id(),
                'company_name' => $validatedData['company_name'],
                // ... 他のフィールド ...
                'latest_call_status_id' => $validatedData['latest_call_status_id'],
                'latest_call_memo' => $validatedData['latest_call_memo'],
            ]);

            // 初回の架電履歴を記録
            CallHistory::create([
                'call_list_id' => $callList->id,
                'call_status_id' => $validatedData['latest_call_status_id'],
                'call_memo' => $validatedData['latest_call_memo'] ?? '初回登録時のステータス',
                'called_at' => Carbon::now(),
                'created_by_user_id' => Auth::id(),
            ]);
        });

        return redirect()->route('call-list.index')
            ->with('success', '架電リストに新しい情報を登録しました。');
    }

    /**
     * 指定されたリソースを表示します。(現在未使用)
     */
    public function show(CallList $callList)
    {
        // 必要に応じて実装
    }

    /**
     * 架電リストの編集フォームを表示します。
     *
     * @param  \App\Models\CallList  $callList
     * @return \Illuminate\View\View
     */
    public function edit(CallList $callList)
    {
        $statuses = CallStatusMaster::orderBy('sort_order')->get();
        return view('call_list.edit', compact('callList', 'statuses'));
    }

    /**
     * 架電リストの情報を更新します。
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CallList  $callList
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, CallList $callList)
    {

        $validatedData = $request->validate([
            'company_name'          => 'required|string|max:255',
            'representative_name'   => 'nullable|string|max:100',
            'address'               => 'nullable|string|max:255',
            'phone_number'          => 'nullable|string|max:50', // 単一の電話番号
            'mobile_phone_number'   => 'nullable|string|max:50', // 単一の携帯電話番号
            'mobile_phone_owner'    => 'nullable|string|max:100',
            'url_website'           => 'nullable|url:http,https|max:255',
            'url_instagram'         => 'nullable|url:http,https|max:255',
            'url_sns_other'         => 'nullable|url:http,https|max:255',
            'email'                 => 'nullable|email|max:255',
            'website_url'           => 'nullable|url|max:2048',
            'instagram_url' => 'nullable|url|max:2048',


        ]);

        // ★ シンプルな更新処理
        $callList->update($validatedData);

        // ★ 成功時のJSONレスポンス (変更なし)
        return redirect()->route('call-list.index')->with('success', '架電リスト (ID: ' . $callList->id . ') を更新しました。');
    }

    /**
     * 架電リストを削除します。
     *
     * @param  \App\Models\CallList  $callList
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(CallList $callList)
    {
        $companyName = $callList->company_name;
        $callList->delete(); // ソフトデリート
        return response()->json([
            'success' => true,
            'message' => "{$companyName} (ID: {$callList->id}) を削除しました。"
        ]);
    }

    /**
     * 架電結果を記録します。
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CallList  $callList
     * @return \Illuminate\Http\JsonResponse
     */
    public function recordCall(Request $request, CallList $callList): JsonResponse
    {
        $validatedData = $request->validate([
            'call_status_id' => 'required|exists:call_status_master,id',
            'call_memo'      => 'nullable|string|max:10000',
            'called_at'      => 'required|date_format:Y-m-d\TH:i',
        ]);

        // ★★★ 修正点: 重複していたcreateを1つに修正 ★★★
        $callHistory = CallHistory::create([
            'call_list_id'       => $callList->id,
            'call_status_id'     => $validatedData['call_status_id'],
            'call_memo'          => $validatedData['call_memo'],
            'called_at'          => Carbon::parse($validatedData['called_at']),
            'created_by_user_id' => Auth::id(),
        ]);

        // 連続不在回数の更新
        $missedCallStatusId = 2; // 「不在着信」のID (定数やconfigで管理するのが望ましい)
        $streak = CallListStreak::firstOrCreate(['call_list_id' => $callList->id]);

        if ($callHistory->call_status_id == $missedCallStatusId) {
            $streak->increment('consecutive_missed_calls');
        } else {
            $streak->consecutive_missed_calls = 0;
            $streak->save();
        }

        // 架電リストの最新情報を更新
        $callList->latest_call_status_id = $validatedData['call_status_id'];
        $callList->latest_call_memo      = $validatedData['call_memo'];
        $callList->save(); // touch()はsave()時に自動で呼ばれるため不要

        // 更新後のステータス情報をフロントエンドに返す
        $callList->load('latestCallStatus');
        $newStatusName = $callList->latestCallStatus ? $callList->latestCallStatus->status_name : '未設定';

        return response()->json([
            'success' => true,
            'message' => $callList->company_name . ' の架電結果を記録しました。',
            'updated_call_list_data' => [
                'id'                       => $callList->id,
                'latest_call_status_name'  => $newStatusName,
                'latest_call_status_classes' => $this->getStatusTailwindClasses($newStatusName),
                'latest_call_memo_short'   => Str::limit($callList->latest_call_memo, 30),
                'updated_at_formatted'     => $callList->updated_at->format('Y/m/d H:i'),
            ]
        ]);
    }

    /**
     * 指定された架電リストの履歴を取得します。
     *
     * @param  string $callListId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHistories(string $callListId): JsonResponse
    {
        $histories = CallHistory::where('call_list_id', $callListId)
            ->with(['user:id,name', 'status:id,status_name'])
            ->orderBy('called_at', 'desc')
            ->get();

        if ($histories->isEmpty() && !CallList::find($callListId)) {
            return response()->json(['error' => '指定された架電リストが見つかりません。'], 404);
        }

        return response()->json($histories);
    }

    /**
     * CSVインポート用のフォームを表示します。
     *
     * @return \Illuminate\View\View
     */
    public function showImportForm()
    {
        return view('call_list.import');
    }

    /**
     * アップロードされたCSVファイルを処理してデータをインポートします。
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processImport(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt,text/csv,text/plain|max:10240', // 10MB
        ]);

        try {
            // --- 準備フェーズ ---
            $file = $request->file('csv_file');
            $filePath = $file->getRealPath();

            // デフォルトステータス「未対応」を確実に取得する
            $defaultStatus = CallStatusMaster::where('status_name', '未対応')->first();
            if (!$defaultStatus) {
                // このエラーは、システム設定の問題であるため、ユーザーに明確に伝える
                return redirect()->route('call-list.import.form')
                    ->with('error', 'インポートに必要な基本ステータス「未対応」がシステムに登録されていません。管理者に連絡してください。');
            }
            $defaultStatusId = $defaultStatus->id;

            $allStatusesMap = CallStatusMaster::pluck('id', 'status_name');
            $currentUser = Auth::user();

            $results = [
                'imported' => 0,
                'dnc_skipped' => 0,
                'duplicate_skipped' => 0,
                'error' => 0,
                'errors' => [],
            ];
            $lineNumber = 1;

            if (($handle = fopen($filePath, 'r')) === FALSE) {
                return redirect()->route('call-list.import.form')->with('error', 'CSVファイルを開けませんでした。');
            }

            // BOM(Byte Order Mark)を除去し、ヘッダーを取得
            $header = array_map(fn($h) => trim(str_replace("\u{FEFF}", '', $h)), fgetcsv($handle));

            if (!$header || empty(array_filter($header))) {
                fclose($handle);
                return redirect()->route('call-list.import.form')->with('error', 'CSVファイルが空か、ヘッダーが読み込めませんでした。');
            }

            if (!in_array('会社名', $header)) {
                fclose($handle);
                return redirect()->route('call-list.import.form')->with('error', 'CSVファイルに必須のヘッダー「会社名」が見つかりません。');
            }

            $columnMap = array_flip($header);

            // --- インポート処理フェーズ ---
            DB::beginTransaction();

            while (($row = fgetcsv($handle)) !== FALSE) {
                $lineNumber++;

                $companyName = isset($columnMap['会社名']) ? trim($row[$columnMap['会社名']]) : '';
                if (empty($companyName)) {
                    $results['errors'][] = "{$lineNumber}行目: 会社名が空のためスキップしました。";
                    $results['error']++;
                    continue;
                }

                $statusNameFromCsv = isset($columnMap['架電状況名']) ? trim($row[$columnMap['架電状況名']]) : '';
                $latestCallStatusId = $defaultStatusId; // 必ず存在するデフォルトIDをまず設定
                if (!empty($statusNameFromCsv)) {
                    if (isset($allStatusesMap[$statusNameFromCsv])) {
                        $latestCallStatusId = $allStatusesMap[$statusNameFromCsv];
                    } else {
                        $results['errors'][] = "{$lineNumber}行目: 架電状況名「{$statusNameFromCsv}」が無効なため、デフォルトで登録します。";
                    }
                }

                $dataToCreate = [
                    'user_id'               => $currentUser->id,
                    'company_name'          => $companyName,
                    'latest_call_status_id' => $latestCallStatusId,
                    'phone_number'          => isset($columnMap['電話番号(固定)']) ? trim($row[$columnMap['電話番号(固定)']] ?? '') : null,
                    'mobile_phone_number'   => isset($columnMap['電話番号(携帯)']) ? trim($row[$columnMap['電話番号(携帯)']] ?? '') : null,
                    'representative_name'   => isset($columnMap['代表者名']) ? trim($row[$columnMap['代表者名']] ?? '') : null,
                    'mobile_phone_owner'    => isset($columnMap['携帯番号主名']) ? trim($row[$columnMap['携帯番号主名']] ?? '') : null,
                    'address'               => isset($columnMap['所在地']) ? trim($row[$columnMap['所在地']] ?? '') : null,
                    'latest_call_memo'      => isset($columnMap['架電内容メモ']) ? trim($row[$columnMap['架電内容メモ']] ?? '') : null,
                    'url_website'           => isset($columnMap['WEBサイトURL']) ? trim($row[$columnMap['WEBサイトURL']] ?? '') : null,
                    'url_instagram'         => isset($columnMap['Instagram URL']) ? trim($row[$columnMap['Instagram URL']] ?? '') : null,
                    'url_sns_other'         => isset($columnMap['その他SNS URL']) ? trim($row[$columnMap['その他SNS URL']] ?? '') : null,
                    'remarks'               => isset($columnMap['備考']) ? trim($row[$columnMap['備考']] ?? '') : null,
                    'source_of_data'        => 'CSV Import',
                ];

                $validator = Validator::make($dataToCreate, [
                    'company_name'          => 'required|string|max:255',
                    'latest_call_status_id' => 'required|integer|exists:call_status_master,id',
                    'phone_number'          => 'nullable|string|max:50',
                    'mobile_phone_number'   => 'nullable|string|max:50',
                    'email'                 => 'nullable|email|max:255',
                ]);

                if ($validator->fails()) {
                    $results['errors'][] = "{$lineNumber}行目: データ不備 (" . implode(', ', $validator->errors()->all()) . ")";
                    $results['error']++;
                    continue;
                }

                if ($this->isDnc($dataToCreate)) {
                    $results['errors'][] = "{$lineNumber}行目: 架電禁止リスト({$companyName})に該当するためスキップしました。";
                    $results['dnc_skipped']++;
                    continue;
                }

                if ($this->isDuplicate($dataToCreate, $currentUser->id)) { // ★引数に $currentUser->id を渡すように少し変更
                    $results['errors'][] = "{$lineNumber}行目: 既にリスト({$companyName})に存在するためスキップしました。";
                    $results['duplicate_skipped']++;
                    continue;
                }

                CallList::create($dataToCreate);
                $results['imported']++;
            }

            DB::commit();
            fclose($handle);
        } catch (\Exception $e) {
            // --- ★★★ エラーアラート強化 ★★★ ---
            // 予期せぬエラーが発生した場合の処理
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            DB::rollBack();

            // エラーの詳細をログに記録
            Log::error('CSV Import Error: ' . $e->getMessage() . ' on line ' . $e->getLine());

            // ユーザーには、より分かりやすいメッセージを表示
            $errorMessage = 'CSVインポート処理中に予期せぬエラーが発生しました。';
            // もし開発環境(APP_DEBUG=true)なら、詳細なエラーを表示する
            if (config('app.debug')) {
                $errorMessage .= ' エラー内容: ' . $e->getMessage();
            } else {
                $errorMessage .= ' システム管理者にご連絡ください。';
            }
            return redirect()->route('call-list.import.form')->with('error', $errorMessage);
        }

        // --- 結果表示フェーズ (変更なし) ---
        $status = $results['imported'] > 0 ? 'success' : 'warning';
        $message = "{$results['imported']}件のインポートに成功しました。";
        $warnings = [];
        if ($results['dnc_skipped'] > 0) $warnings[] = "DNCリストにより{$results['dnc_skipped']}件スキップ";
        if ($results['duplicate_skipped'] > 0) $warnings[] = "重複により{$results['duplicate_skipped']}件スキップ";
        if ($results['error'] > 0) $warnings[] = "エラーやデータ不備により{$results['error']}件スキップ";
        if (!empty($warnings)) {
            $message .= ' (' . implode('、', $warnings) . ')';
        }

        return redirect()->route('call-list.import.form')->with($status, $message)->with('import_errors', $results['errors']);
    }

    private function isDnc(array $data): bool
    {
        // チェック対象の会社名、電話番号、携帯番号が全て空なら、DNCには該当しない
        if (empty($data['company_name']) && empty($data['phone_number']) && empty($data['mobile_phone_number'])) {
            return false;
        }

        // DoNotCallListテーブルに対してクエリを開始し、
        // 会社名、電話番号(固定)、電話番号(携帯) のいずれかが一致するかを一度にチェック
        return DoNotCallList::query()
            ->where(function ($query) use ($data) {
                if (!empty($data['company_name'])) {
                    $query->orWhere('company_name', $data['company_name']);
                }
                if (!empty($data['phone_number'])) {
                    $query->orWhere('phone_number', $data['phone_number']);
                }
                if (!empty($data['mobile_phone_number'])) {
                    $query->orWhere('phone_number', $data['mobile_phone_number']);
                }
            })
            ->exists(); // 1件でも存在すれば true を返す
    }

    private function isDuplicate(array $data): bool
    {
        // 会社名と電話番号が両方とも空なら、重複とはみなさない
        if (empty($data['company_name']) && empty($data['phone_number']) && empty($data['mobile_phone_number'])) {
            return false;
        }

        // ログインユーザーのリストに限定して検索
        $query = CallList::where('user_id', Auth::id());

        $query->where(function ($q) use ($data) {
            // 会社名が同じか
            if (!empty($data['company_name'])) {
                $q->orWhere('company_name', $data['company_name']);
            }
            // または、いずれかの電話番号が同じか
            if (!empty($data['phone_number'])) {
                $q->orWhere('phone_number', $data['phone_number'])
                    ->orWhere('mobile_phone_number', $data['phone_number']);
            }
            if (!empty($data['mobile_phone_number'])) {
                $q->orWhere('phone_number', $data['mobile_phone_number'])
                    ->orWhere('mobile_phone_number', $data['mobile_phone_number']);
            }
        });

        return $query->exists();
    }


    // =================================================================
    // Private Helper Methods
    // =================================================================

    /**
     * 優先架電リストのID配列を取得します。
     *
     * @param  string $timeOfDay 'am' または 'pm'
     * @return array
     */
    private function _getPriorityListIds(string $timeOfDay): array
    {
        Log::info("--- _getPriorityListIds [Final Logic Rev.2] started for: [{$timeOfDay}] ---");

        $missedCallStatusId = 2; // 「不在着信」
        $callAgainStatusId = 9;  // 「再度架電」

        // --- 条件1: 昨日の「不在着信」リスト（最新の不在履歴のみを判定）---
        $missedCallListIds = [];

        // 判定対象となる期間を設定
        $periodStart = ($timeOfDay === 'am')
            ? Carbon::yesterday()->setTime(12, 0, 0) // 午前リスト -> 昨日の午後
            : Carbon::yesterday()->setTime(6, 0, 0);   // 午後リスト -> 昨日の午前

        $periodEnd = ($timeOfDay === 'am')
            ? Carbon::yesterday()->setTime(22, 0, 0) // 要件通り22時まで
            : Carbon::yesterday()->setTime(11, 59, 59); // 12時まで

        Log::info("Priority List ({$timeOfDay}) - Condition 1: Checking for LATEST missed calls between {$periodStart->toDateTimeString()} and {$periodEnd->toDateTimeString()}");

        // サブクエリ: 各call_list_idごとに、最新の「不在着信」の日時を1つだけ取得
        $latestMissedCallSubquery = CallHistory::select('call_list_id', DB::raw('MAX(called_at) as latest_missed_at'))
            ->where('call_status_id', $missedCallStatusId)
            ->groupBy('call_list_id');

        // 上記サブクエリの結果 (各リストの最新不在日時) を使って、指定期間に該当するリストIDを取得
        $baseMissedCallListIds = DB::table(DB::raw("({$latestMissedCallSubquery->toSql()}) as latest_misses"))
            ->mergeBindings($latestMissedCallSubquery->getQuery())
            ->whereBetween('latest_missed_at', [$periodStart, $periodEnd])
            ->pluck('call_list_id');

        Log::info("Priority List ({$timeOfDay}) - Condition 1: Found " . $baseMissedCallListIds->count() . " raw lists based on their LATEST missed call time.");

        $missedCallListIds = CallList::whereIn('id', $baseMissedCallListIds)
            ->where(function ($query) {
                $query->whereDoesntHave('streak') // streak記録がない (＝連続0回)
                    ->orWhereHas('streak', function ($q) {
                        // オブザーバーのおかげで、この値は常に「"現在"連続している不在回数」になる
                        $q->where('consecutive_missed_calls', '<', 4);
                    });
            })
            ->pluck('id')
            ->toArray();

        if ($baseMissedCallListIds->isNotEmpty()) {
            // 連続不在が4回未満のリストに絞り込み
            $missedCallListIds = CallList::whereIn('id', $baseMissedCallListIds)
                ->where(function ($query) {
                    $query->whereDoesntHave('streak') // streak記録がない (＝連続不在ではない)
                        ->orWhereHas('streak', function ($q) {
                            $q->where('consecutive_missed_calls', '<', 4); // またはstreakが4回未満
                        });
                })
                ->pluck('id')
                ->toArray();
            Log::info("Priority List ({$timeOfDay}) - Condition 1: After streak filtering (< 4), " . count($missedCallListIds) . " lists remain.");
        }


        // --- 条件2: 2週間前の「再度架電」タスク (このロジックは変更なし) ---
        $callAgainListIds = [];
        $twoWeeksAgo = Carbon::today()->subWeeks(2);
        if ($timeOfDay === 'am') {
            // 午前リストの場合 → 2週間前の「午前」の再度架電タスク
            $periodStart = $twoWeeksAgo->copy()->setTime(6, 0, 0);
            $periodEnd = $twoWeeksAgo->copy()->setTime(11, 59, 59);
            Log::info("Priority List ({$timeOfDay}) - Condition 2: Checking 2-weeks-ago AM 'Call Again' tasks ({$periodStart} to {$periodEnd})");
        } else { // 'pm'
            // 午後リストの場合 → 2週間前の「午後」の再度架電タスク
            $periodStart = $twoWeeksAgo->copy()->setTime(12, 0, 0);
            $periodEnd = $twoWeeksAgo->copy()->setTime(22, 0, 0);
            Log::info("Priority List ({$timeOfDay}) - Condition 2: Checking 2-weeks-ago PM 'Call Again' tasks ({$periodStart} to {$periodEnd})");
        }

        $callAgainListIds = CallHistory::where('call_status_id', $callAgainStatusId)
            ->whereBetween('called_at', [$periodStart, $periodEnd])
            ->distinct()
            ->pluck('call_list_id')
            ->toArray();
        Log::info("Priority List ({$timeOfDay}) - Condition 2: Found " . count($callAgainListIds) . " lists to call again.");


        // --- 最終的なIDリストを生成 ---
        $finalIds = array_unique(array_merge($missedCallListIds, $callAgainListIds));
        Log::info("Total unique priority list IDs found for [{$timeOfDay}]: " . count($finalIds));
        Log::info("--- _getPriorityListIds finished for [{$timeOfDay}] ---");

        return $finalIds;
    }

    /**
     * ステータス名に応じたTailwind CSSのクラスを返します。
     *
     * @param  string $statusName
     * @return string
     */
    private function getStatusTailwindClasses(string $statusName): string
    {
        return match ($statusName) {
            'アポイント' => 'bg-green-100 dark:bg-green-800 text-green-700 dark:text-green-200',
            '再度架電', '追いかけ', '留守番（折り返し）' => 'bg-yellow-100 dark:bg-yellow-800 text-yellow-700 dark:text-yellow-200',
            'NG', '求人なし', '電話番号なし' => 'bg-red-100 dark:bg-red-800 text-red-700 dark:text-red-200',
            default => 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200',
        };
    }

    /**
     * DNC(架電禁止リスト)をチェックし、該当する場合メッセージを返します。
     *
     * @param string|null $phoneNumber
     * @param string|null $companyName
     * @return string|null
     */
    private function checkDoNotCall(?string $phoneNumber, ?string $companyName): ?string
    {
        if (!empty($phoneNumber)) {
            $dncEntry = DoNotCallList::where('phone_number', $phoneNumber)->first();
            if ($dncEntry) {
                $reason = $dncEntry->reason ? " (理由: {$dncEntry->reason})" : '';
                return "電話番号「{$phoneNumber}」は架電禁止リストに登録されています。{$reason}";
            }
        }

        if (!empty($companyName)) {
            $dncEntry = DoNotCallList::where('company_name', $companyName)->first();
            if ($dncEntry) {
                $reason = $dncEntry->reason ? " (理由: {$dncEntry->reason})" : '';
                return "会社名「{$companyName}」は架電禁止リストに登録されています。{$reason}";
            }
        }
        return null;
    }
}
