<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CallStatusMaster;
use Illuminate\Support\Facades\DB; // 

class ImportStatusMasterData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-status-master-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
public function handle()
{
    $this->info('CallStatusMaster のデータ移行を開始します...');
    $filePath = storage_path('app/import/status_master_data.csv'); // CSVの置き場所の例

    if (!file_exists($filePath)) {
        $this->error('CSVファイルが見つかりません: ' . $filePath);
        return 1;
    }

    DB::beginTransaction();
    try {
        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            fgetcsv($handle); // ヘッダー行をスキップ

            while (($data = fgetcsv($handle)) !== FALSE) {
                CallStatusMaster::updateOrCreate(
                    ['id' => $data[0]], // IDで検索
                    [
                        'status_name' => $data[1],
                        'sort_order' => (int)$data[2],
                        // 'usage_count' => (int)$data[3], // usage_countも移行する場合
                        'created_at' => $data[4] ?: now(), // 日付がない場合は現在時刻
                        'updated_at' => $data[5] ?: now(),
                    ]
                );
            }
            fclose($handle);
        }
        DB::commit();
        $this->info('CallStatusMaster のデータ移行が完了しました。');
    } catch (\Exception $e) {
        DB::rollBack();
        $this->error('エラーが発生しました: ' . $e->getMessage());
        return 1;
    }
    return 0;
}
}
