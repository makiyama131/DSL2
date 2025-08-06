<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CallList;
use App\Models\User; // ★ Userモデルをuseに追加
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ExportCallSummary extends Command
{
    /**
     * The name and signature of the console command.
     * {userId?} の '?' は、この引数がオプション（任意）であることを示します
     * @var string
     */
    protected $signature = 'export:call-summary {userId?}'; // ★★★ 引数を追加 ★★★

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports a summary of call lists. Optionally filters by a specific user ID.'; // ★ 説明を更新

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // ★★★ 引数からユーザーIDを取得 ★★★
        $userId = $this->argument('userId');
        $user = null;

        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("指定されたユーザーID {$userId} が見つかりません。");
                return Command::FAILURE;
            }
            $this->info("ユーザー「{$user->name}」(ID: {$userId}) のデータに絞り込んでエクスポートを開始します...");
        } else {
            $this->info('全ユーザーの架電リストのサマリーデータのエクスポートを開始します...');
        }

        try {
            // ★★★ ファイル名にユーザー識別子を追加 ★★★
            $userIdentifier = $userId ? "_user_{$userId}" : "_all_users";
            $timestamp = Carbon::now()->format('Ymd_His');
            $filename = "call_summary{$userIdentifier}_{$timestamp}.csv";
            $directory = 'exports';
            $filepath = "{$directory}/{$filename}";

            Storage::disk('local')->makeDirectory($directory);
            $handle = fopen(storage_path("app/{$filepath}"), 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            $header = [
                'リストID', '会社名', '主電話番号(固定)', '主電話番号(携帯)', '代表者名', 'メールアドレス',
                '最新の架電状況', '最新のメモ', '総架電回数', '最終更新日', '担当者名'
            ];
            fputcsv($handle, $header);

            $processedCount = 0;

            // ★★★ クエリの開始部分を修正 ★★★
            $query = CallList::query()->with(['latestCallStatus', 'user']) // userリレーションも読み込む
                                      ->withCount('callHistories');

            // もしユーザーIDが指定されていれば、クエリを絞り込む
            if ($userId) {
                $query->where('user_id', $userId);
            }

            $query->orderBy('id')->chunkById(200, function ($callLists) use ($handle, &$processedCount) {
                foreach ($callLists as $callList) {
                    $row = [
                        $callList->id,
                        $callList->company_name,
                        $callList->phone_number,
                        $callList->mobile_phone_number,
                        $callList->representative_name,
                        $callList->email, // ★ メールアドレスデータを追加
                        $callList->latestCallStatus->status_name ?? '未設定',
                        $callList->latest_call_memo,
                        $callList->call_histories_count,
                        $callList->updated_at->format('Y-m-d H:i:s'),
                        $callList->user->name ?? '不明', // ★ 担当者名を追加
                    ];
                    fputcsv($handle, $row);
                }
                $processedCount += $callLists->count();
                $this->info("{$processedCount}件処理完了...");
            });

            fclose($handle);

            $this->info('------------------------------------');
            $this->info('エクスポートが正常に完了しました。');
            $this->info("ファイルパス: storage/app/{$filepath}");

        } catch (\Exception $e) {
            $this->error('エクスポート処理中にエラーが発生しました: ' . $e->getMessage());
            Log::error('Call Summary Export Failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

// php artisan export:call-summary 7