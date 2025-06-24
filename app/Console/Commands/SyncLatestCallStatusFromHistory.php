<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CallList;
use App\Models\CallHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncLatestCallStatusFromHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:latest-call-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates latest_call_status_id and latest_call_memo in call_list based on the newest call_history entry for each call list.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('架電リストの最新状況を架電履歴から同期する処理を開始します...');

        // CallListをチャンクで処理してメモリ負荷を軽減
        $chunkSize = 200; // 一度に処理する件数 (環境に合わせて調整)
        $updatedCount = 0;
        $notFoundCount = 0;

        DB::beginTransaction();
        try {
            CallList::with('callHistories') // Eager loadはここでは効果が薄いかもだが念のため
                ->orderBy('id') // 処理順序を一定にするため
                ->chunkById($chunkSize, function ($callLists) use (&$updatedCount, &$notFoundCount) {
                    foreach ($callLists as $callList) {
                        // 各CallListに対して最新のCallHistoryを取得
                        // called_at で降順、同じ日時なら id で降順 (より新しいものが先頭)
                        $latestHistory = $callList->callHistories()
                                                  ->orderBy('called_at', 'desc')
                                                  ->orderBy('id', 'desc')
                                                  ->first();

                        if ($latestHistory) {
                            // 最新履歴が見つかった場合、CallListの情報を更新
                            // 変更があった場合のみ更新する
                            if ($callList->latest_call_status_id != $latestHistory->call_status_id ||
                                $callList->latest_call_memo != $latestHistory->call_memo) {

                                $callList->latest_call_status_id = $latestHistory->call_status_id;
                                $callList->latest_call_memo = $latestHistory->call_memo;
                                // updated_at も更新されるように save() を使うか、明示的に
                                $callList->touch(); // これでupdated_atが更新される
                                $callList->save();
                                $updatedCount++;
                                $this->line("CallList ID {$callList->id}: 最新状況を更新しました (Status ID: {$latestHistory->call_status_id})");
                            }
                        } else {
                            // 該当するCallListに履歴が1件もない場合
                            // 必要であれば、ここでlatest_call_status_idをデフォルト値（例：未対応のID）に設定するなどの処理も可能
                            // 今回は履歴がない場合は何もしない
                            $notFoundCount++;
                             // $this->line("CallList ID {$callList->id}: 関連する架電履歴が見つかりませんでした。");
                        }
                    }
                    $this->info($updatedCount . '件のCallListを更新し、' . $notFoundCount . '件は履歴なし。現在処理中のID範囲: ' . $callLists->first()->id . ' - ' . $callLists->last()->id);
                });

            DB::commit();
            $this->info('------------------------------------');
            $this->info('架電リストの最新状況の同期処理が完了しました。');
            $this->info($updatedCount . '件の架電リストの最新状況が更新されました。');
            if ($notFoundCount > 0) {
                $this->info($notFoundCount . '件の架電リストには関連する履歴がありませんでした。');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SyncLatestCallStatusFromHistory Error: ' . $e->getMessage());
            $this->error('エラーが発生しました: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}