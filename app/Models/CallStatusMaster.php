<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallStatusMaster extends Model
{
    use HasFactory;

    protected $table = 'call_status_master'; // テーブル名を明示的に指定

    protected $fillable = [
        'status_name',
        'sort_order',
        'usage_count', // usage_count はトリガーで更新する想定なので、fillable に含めるかは要検討
    ];

    // usage_count はトリガーで管理するなら guarded に含めるか、
    // もしくは fillable から外すのが良いかもしれません。
    // 今回は一旦含めておきます。
}