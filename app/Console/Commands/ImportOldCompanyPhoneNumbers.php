<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CallList;
use App\Models\CallListPhoneNumber;
use App\Models\User; // targetUserIdの存在確認用
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator; // 必要なら
use Carbon\Carbon;

class ImportOldCompanyPhoneNumbers extends Command
{
    protected $signature = 'import:old-company-phones {targetUserId} {--filepath=import/old_company_phone_numbers.csv}';
    protected $description = 'Imports old company phone numbers, linking them to existing CallList records via old_id and assigning to a target user.';

    private function sanitizeStringOrNull($value) {
        $trimmedValue = trim($value ?? '');
        return (empty($trimmedValue) || strtoupper($trimmedValue) === 'NULL') ? null : $trimmedValue;
    }

    public function handle(): int
    {
        $targetUserId = $this->argument('targetUserId'); // CallListを検索する際のユーザーID
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

        $this->info($targetUser->name . ' (ID: ' . $targetUserId . ') の架電リストに紐づく電話番号としてデータをインポートします。');
        $this->info('CSVファイル: ' . $fullFilepath);

        if (!$this->confirm('処理を開始してもよろしいですか？', true)) {
            $this->comment('処理を中止しました.');
            return Command::INVALID;
        }

        $importedCount = 0;
        $errorCount = 0;
        $skippedNoCallListCount = 0;
        $skippedExistingOldIdCount = 0;
        $errors = [];
        $lineNumber = 1;

        if (($handle = fopen($fullFilepath, 'r')) !== FALSE) {
            $header = fgetcsv($handle);
            if (!$header || count($header) < 6) { // CSVヘッダーは6列と仮定
                $this->error('CSVファイルのヘッダーが不正か、列数が不足しています。期待する列数: 6');
                fclose($handle);
                return Command::FAILURE;
            }

            DB::beginTransaction();
            try {
                while (($row = fgetcsv($handle)) !== FALSE) {
                    $lineNumber++;
                    if (count($row) < 6) {
                        $errors[] = $lineNumber . '行目: 列数が不足。スキップ。';
                        $errorCount++;
                        continue;
                    }

                    // CSV: "id","call_list_id"(旧),"phone_type","phone_number","created_at","updated_at"
                    $oldCompanyPhoneNumberId = trim($row[0] ?? '');
                    $oldCallListId = trim($row[1] ?? '');
                    $phoneType = $this->sanitizeStringOrNull($row[2]);
                    $phoneNumber = $this->sanitizeStringOrNull($row[3]);
                    $csvCreatedAt = $this->sanitizeStringOrNull($row[4]);
                    $csvUpdatedAt = $this->sanitizeStringOrNull($row[5]);

                    if (empty($phoneNumber)) {
                        $errors[] = $lineNumber . '行目 (旧ID:'.$oldCompanyPhoneNumberId.', 旧CallListID:'.$oldCallListId.'): 電話番号が空のためスキップ。';
                        $errorCount++;
                        continue;
                    }

                    // 1. 新しい CallList ID を検索 (old_id を使用)
                    $newCallList = CallList::where('old_id', $oldCallListId)
                                           ->where('user_id', $targetUserId) // ターゲットユーザーに紐づくリストから検索
                                           ->first();

                    if (!$newCallList) {
                        $errors[] = $lineNumber . '行目 (旧ID:'.$oldCompanyPhoneNumberId.', 旧CallListID:'.$oldCallListId.'): 対応する新しいCallListが見つからずスキップ。';
                        $skippedNoCallListCount++;
                        continue;
                    }

                    // 2. 既に同じ old_id の電話番号が登録されていないかチェック (再実行時の重複防止)
                    if (CallListPhoneNumber::where('old_id', $oldCompanyPhoneNumberId)->exists()) {
                        $errors[] = $lineNumber . '行目 (旧ID:'.$oldCompanyPhoneNumberId.'): 既に同じ旧IDの電話番号が存在するためスキップ。';
                        $skippedExistingOldIdCount++;
                        continue;
                    }

                    $dataToCreate = [
                        'call_list_id' => $newCallList->id,
                        'phone_type'   => $phoneType,
                        'phone_number' => $phoneNumber,
                        'old_id'       => ctype_digit($oldCompanyPhoneNumberId) ? (int)$oldCompanyPhoneNumberId : null,
                        'created_at'   => $csvCreatedAt ? Carbon::parse($csvCreatedAt)->toDateTimeString() : now()->toDateTimeString(),
                        'updated_at'   => $csvUpdatedAt ? Carbon::parse($csvUpdatedAt)->toDateTimeString() : now()->toDateTimeString(),
                    ];

                    // 簡単なバリデーション
                    $validator = Validator::make($dataToCreate, [
                        'call_list_id' => 'required|integer|exists:call_list,id',
                        'phone_type'   => 'nullable|string|max:50',
                        'phone_number' => 'required|string|max:50',
                        'old_id'       => 'nullable|integer',
                    ]);

                    if ($validator->fails()) {
                        $errors[] = $lineNumber . '行目 (旧ID:'.$oldCompanyPhoneNumberId.'): ' . implode(', ', $validator->errors()->all());
                        $errorCount++;
                        continue;
                    }

                    CallListPhoneNumber::create($dataToCreate);
                    $importedCount++;
                    if ($importedCount % 100 == 0) {
                        $this->info($importedCount . '件処理完了...');
                    }
                } // while end
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Old CompanyPhoneNumbers Import Error at line ' . $lineNumber . ': ' . $e->getMessage(), $dataToCreate ?? []);
                $this->error('エラーが発生しました (行: ' . $lineNumber . '): ' . $e->getMessage());
                if(!empty($errors)){ $this->line('これまでのエラー/スキップ詳細:'); foreach($errors as $err) { $this->line('- '. $err); } }
                return Command::FAILURE;
            }
            fclose($handle);

            $this->info('------------------------------------');
            $this->info('データ移行処理が完了しました。');
            $this->info($importedCount . '件の電話番号をインポートしました。');
            if ($skippedNoCallListCount > 0) $this->warn($skippedNoCallListCount . '件は対応するCallListが見つからずスキップ。');
            if ($skippedExistingOldIdCount > 0) $this->warn($skippedExistingOldIdCount . '件は旧IDが既に存在したためスキップ。');
            if ($errorCount > 0) {
                $this->warn($errorCount . '件のエラー/スキップがありました。詳細は以下の通りです:');
                foreach ($errors as $err) { $this->line('- ' . $err); }
            }
             if ($importedCount === 0 && $errorCount === 0 && $skippedNoCallListCount === 0 && $skippedExistingOldIdCount === 0 && $lineNumber > 1) {
                $this->info('インポート対象の新しいデータはありませんでした。');
            }

        } else {
            $this->error('CSVファイルを開けませんでした: ' . $fullFilepath);
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}