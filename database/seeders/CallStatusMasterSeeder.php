<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CallStatusMaster; // ★ CallStatusMasterモデルをuse
use Illuminate\Support\Facades\DB;   // ★ DBファサードをuse (直接操作する場合やトランザクションで)
use Carbon\Carbon;              // ★ Carbonをuse (日時操作用)

class CallStatusMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 既存のデータをクリアする場合 (任意)
        // DB::table('call_status_master')->truncate(); // もし既存データを全削除して入れ替えたい場合

        $statuses = [
            // ユーザー様がご提示のデータに基づいて配列を作成
            ['id' => 1,  'status_name' => '未対応',           'sort_order' => 1,   'usage_count' => 0],
            ['id' => 2,  'status_name' => '不在着信',         'sort_order' => 10,  'usage_count' => 0],
            ['id' => 3,  'status_name' => '求人なし',         'sort_order' => 20,  'usage_count' => 0],
            ['id' => 4,  'status_name' => '電話番号なし',     'sort_order' => 30,  'usage_count' => 0],
            ['id' => 5,  'status_name' => 'NG',               'sort_order' => 40,  'usage_count' => 0],
            ['id' => 6,  'status_name' => '留守番（折り返し）', 'sort_order' => 50,  'usage_count' => 0],
            ['id' => 7,  'status_name' => '受付未攻略',       'sort_order' => 60,  'usage_count' => 0],
            ['id' => 8,  'status_name' => 'アポイント',         'sort_order' => 70,  'usage_count' => 0],
            ['id' => 9,  'status_name' => '再度架電',         'sort_order' => 80,  'usage_count' => 0],
            ['id' => 10, 'status_name' => '追いかけ',           'sort_order' => 90,  'usage_count' => 0],
            ['id' => 11, 'status_name' => 'お問合せ送信済み', 'sort_order' => 100, 'usage_count' => 0],
            ['id' => 12, 'status_name' => 'その他',             'sort_order' => 999, 'usage_count' => 0],
        ];

        $now = Carbon::now();

        foreach ($statuses as $status) {
            // created_at と updated_at は、もし$status配列内に具体的な日時がなければ現在時刻を使用
            $createdAt = $status['created_at'] ?? $now;
            $updatedAt = $status['updated_at'] ?? $now;

            // もしユーザー様が提示したデータに含まれていた created_at, updated_at をそのまま使いたい場合、
            // $statuses 配列にその情報を追加し、ここで参照してください。
            // 例えば、$status = ['id' => 1, 'status_name' => '未対応', ..., 'created_at' => '2025-05-30 10:20:40', 'updated_at' => '2025-05-30 10:20:40'] のように。

            CallStatusMaster::updateOrCreate(
                ['id' => $status['id']],
                [
                    'status_name' => $status['status_name'],
                    'sort_order'  => $status['sort_order'],
                    'usage_count' => $status['usage_count'],
                    'created_at'  => $createdAt,
                    'updated_at'  => $updatedAt,
                ]
            );
        }

        $this->command->info('架電状況マスタのデータ投入が完了しました。');
    }
}