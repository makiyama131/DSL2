<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_list_streaks', function (Blueprint $table) {
            // call_listテーブルのIDを主キーかつ外部キーとして設定
            $table->foreignId('call_list_id')->primary()->constrained('call_list')->cascadeOnDelete();
            $table->unsignedInteger('consecutive_missed_calls')->default(0)->comment('連続不在回数');
            $table->timestamps(); // updated_at を更新日時の管理に使用
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_list_streaks');
    }
};