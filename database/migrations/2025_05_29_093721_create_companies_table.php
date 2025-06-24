<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id(); // INT, PRIMARY KEY, AUTO_INCREMENT と同じ
            $table->string('name'); // 会社名 (VARCHAR)
            $table->string('emoji_identifier')->nullable(); // 管理用の絵文字 (VARCHAR), NULLを許容
            $table->timestamps(); // created_at と updated_at カラム (TIMESTAMP) を自動作成
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};