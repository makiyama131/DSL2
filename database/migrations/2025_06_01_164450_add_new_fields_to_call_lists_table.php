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
        Schema::table('call_list', function (Blueprint $table) { // ★ 'call_lists' から 'call_list' に変更
            $table->string('url_website')->nullable()->after('address')->comment('WEBサイトURL');
            $table->string('url_instagram')->nullable()->after('url_website')->comment('Instagram URL');
            $table->string('url_sns_other')->nullable()->after('url_instagram')->comment('その他SNS URL');
            $table->text('remarks')->nullable()->after('url_sns_other')->comment('備考');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_list', function (Blueprint $table) { // ★ 'call_lists' から 'call_list' に変更
            $table->dropColumn(['url_website', 'url_instagram', 'url_sns_other', 'remarks']);
        });
    }
};