<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoNotCallList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // ★ Authファサードをuseに追加
use Illuminate\Validation\Rule; // ★ Ruleファサードをuseに追加 (ユニーク制約の無視に使う)
use Illuminate\Http\JsonResponse;  // 既にあれば不要
use Illuminate\Support\Facades\Log; // ★ 追加





class DoNotCallListController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // addedByUser リレーションを Eager Loading し、ページネーションを適用
        $dncLists = DoNotCallList::with('addedByUser')
            ->orderBy('created_at', 'desc') // 作成日の新しい順
            ->paginate(15); // 1ページあたり15件表示

        return view('admin.dnc_lists.index', compact('dncLists'));
    }

    /**
     * 指定された電話番号または会社名がDNCリストに存在するか確認する (リアルタイムチェック用)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkDncStatus(Request $request): JsonResponse
    {
        $phoneNumber = $request->query('phone_number');
        $companyName = $request->query('company_name');

        Log::info('[DNC Realtime Check] Received - Phone: ' . ($phoneNumber ?? 'N/A') . ', Company: ' . ($companyName ?? 'N/A')); // ★ 受信値をログ

        $isDnc = false;
        $message = '';
        $foundEntry = null; // $foundEntry をループの外で初期化

        if ($phoneNumber) {
            Log::info('[DNC Realtime Check] Checking phone number: \'' . $phoneNumber . '\'');
            $phoneDncEntry = DoNotCallList::whereNotNull('phone_number')
                ->where('phone_number', $phoneNumber)
                ->first();
            if ($phoneDncEntry) {
                Log::info('[DNC Realtime Check] Phone DNC entry found:', $phoneDncEntry->toArray());
                $isDnc = true;
                $message = __('電話番号「:value」は架電禁止リストに登録されています。', ['value' => $phoneNumber]);
                $foundEntry = $phoneDncEntry; // メッセージ用の foundEntry を更新
            } else {
                Log::info('[DNC Realtime Check] No DNC entry for phone: \'' . $phoneNumber . '\'');
            }
        }

        if (!$isDnc && $companyName) {
            Log::info('[DNC Realtime Check] Checking company name: \'' . $companyName . '\'');
            $companyDncEntry = DoNotCallList::whereNotNull('company_name')
                ->where('company_name', $companyName)
                ->first();
            if ($companyDncEntry) {
                Log::info('[DNC Realtime Check] Company DNC entry found:', $companyDncEntry->toArray());
                $isDnc = true;
                $message = __('会社名「:value」は架電禁止リストに登録されています。', ['value' => $companyName]);
                $foundEntry = $companyDncEntry; // メッセージ用の foundEntry を更新
            } else {
                Log::info('[DNC Realtime Check] No DNC entry for company: \'' . $companyName . '\'');
            }
        }

        if ($isDnc && $foundEntry && $foundEntry->reason) {
            $message .= __(' (理由: :reason)', ['reason' => $foundEntry->reason]);
        }

        Log::info('[DNC Realtime Check] Response - is_dnc: ' . ($isDnc ? 'true' : 'false') . ', message: ' . $message); // ★ レスポンス内容をログ

        return response()->json([
            'is_dnc' => $isDnc,
            'message' => $isDnc ? $message : '',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.dnc_lists.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'phone_number' => [
                'nullable', // NULLを許可
                'string',
                'max:50',
                // phone_numberがNULLでない場合のみユニーク制約を適用 (company_nameとの組み合わせで少なくとも一方は必須)
                // 注意: uniqueルールは、値が空文字列("")の場合もチェックするため、nullableでも空文字列を許容したい場合は注意。
                // 今回は、空文字列をNULLとして扱うミドルウェア(ConvertEmptyStringsToNull)が有効なら問題なし。
                'unique:do_not_call_lists,phone_number',
                // 'required_without:company_name' // company_nameがなければphone_numberは必須
            ],
            'company_name' => [
                'nullable', // NULLを許可
                'string',
                'max:255',
                'unique:do_not_call_lists,company_name',
                // 'required_without:phone_number' // phone_numberがなければcompany_nameは必須
            ],
            'reason' => 'nullable|string|max:1000', // TEXT型なのでmaxは大きめでも良い
            'notes' => 'nullable|string|max:1000',
        ]);

        // phone_number と company_name の少なくとも一方が入力されているか追加でバリデーション
        if (empty($validatedData['phone_number']) && empty($validatedData['company_name'])) {
            // withErrors() を使って特定のエラーメッセージを返す
            return redirect()->back()
                ->withErrors(['phone_number' => __('電話番号または会社名の少なくとも一方は必須です。')])
                ->withInput();
        }

        // ログインユーザーのIDを追加者として記録
        $validatedData['added_by_user_id'] = Auth::id();

        DoNotCallList::create($validatedData);

        return redirect()->route('admin.dnc-lists.index')
            ->with('success', __('新しい架電禁止情報を登録しました。'));
    }

    /**
     * Display the specified resource.
     */
    public function show(DoNotCallList $doNotCallList)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DoNotCallList $doNotCallList) // ルートモデルバインディングを使用
    {
        // $doNotCallList には編集対象のデータが自動的にインジェクションされます
        return view('admin.dnc_lists.edit', compact('doNotCallList'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DoNotCallList $doNotCallList)
    {
        $validatedData = $request->validate([
            'phone_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('do_not_call_lists')->ignore($doNotCallList->id), // ★ 更新時は自分自身のレコードをユニークチェックから除外
            ],
            'company_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('do_not_call_lists')->ignore($doNotCallList->id), // ★ 更新時は自分自身のレコードをユニークチェックから除外
            ],
            'reason' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ]);

        if (empty($validatedData['phone_number']) && empty($validatedData['company_name'])) {
            return redirect()->back()
                ->withErrors(['phone_number' => __('電話番号または会社名の少なくとも一方は必須です。')])
                ->withInput();
        }

        // added_by_user_id は通常、更新時には変更しないが、もし変更のロジックが必要な場合はここに追加
        // $validatedData['added_by_user_id'] = Auth::id(); // 例: 更新者も記録する場合 (カラム追加が必要)

        $doNotCallList->update($validatedData);

        return redirect()->route('admin.dnc-lists.index')
            ->with('success', __('架電禁止情報を更新しました。'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DoNotCallList $doNotCallList) // ルートモデルバインディングを使用
    {
        // 削除対象の情報を取得（メッセージ用など、必要であれば）
        $identifier = $doNotCallList->phone_number ?? $doNotCallList->company_name ?? 'ID: ' . $doNotCallList->id;

        $doNotCallList->delete();

        return redirect()->route('admin.dnc-lists.index')
            ->with('success', __(':identifier の架電禁止情報を削除しました。', ['identifier' => $identifier]));
    }
}
