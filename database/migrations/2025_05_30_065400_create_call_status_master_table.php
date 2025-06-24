<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_status_master', function (Blueprint $table) {
            $table->id(); // 状況ID (INT AUTO_INCREMENT PRIMARY KEY)
            $table->string('status_name', 100)->unique(); // 状況名 (VARCHAR(100) NOT NULL UNIQUE)
            $table->integer('sort_order')->default(0); // 表示順 (INT DEFAULT 0)
            $table->integer('usage_count')->default(0); // このステータスの利用回数 (INT DEFAULT 0)
            $table->timestamps(); //作成日時 (created_at), 最終更新日時 (updated_at)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_status_master');
    }
};