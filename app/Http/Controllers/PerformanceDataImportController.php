<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PerformanceData; // PerformanceDataモデルを使用
use Illuminate\Http\Request;
use League\Csv\Reader; // CSVリーダーを使用
use League\Csv\Statement; // CSVステートメントを使用 (オプション)
use Illuminate\Support\Facades\Validator; // バリデーションファサードを使用
use Carbon\Carbon; // 日付操作のためにCarbonを使用
use Exception; // 例外処理のため

class PerformanceDataImportController extends Controller
{
    /**
     * CSVアップロードフォームを表示する
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\View\View
     */
    public function create(Company $company)
    {
        return view('performance_data.import', compact('company'));
    }

    /**
     * アップロードされたCSVを処理して保存する
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Company $company)
    {
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt|max:2048', // 必須、CSVまたはTXT形式、最大2MB
        ]);

        if (!$request->file('csv_file')->isValid()) {
            return back()->with('error', 'CSVファイルのアップロードに失敗しました。');
        }

        $path = $request->file('csv_file')->getRealPath();
        $processedCount = 0;
        $skippedCount = 0;
        $errorMessages = [];

        try {
            $csv = Reader::createFromPath($path, 'r');
            $csv->setHeaderOffset(0); // 1行目をヘッダーとして設定

            // ヘッダーが期待通りか確認 (オプション)
            $expectedHeaders = ['日付','表示回数','クリック率（CTR）','クリック数','応募開始率 (ASR)','応募開始数','応募完了率','応募数'];
            if ($csv->getHeader() !== $expectedHeaders) {
                return back()->with('error', 'CSVのヘッダー形式が正しくありません。期待するヘッダー: ' . implode(',', $expectedHeaders));
            }

            $records = $csv->getRecords(); // ヘッダーをキーとした連想配列としてレコードを取得

            foreach ($records as $index => $record) {
                $rowNumber = $index + 1; // CSVの行番号 (ヘッダー行を除く)

                // データのバリデーションと型変換
                $validator = Validator::make($record, [
                    '日付' => 'required|date_format:Y-m-d', // YYYY-MM-DD形式を期待
                    '表示回数' => 'required|integer|min:0',
                    'クリック率（CTR）' => 'required|numeric|min:0|max:1', // 0から1の間の数値
                    'クリック数' => 'required|integer|min:0',
                    '応募開始率 (ASR)' => 'required|numeric|min:0|max:1',
                    '応募開始数' => 'required|integer|min:0',
                    '応募完了率' => 'required|numeric|min:0|max:1',
                    '応募数' => 'required|integer|min:0',
                ]);

                if ($validator->fails()) {
                    $skippedCount++;
                    $errorMessages[] = "{$rowNumber}行目: " . implode(', ', $validator->errors()->all());
                    continue; // エラーのある行はスキップ
                }

                // バリデーション済みデータの取得
                $validatedData = $validator->validated();

                PerformanceData::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'date' => Carbon::createFromFormat('Y-m-d', $validatedData['日付'])->toDateString(),
                    ],
                    [
                        'impressions' => $validatedData['表示回数'],
                        'ctr' => $validatedData['クリック率（CTR）'],
                        'clicks' => $validatedData['クリック数'],
                        'asr' => $validatedData['応募開始率 (ASR)'],
                        'application_starts' => $validatedData['応募開始数'],
                        'completion_rate' => $validatedData['応募完了率'],
                        'applications' => $validatedData['応募数'],
                    ]
                );
                $processedCount++;
            }

        } catch (Exception $e) {
            // CSVパース自体のエラーなど、予期せぬエラー
            return back()->with('error', 'CSVファイルの処理中にエラーが発生しました: ' . $e->getMessage());
        }

        $feedbackMessage = $company->name . " のパフォーマンスデータをインポートしました。処理件数: {$processedCount}件。";
        if ($skippedCount > 0) {
            $feedbackMessage .= " スキップ件数: {$skippedCount}件。詳細はログ等で確認してください。（エラー詳細: " . implode('; ', $errorMessages) . "）";
            return redirect()->route('performance_data.import.create', $company) // エラーがあった場合はフォームに戻す
                             ->with('warning', $feedbackMessage); // warningメッセージとして表示
        }

        return redirect()->route('companies.index') // 成功時は会社一覧へ (または会社詳細やデータ一覧などへ)
                         ->with('success', $feedbackMessage);
    }
}