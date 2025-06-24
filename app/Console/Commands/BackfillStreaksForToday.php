<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CallList;
use App\Models\CallHistory;
use App\Models\CallListStreak;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillStreaksForToday extends Command
{
    protected $signature = 'backfill:streaks-for-today';
    protected $description = 'Calculates and populates the current consecutive missed call streaks for lists that were called today.';
    private const MISSED_CALL_STATUS_ID = 2; // 「不在着信」のID

    public function handle(): int
    {
        $this->info('本日架電されたリストの連続不在回数を計算し、初期設定します...');

        // 本日、何らかの架電履歴がある call_list_id を重複なく取得
        $callListIdsCalledToday = CallHistory::whereDate('called_at', Carbon::yesterday())->distinct()->pluck('call_list_id');

        if ($callListIdsCalledToday->isEmpty()) {
            $this->info('本日架電されたリストはありません。処理を終了します。');
            return Command::SUCCESS;
        }
        $this->info($callListIdsCalledToday->count() . '件のリストを処理します...');
        
        $progressBar = $this->output->createProgressBar($callListIdsCalledToday->count());
        $progressBar->start();

        DB::beginTransaction();
        try {
            foreach ($callListIdsCalledToday as $listId) {
                // 各リストの全履歴を新しい順に取得
                $histories = CallHistory::where('call_list_id', $listId)->orderBy('called_at', 'desc')->orderBy('id', 'desc')->get();
                
                $consecutiveMissedCalls = 0;
                // 最新の履歴から遡って、連続する「不在着信」をカウント
                foreach ($histories as $history) {
                    if ($history->call_status_id === self::MISSED_CALL_STATUS_ID) {
                        $consecutiveMissedCalls++;
                    } else {
                        // 「不在着信」以外のステータスに当たったら、連続は途切れるのでループを抜ける
                        break;
                    }
                }

                // 計算結果を call_list_streaks テーブルに保存（あれば更新、なければ作成）
                CallListStreak::updateOrCreate(
                    ['call_list_id' => $listId],
                    ['consecutive_missed_calls' => $consecutiveMissedCalls]
                );
                $progressBar->advance();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BackfillStreaksForToday Error: ' . $e->getMessage());
            $this->error("\nエラーが発生しました: " . $e->getMessage());
            return Command::FAILURE;
        }

        $progressBar->finish();
        $this->info("\n処理が完了しました。");
        return Command::SUCCESS;
    }
}