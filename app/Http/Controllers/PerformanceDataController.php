<?php

namespace App\Http\Controllers;

use App\Models\Company;
// use App\Models\PerformanceData; // $company->performanceData() で使うので直接は不要な場合も
use Illuminate\Http\Request;
use Carbon\Carbon; // Carbon を use
use App\Models\PerformanceData;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB; // トランザクション用
use Illuminate\Support\Facades\Log; // ログ用



class PerformanceDataController extends Controller
{
    /**
     * 指定された会社のパフォーマンスデータ一覧とグラフ用データを表示する
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\View\View
     */

    public function showImportForm(Company $company)
    {
        // companyモデルがルートモデルバインディングで渡ってくる
        return view('performance_data.import', compact('company')); // ★ 'import_form' から 'import' に変更
    }

    /**
     * アップロードされたパフォーマンスデータのCSVファイルを処理する
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processImport(Request $request, Company $company) // ★ このメソッドを追加または確認
    {
        // 引継ぎ情報によると「基本的な処理は実装済み」とのことなので、
        // もし既存の処理があれば、それをここに記述または呼び出す形になります。
        // 以下は、もし実装がまだの場合の基本的な処理の骨子です。

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048', // 2MBまで
        ]);

        $file = $request->file('csv_file');
        $filePath = $file->getRealPath();

        $importedCount = 0;
        $updatedCount = 0;
        $errorCount = 0;
        $errors = [];
        $lineNumber = 1;

        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            $header = fgetcsv($handle); // ヘッダー行 (日付,表示回数,クリック率(CTR),...)
            if (!$header || count($header) < 8) { // 最低でも8列あるかなど簡易チェック
                return redirect()->back()
                    ->with('error', 'CSVファイルのヘッダーが不正か、列数が不足しています。')
                    ->withInput();
            }

            DB::beginTransaction();
            try {
                while (($row = fgetcsv($handle)) !== FALSE) {
                    $lineNumber++;
                    if (count($row) < 8) {
                        $errors[] = $lineNumber . '行目: 列数が不足しています。';
                        $errorCount++;
                        continue;
                    }

                    $data = [
                        'date'               => trim($row[0] ?? ''),
                        'impressions'        => (int)($row[1] ?? 0),
                        'ctr'                => (float)($row[2] ?? 0),
                        'clicks'             => (int)($row[3] ?? 0),
                        'asr'                => (float)($row[4] ?? 0),
                        'application_starts' => (int)($row[5] ?? 0),
                        'completion_rate'    => (float)($row[6] ?? 0),
                        'applications'       => (int)($row[7] ?? 0),
                    ];

                    $validator = Validator::make($data, [
                        'date'               => 'required|date_format:Y-m-d', // CSVの日付形式に合わせる
                        'impressions'        => 'required|integer|min:0',
                        'ctr'                => 'required|numeric|min:0|max:1', // 0から1の間の小数
                        'clicks'             => 'required|integer|min:0',
                        'asr'                => 'required|numeric|min:0|max:1',
                        'application_starts' => 'required|integer|min:0',
                        'completion_rate'    => 'required|numeric|min:0|max:1',
                        'applications'       => 'required|integer|min:0',
                    ]);

                    if ($validator->fails()) {
                        $errors[] = $lineNumber . '行目: ' . implode(', ', $validator->errors()->all());
                        $errorCount++;
                        continue;
                    }

                    // データベースに保存 (company_id と date でユニークと仮定し、あれば更新、なければ作成)
                    $performance = PerformanceData::updateOrCreate(
                        [
                            'company_id' => $company->id,
                            'date' => $data['date'],
                        ],
                        [
                            'impressions'        => $data['impressions'],
                            'ctr'                => $data['ctr'],
                            'clicks'             => $data['clicks'],
                            'asr'                => $data['asr'],
                            'application_starts' => $data['application_starts'],
                            'completion_rate'    => $data['completion_rate'],
                            'applications'       => $data['applications'],
                            // 'created_by_user_id' => Auth::id(), // もし記録するなら
                        ]
                    );

                    if ($performance->wasRecentlyCreated) {
                        $importedCount++;
                    } else if ($performance->wasChanged()){ // 何か変更があった場合 (updateOrCreateは変更なくてもwasChanged=falseになることがある)
                        $updatedCount++;
                    } else {
                        // 変更なしの場合 (値が全く同じだったなど)
                        // $updatedCount++; // または何もしない、もしくは別のカウンター
                    }

                } // while end
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Performance CSV Import Error for Company ID {$company->id}: " . $e->getMessage());
                return redirect()->back()
                    ->with('error', 'CSVインポート処理中にエラーが発生しました。詳細はログを確認してください。')
                    ->with('import_errors', $errors);
            }
            fclose($handle);

            $feedbackMessage = '';
            if ($importedCount > 0) $feedbackMessages[] = $importedCount . '件のデータを新規登録しました。';
            if ($updatedCount > 0) $feedbackMessages[] = $updatedCount . '件のデータを更新しました。'; // 更新件数もフィードバック
            if ($errorCount > 0) $feedbackMessages[] = 'エラーにより ' . $errorCount . '件スキップしました。';
            $finalMessage = implode(' ', $feedbackMessages);
            if (empty($finalMessage)) $finalMessage = '処理対象のデータがありませんでした。';


            if ($errorCount > 0) {
                 return redirect()->route('performance_data.import.create', $company) // エラー時はフォームに戻す
                                 ->with('warning', $finalMessage)
                                 ->with('import_errors', $errors);
            } else {
                 // 成功時はパフォーマンスデータ一覧ページにリダイレクト
                 return redirect()->route('performance_data.index', $company)->with('success', $finalMessage);
            }

        } else {
            return redirect()->back()->with('error', 'CSVファイルを開けませんでした。')->withInput();
        }
    }

    public function index(Request $request, Company $company)
    {
        // --- 期間指定フィルター ---
        $validatedData = $request->validate([
            'start_date' => 'nullable|date|before_or_equal:end_date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $validatedData['start_date'] ?? null;
        $endDate = $validatedData['end_date'] ?? null;

        // パフォーマンスデータのクエリビルダ (テーブル表示用)
        $query = $company->performanceData();
        if ($startDate && $endDate) {
            $query->whereBetween('date', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()]);
        } elseif ($startDate) {
            $query->where('date', '>=', Carbon::parse($startDate)->startOfDay());
        } elseif ($endDate) {
            $query->where('date', '<=', Carbon::parse($endDate)->endOfDay());
        }
        $performanceData = $query->clone()
                                  ->orderBy('date', 'asc')
                                  ->paginate(30)
                                  ->withQueryString();

        // グラフ表示用およびサマリー計算用の全データ取得
        $chartQuery = $company->performanceData(); // 新しいクエリビルダインスタンス
        if ($startDate && $endDate) {
            $chartQuery->whereBetween('date', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()]);
        } elseif ($startDate) {
            $chartQuery->where('date', '>=', Carbon::parse($startDate)->startOfDay());
        } elseif ($endDate) {
            $chartQuery->where('date', '<=', Carbon::parse($endDate)->endOfDay());
        }
        $allPerformanceDataForChartAndSummary = $chartQuery->orderBy('date', 'asc')->get(); // ★ サマリー計算にも使う

        // Chart.js用のデータ準備
        $chartLabels = $allPerformanceDataForChartAndSummary->pluck('date')->map(function ($date) {
            return Carbon::parse($date)->format('Y/m/d');
        })->all();
        $chartImpressionsData = $allPerformanceDataForChartAndSummary->pluck('impressions')->all();
        $chartApplicationsData = $allPerformanceDataForChartAndSummary->pluck('applications')->all();

        // ★★★ ここからサマリーKPIの計算を追加 ★★★
        $totalImpressions = $allPerformanceDataForChartAndSummary->sum('impressions');
        $totalClicks = $allPerformanceDataForChartAndSummary->sum('clicks');
        $totalApplicationStarts = $allPerformanceDataForChartAndSummary->sum('application_starts');
        $totalApplications = $allPerformanceDataForChartAndSummary->sum('applications');

        // CTR (Click Through Rate) の計算: (総クリック数 / 総表示回数) * 100
        // ゼロ除算を避ける
        $averageCTR = ($totalImpressions > 0) ? ($totalClicks / $totalImpressions) * 100 : 0;

        // 応募完了率 (Completion Rate) の計算: (総応募数 / 総応募開始数) * 100
        // ゼロ除算を避ける
        $averageCompletionRate = ($totalApplicationStarts > 0) ? ($totalApplications / $totalApplicationStarts) * 100 : 0;
        // ★★★ サマリーKPIの計算ここまで ★★★


        return view('performance_data.index', compact(
            'company',
            'performanceData',
            'chartLabels',
            'chartImpressionsData',
            'chartApplicationsData',
            'startDate',
            'endDate',
            // ★★★ 計算したサマリーKPIをビューに渡す ★★★
            'totalImpressions',
            'totalClicks',
            'totalApplicationStarts',
            'totalApplications',
            'averageCTR',
            'averageCompletionRate'
        ));
    }
}