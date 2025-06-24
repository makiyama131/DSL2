<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User; // Userモデルをuse
use Illuminate\Support\Facades\Hash; // パスワードハッシュ化のためにuse
use Illuminate\Support\Str; // remember_tokenのためにuse (任意)

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 既存のユーザーをクリアしたい場合はコメントを外す (開発時のみ)
        // User::truncate();

        User::create([
            'name' => '管理者 太郎',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'admin', // 管理者
            'remember_token' => Str::random(10),
        ]);

        User::create([
            'name' => '運用 花子',
            'email' => 'unyo@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'unyo', // 運用担当
            'remember_token' => Str::random(10),
        ]);

        User::create([
            'name' => '営業 次郎',
            'email' => 'eigyo@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'eigyo', // 営業担当 (デフォルト値と同じだが明示)
            'remember_token' => Str::random(10),
        ]);

        // 必要に応じてさらにユーザーを追加
        // User::factory()->count(5)->create(); // UserFactoryを使う場合
    }
}