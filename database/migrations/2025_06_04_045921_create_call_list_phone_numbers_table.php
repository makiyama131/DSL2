<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('call_list_phone_numbers', function (Blueprint $table) {
            $table->id(); // 新しいシステムでの電話番号レコード自体のID
            $table->foreignId('call_list_id')->constrained('call_list')->cascadeOnDelete(); // 新しいcall_listテーブルのIDを参照
            $table->string('phone_type', 50)->nullable()->comment('電話種別 (例: 固定, 携帯, FAX)');
            $table->string('phone_number', 50)->comment('電話番号');
            $table->bigInteger('old_id')->nullable()->unique()->comment('移行元CompanyPhoneNumbersのID (移行時参照用)');
            $table->timestamps(); // created_at, updated_at

            $table->index('phone_number');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('call_list_phone_numbers');
    }
};