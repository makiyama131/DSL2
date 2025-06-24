<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_history', function (Blueprint $table) {
            $table->id(); // 履歴ID
            $table->foreignId('call_list_id')->constrained('call_list')->onDelete('cascade'); // CallList.idへの外部キー
            $table->foreignId('call_status_id')->constrained('call_status_master')->onDelete('restrict')->onUpdate('cascade'); // CallStatusMaster.idへの外部キー
            $table->text('call_memo')->nullable();
            $table->timestamp('called_at'); // 実際に架電した日時
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->onDelete('set null')->onUpdate('cascade')->comment('記録者ID (users.id)');
            $table->timestamp('created_at')->useCurrent(); // この履歴レコードの作成日時
            // updated_at は通常不要ですが、もし履歴を編集するなら追加
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_history');
    }
};