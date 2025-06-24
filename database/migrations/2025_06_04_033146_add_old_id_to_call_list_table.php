<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('call_list', function (Blueprint $table) {
            $table->bigInteger('old_id')->nullable()->after('id')->comment('移行元システムでのID');
            // もし旧IDでインデックスを貼りたい場合は追加
            // $table->index('old_id');
        });
    }
    public function down(): void {
        Schema::table('call_list', function (Blueprint $table) {
            $table->dropColumn('old_id');
        });
    }
};