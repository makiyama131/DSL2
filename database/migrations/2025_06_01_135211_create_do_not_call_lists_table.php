<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // DBファサードをuseに追加

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('do_not_call_lists', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 50)->nullable()->unique()->comment('禁止対象の電話番号');
            $table->string('company_name', 255)->nullable()->unique()->comment('禁止対象の会社名');
            $table->text('reason')->nullable()->comment('禁止理由');
            $table->text('notes')->nullable()->comment('備考');
            $table->foreignId('added_by_user_id')->nullable()->constrained('users')->nullOnDelete()->comment('追加したユーザーID');
            $table->timestamps();
        });

        // ★★★ CHECK制約の追加を Schema::create() の外に移動 ★★★
        // これにより、do_not_call_lists テーブルが作成された後に ALTER TABLE が実行される
        if (DB::getDriverName() !== 'sqlite') {
             DB::statement('ALTER TABLE do_not_call_lists ADD CONSTRAINT chk_dnc_identifier CHECK (phone_number IS NOT NULL OR company_name IS NOT NULL)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // CHECK制約をRAW SQLで追加した場合、downメソッドでの削除も考慮が必要
        // ただし、このマイグレーション全体をロールバックする場合はテーブルごと削除されるので、
        // dropIfExistsの前に明示的に制約を削除しなくても問題ないことが多いです。
        // if (DB::getDriverName() !== 'sqlite') {
        //     // DB::statement('ALTER TABLE do_not_call_lists DROP CONSTRAINT chk_dnc_identifier'); // MySQL/MariaDBの場合
        // }
        Schema::dropIfExists('do_not_call_lists');
    }
};