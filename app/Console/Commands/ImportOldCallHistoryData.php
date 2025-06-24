<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CallList;
use App\Models\CallHistory;
use App\Models\User; // targetUserIdの存在確認用
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ImportOldCallHistoryData extends Command
{
    protected $signature = 'import:old-call-history {targetUserId} {--filepath=import/old_call_history_data.csv}';
    protected $description = 'Imports old call history data from a CSV file, mapping to new call_list IDs via old_id and assigning to a target user.';

    private function sanitizeStringOrNull($value) {
        $trimmedValue = trim($value ?? '');
        return (empty($trimmedValue) || strtoupper($trimmedValue) === 'NULL') ? null : $trimmedValue;
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

        $this->info($targetUser->name . ' (ID: ' . $targetUserId . ') の架電リストに紐づく履歴としてデータをインポートします。');
        $this->info('CSVファイル: ' . $fullFilepath);

        if (!$this->confirm('処理を開始してもよろしいですか？', true)) {
            $this->comment('処理を中止しました。');
            return Command::INVALID;
        }

        $importedCount = 0;
        $errorCount = 0;
        $skippedNoCallListCount = 0;
        $errors = [];
        $lineNumber = 1;

        if (($handle = fopen($fullFilepath, 'r')) !== FALSE) {
            $header = fgetcsv($handle); // ヘッダー行
            if (!$header || count($header) < 7) { // CSVヘッダーは7列と仮定
                $this->error('CSVファイルのヘッダーが不正か、列数が不足しています。期待する列数: 7');
                fclose($handle);
                return Command::FAILURE;
            }

            DB::beginTransaction();
            try {
                while (($row = fgetcsv($handle)) !== FALSE) {
                    $lineNumber++;
                    if (count($row) < 7) {
                        $errors[] = $lineNumber . '行目: 列数が不足しています。スキップします。';
                        $errorCount++;
                        continue;
                    }

                    // CSVの列: "id","call_list_id"(旧),"call_status_id","call_memo","called_at","created_by_user_id"(旧),"created_at"
                    $oldHistoryId = trim($row[0] ?? '');
                    $oldCallListId = trim($row[1] ?? '');
                    $csvCallStatusId = trim($row[2] ?? '');
                    $csvCallMemo = $this->sanitizeStringOrNull($row[3]);
                    $csvCalledAt = $this->sanitizeStringOrNull($row[4]);
                    // $csvOldCreatedByUserId = $this->sanitizeStringOrNull($row[5]); // 今回は使わない
                    $csvCreatedAt = $this->sanitizeStringOrNull($row[6]);

                    // 1. 新しい CallList ID を検索 (old_id を使用)
                    $newCallList = CallList::where('old_id', $oldCallListId)
                                           ->where('user_id', $targetUserId) // 同じユーザーに紐づく CallList を対象
                                           ->first();

                    if (!$newCallList) {
                        $errors[] = $lineNumber . '行目 (旧CallListID: ' . $oldCallListId . '): 対応する新しいCallListが見つからないためスキップ。';
                        $skippedNoCallListCount++;
                        continue;
                    }

                    $data = [
                        'call_list_id'       => $newCallList->id, // ★ 新しい CallList の ID
                        'call_status_id'     => ctype_digit($csvCallStatusId) ? (int)$csvCallStatusId : null,
                        'call_memo'          => $csvCallMemo,
                        'called_at'          => $csvCalledAt ? Carbon::parse($csvCalledAt)->toDateTimeString() : now()->toDateTimeString(),
                        'created_by_user_id' => $targetUserId, // ★ CallList と同じユーザーIDを設定
                        'created_at'         => $csvCreatedAt ? Carbon::parse($csvCreatedAt)->toDateTimeString() : now()->toDateTimeString(),
                        // updated_at はEloquentが自動設定
                    ];

                    // 簡単なバリデーション
                    $validator = Validator::make($data, [
                        'call_list_id'       => 'required|integer|exists:call_list,id', // ★ call_list (単数形) を参照
                        'call_status_id'     => 'required|integer|exists:call_status_master,id',
                        'call_memo'          => 'nullable|string|max:10000', // メモは長くなる可能性があるのでmaxを調整
                        'called_at'          => 'required|date',
                        'created_by_user_id' => 'required|integer|exists:users,id',
                        'created_at'         => 'required|date',
                    ]);

                    if ($validator->fails()) {
                        $errors[] = $lineNumber . '行目 (旧HistoryID: ' . $oldHistoryId . ', 旧CallListID: ' . $oldCallListId . '): ' . implode(', ', $validator->errors()->all());
                        $errorCount++;
                        continue;
                    }

                    CallHistory::create($data);
                    $importedCount++;
                    if ($importedCount % 100 == 0) {
                        $this->info($importedCount . '件処理完了...');
                    }
                } // while end
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Old CallHistory Import Error at line ' . $lineNumber . ': ' . $e->getMessage(), $data ?? []);
                $this->error('エラーが発生しました (行: ' . $lineNumber . '): ' . $e->getMessage());
                if (!empty($errors)) {
                    $this->line('これまでのバリデーションエラー/スキップ詳細:');
                    foreach ($errors as $err) { $this->line('- ' . $err); }
                }
                return Command::FAILURE;
            }
            fclose($handle);

            $this->info('------------------------------------');
            $this->info('データ移行処理が完了しました。');
            $this->info($importedCount . '件の架電履歴をインポートしました。');
            if ($skippedNoCallListCount > 0) {
                $this->warn($skippedNoCallListCount . '件は対応するCallListが見つからずスキップしました。');
            }
            if ($errorCount > 0) {
                $this->warn($errorCount . '件のバリデーションエラーがありました。詳細は以下の通りです:');
                foreach ($errors as $err) {
                    $this->line('- ' . $err);
                }
            }
             if ($importedCount === 0 && $errorCount === 0 && $skippedNoCallListCount === 0 && $lineNumber > 1) {
                 $this->info('インポート対象の新しいデータはありませんでした。');
            }


        } else {
            $this->error('CSVファイルを開けませんでした: ' . $fullFilepath);
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}