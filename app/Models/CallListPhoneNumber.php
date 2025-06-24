<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallListPhoneNumber extends Model
{
    use HasFactory;
    protected $table = 'call_list_phone_numbers'; // テーブル名を明示
    protected $fillable = [
        'call_list_id',
        'phone_type',
        'phone_number',
        'old_id',
        // created_at, updated_at はEloquentが管理
    ];

    public function callList(): BelongsTo
    {
        return $this->belongsTo(CallList::class);
    }
}