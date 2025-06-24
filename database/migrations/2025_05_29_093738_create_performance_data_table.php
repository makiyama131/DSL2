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
        Schema::create('performance_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade'); // 外部キー (companiesテーブルのidを参照し、会社削除時にデータも削除)
            $table->date('date'); // 日付
            $table->integer('impressions')->nullable(); // 表示回数, NULLを許容
            $table->decimal('ctr', 19, 17)->nullable(); // クリック率, 全体19桁、小数点以下17桁, NULLを許容
            $table->integer('clicks')->nullable(); // クリック数, NULLを許容
            $table->decimal('asr', 19, 17)->nullable(); // 応募開始率, NULLを許容
            $table->integer('application_starts')->nullable(); // 応募開始数, NULLを許容
            $table->decimal('completion_rate', 19, 17)->nullable(); // 応募完了率, NULLを許容
            $table->integer('applications')->nullable(); // 応募数, NULLを許容
            $table->timestamps(); // created_at と updated_at

            // 同じ会社・同じ日付のデータが重複しないようにユニーク制約を設定
            $table->unique(['company_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_data');
    }
};