<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call_list', function (Blueprint $table) {
            // usersテーブルのidを参照する外部キー。リスト作成者を想定。
            // nullableにしておくと、何らかの理由でユーザーが削除された場合もリストは残せる（onDelete('set null')）。
            // あるいは、ユーザーが削除されたらリストも一緒に削除するなら onDelete('cascade')。
            // 今回は、リスト作成者は必須として、もしユーザーが削除されるケースを考えるなら SET NULL が無難かもしれません。
            // nullable() にしない場合は、必ずユーザーIDが設定されるようにします。
            $table->foreignId('user_id')->after('id')->constrained('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('call_list', function (Blueprint $table) {
            // 外部キー制約を先に削除してからカラムを削除
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};