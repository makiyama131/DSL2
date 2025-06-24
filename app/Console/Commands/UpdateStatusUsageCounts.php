<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CallStatusMaster;
use App\Models\CallHistory;
use Illuminate\Support\Facades\DB; // トランザクションやRawクエリ用 (今回はEloquentのみでも可)

class UpdateStatusUsageCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:status-usage-counts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the usage_count for each status in call_status_master based on call_history records.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('架電状況マスタの利用回数 (usage_count) の更新を開始します...');

        $statuses = CallStatusMaster::all();
        $updatedCount = 0;

        if ($statuses->isEmpty()) {
            $this->info('更新対象の架電状況マスタデータがありません。');
            return Command::SUCCESS;
        }

        DB::beginTransaction();
        try {
            foreach ($statuses as $status) {
                $count = CallHistory::where('call_status_id', $status->id)->count();

                // 更新前の値と比較して、変更があった場合のみ保存＆ログ出力 (任意)
                if ($status->usage_count !== $count) {
                    $this->line("ステータス名: {$status->status_name} (ID: {$status->id}) - 旧利用回数: {$status->usage_count} -> 新利用回数: {$count}");
                    $status->usage_count = $count;
                    $status->save();
                    $updatedCount++;
                } else {
                    $this->line("ステータス名: {$status->status_name} (ID: {$status->id}) - 利用回数: {$count} (変更なし)");
                }
            }
            DB::commit();
            $this->info('------------------------------------');
            $this->info('利用回数の更新が完了しました。');
            $this->info($updatedCount . '件のステータスの利用回数が更新されました。');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('エラーが発生しました: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}