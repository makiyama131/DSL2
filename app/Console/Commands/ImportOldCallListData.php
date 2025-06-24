<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CallList;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ImportOldCallListData extends Command
{
    protected $signature = 'import:old-call-list {targetUserId} {--filepath=import/old_call_list_data.csv}';
    protected $description = 'Imports old call list data from a CSV file for a specific user, mapping old IDs and timestamps.';

    // emailとURLをPHPのnullに変換するヘルパー関数
    private function sanitizeStringOrNull($value) {
        $trimmedValue = trim($value ?? '');
        return (empty($trimmedValue) || strtoupper($trimmedValue) === 'NULL') ? null : $trimmedValue;
    }

    private function sanitizeUrlOrNull($value) {
        $url = $this->sanitizeStringOrNull($value);
        if ($url && !filter_var($url, FILTER_VALIDATE_URL)) {
            // URLとして不正な場合はnullにする（またはログに記録してスキップも可）
            Log::warning("Invalid URL format for value '{$url}' during import. Setting to null.");
            return null;
        }
        return $url;
    }

    private function sanitizeEmailOrNull($value) {
        $email = $this->sanitizeStringOrNull($value);
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // emailとして不正な場合はnullにする（またはログに記録してスキップも可）
            Log::warning("Invalid email format for value '{$email}' during import. Setting to null.");
            return null;
        }
        return $email;
    }


    public function handle(): int
    {
        $targetUserId = $this->argument('targetUserId');
        $filepath = $this->option('filepath');
        $fullFilepath = storage_path('app/' . $filepath);

        $targetUser = User::find($targetUserId);
        if (!$targetUser) {
            $this->error('指定されたユーザーID ' . $targetUserId . ' が見つかりません。');
            return Command::FAILURE;
        }

        if (!file_exists($fullFilepath)) {
            $this->error('CSVファイルが見つかりません: ' . $fullFilepath);
            return Command::FAILURE;
        }

        $this->info($targetUser->name . ' (ID: ' . $targetUserId . ') の架電リストとしてデータをインポートします。');
        $this->info('CSVファイル: ' . $fullFilepath);

        if (!$this->confirm('処理を開始してもよろしいですか？', true)) {
            $this->comment('処理を中止しました。');
            return Command::INVALID;
        }

        $importedCount = 0;
        $errorCount = 0;
        $skippedExistingOldIdCount = 0; // old_id重複スキップカウント
        $errors = []; // バリデーションエラーなどの詳細
        $lineNumber = 1;
        $defaultStatusId = 1; // ★ 例: 「未対応」のID
        $statusIdFromCsv = trim($row[7] ?? '');



        if (($handle = fopen($fullFilepath, 'r')) !== FALSE) {
            $header = fgetcsv($handle);
            if (!$header || count($header) < 17) {
                $this->error('CSVファイルのヘッダーが不正か、列数が不足しています。期待する列数: 17');
                fclose($handle);
                return Command::FAILURE;
            }

            DB::beginTransaction();
            try {
                while (($row = fgetcsv($handle)) !== FALSE) {
                    $lineNumber++;
                    if (count($row) < 17) {
                        $errors[] = $lineNumber . '行目: 列数が不足しています。スキップします。';
                        $errorCount++;
                        continue;
                    }

                    // CSVの列とデータをマッピング
                    $data = [
                        'old_id'                => (int)trim($row[0] ?? '0'),
                        'user_id'               => $targetUserId,
                        'company_name'          => $this->sanitizeStringOrNull($row[1]),
                        'address'               => $this->sanitizeStringOrNull($row[2]),
                        'phone_number'          => $this->sanitizeStringOrNull($row[3]),
                        'mobile_phone_number'   => $this->sanitizeStringOrNull($row[4]),
                        'mobile_phone_owner'    => $this->sanitizeStringOrNull($row[5]),
                        'representative_name'   => $this->sanitizeStringOrNull($row[6]),
                        'latest_call_status_id' => ctype_digit($statusIdFromCsv) ? (int)$statusIdFromCsv : $defaultStatusId,
                        'latest_call_memo'      => $this->sanitizeStringOrNull($row[8]),
                        'url_instagram'         => $this->sanitizeUrlOrNull($row[9]),  // CSV 'instagram_url' -> DB 'url_instagram'
                        'email'                 => $this->sanitizeEmailOrNull($row[10]),
                        'url_website'           => $this->sanitizeUrlOrNull($row[11]), // CSV 'website_url' -> DB 'url_website'
                        'source_of_data'        => $this->sanitizeStringOrNull($row[12]),
                        'remarks'               => $this->sanitizeStringOrNull($row[13]), // CSV 'company_remarks' -> DB 'remarks'
                        'created_at'            => $this->sanitizeStringOrNull($row[14]) ? Carbon::parse($this->sanitizeStringOrNull($row[14]))->toDateTimeString() : now()->toDateTimeString(),
                        'updated_at'            => $this->sanitizeStringOrNull($row[15]) ? Carbon::parse($this->sanitizeStringOrNull($row[15]))->toDateTimeString() : now()->toDateTimeString(),
                        'deleted_at'            => $this->sanitizeStringOrNull($row[16]) ? Carbon::parse($this->sanitizeStringOrNull($row[16]))->toDateTimeString() : null,
                        // 'url_sns_other' はCSVにないので、モデルの$fillableにあればNULLになる
                    ];

                    // バリデーションルール (CallListモデルの$fillableとテーブル構造に合わせる)
                    $validator = Validator::make($data, [
                        'old_id'                => 'required|integer|min:1',
                        'user_id'               => 'required|integer|exists:users,id',
                        'company_name'          => 'required|string|max:255',
                        'address'               => 'nullable|string|max:255',
                        'phone_number'          => 'nullable|string|max:50',
                        'mobile_phone_number'   => 'nullable|string|max:50',
                        'mobile_phone_owner'    => 'nullable|string|max:100',
                        'representative_name'   => 'nullable|string|max:100',
                        'latest_call_status_id' => 'required|integer|exists:call_status_master,id',
                        'latest_call_memo'      => 'nullable|string|max:1000',
                        'url_instagram'         => 'nullable|string|max:255', // sanitizeUrlOrNullでURL形式はチェック済み(不正ならnullになる)
                        'email'                 => 'nullable|string|max:255', // sanitizeEmailOrNullでemail形式はチェック済み(不正ならnullになる)
                        'url_website'           => 'nullable|string|max:255', // sanitizeUrlOrNullでURL形式はチェック済み(不正ならnullになる)
                        'source_of_data'        => 'nullable|string|max:255',
                        'remarks'               => 'nullable|string|max:1000',
                        'created_at'            => 'required|date',
                        'updated_at'            => 'required|date',
                        'deleted_at'            => 'nullable|date',
                    ]);

                    if ($validator->fails()) {
                        $errors[] = $lineNumber . '行目 (旧ID: ' . $data['old_id'] . '): ' . implode(', ', $validator->errors()->all());
                        $errorCount++;
                        continue;
                    }

                    // 既存のold_idを持つレコードがあればスキップ
                    if (CallList::where('old_id', $data['old_id'])->where('user_id', $targetUserId)->withTrashed()->exists()) { // ソフトデリート済みもチェック
                         $errors[] = $lineNumber . '行目 (旧ID: ' . $data['old_id'] . '): 既に同じ旧IDのデータが存在するためスキップ。';
                         $skippedExistingOldIdCount++;
                         continue;
                    }

                    CallList::create($data);
                    $importedCount++;
                    if ($importedCount % 100 == 0) {
                        $this->info($importedCount . '件処理完了...');
                    }
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Old CallList Import Error at line ' . $lineNumber . ': ' . $e->getMessage(), $data ?? []);
                $this->error('エラーが発生しました (行: ' . $lineNumber . '): ' . $e->getMessage());
                // エラー発生時点までのエラー詳細を表示
                if (!empty($errors)) {
                    $this->line('これまでのバリデーションエラー/スキップ詳細:');
                    foreach ($errors as $err) { $this->line('- ' . $err); }
                }
                return Command::FAILURE;
            }
            fclose($handle);

            $this->info('------------------------------------');
            $this->info('データ移行処理が完了しました。');
            $this->info($importedCount . '件のデータをインポートしました。');
            if ($skippedExistingOldIdCount > 0) {
                $this->warn($skippedExistingOldIdCount . '件は旧IDが既に存在したためスキップしました。');
            }
            if ($errorCount > 0) {
                $this->warn($errorCount . '件のバリデーションエラーまたは列数不足がありました。詳細は以下の通りです:');
                foreach ($errors as $err) {
                    $this->line('- ' . $err);
                }
            }
            if ($importedCount === 0 && $errorCount === 0 && $skippedExistingOldIdCount === 0 && $lineNumber > 1) {
                 $this->info('インポート対象の新しいデータはありませんでした。');
            }

        } else {
            $this->error('CSVファイルを開けませんでした: ' . $fullFilepath);
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}