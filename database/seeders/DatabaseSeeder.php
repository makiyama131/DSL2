<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create(); // 例えばUserのファクトリー呼び出しなど

        $this->call([
            CallStatusMasterSeeder::class, // ★ 作成したシーダーをここに追加
            
            // 他のシーダーがあればここに追加
        ]);
    }
}