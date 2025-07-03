<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->comment('作成者ID');
            $table->date('report_date')->comment('報告日');
            $table->string('title')->comment('タイトル');
            $table->integer('calls_count')->default(0)->comment('架電数');
            $table->integer('prospect_appointments_count')->default(0)->comment('見込みアポ数');
            $table->integer('appointments_count')->default(0)->comment('アポ数');
            $table->integer('meetings_count')->default(0)->comment('商談数');
            $table->text('reflection')->nullable()->comment('反省点・改善点');
            $table->text('memo')->nullable()->comment('メモ');
            $table->timestamps();

            $table->unique(['user_id', 'report_date']); // ユーザーと日付の組み合わせでユニーク
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};