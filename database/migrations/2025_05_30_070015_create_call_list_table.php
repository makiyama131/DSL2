<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_list', function (Blueprint $table) {
            $table->id(); // 顧客ID
            $table->string('company_name');
            $table->string('address')->nullable();
            $table->string('phone_number', 50)->nullable()->comment('主たる固定電話');
            $table->string('mobile_phone_number', 50)->nullable()->comment('主たる携帯電話');
            $table->string('mobile_phone_owner', 100)->nullable();
            $table->string('representative_name', 100)->nullable();

            $table->foreignId('latest_call_status_id')->nullable()->constrained('call_status_master')->onDelete('set null')->onUpdate('cascade');
            // ->constrained() は Laravel 8以降で外部キー制約を簡潔に書けるものです。
            // 'call_status_master' は参照先のテーブル名。
            // ->onDelete('set null') は、参照先のステータスが削除されたら、このカラムをNULLにする設定です。
            // (要件定義では RESTRICT でしたが、マスタデータが削除されることは稀で、
            //  もし削除されても架電リストのデータは残したい場合 set null が適しています。RESTRICT のままでも良いです。)

            $table->text('latest_call_memo')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('email')->nullable();
            $table->string('website_url')->nullable();
            $table->string('source_of_data', 100)->nullable()->comment('データ取得元');
            $table->text('company_remarks')->nullable()->comment('会社備考');
            $table->string('fax_number', 50)->nullable()->comment('FAX番号');
            // --- 既存システムとの連携用カラム (前回のディスカッションより) ---
            $table->foreignId('contracted_company_id')->nullable()->constrained('companies')->onDelete('set null')->onUpdate('cascade')->comment('契約に至った場合のcompaniesテーブルID');
            // --------------------------------------------------------------
            $table->timestamps(); // created_at, updated_at
            $table->softDeletes(); // deleted_at (論理削除用)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_list');
    }
};