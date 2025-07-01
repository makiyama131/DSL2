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
    Schema::table('call_list', function (Blueprint $table) {
        $table->json('simple_tags')->nullable()->after('latest_call_memo')->comment('シンプルメモタグ');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_list', function (Blueprint $table) {
            //
        });
    }
};
