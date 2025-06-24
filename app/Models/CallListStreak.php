<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallListStreak extends Model
{
    use HasFactory;
    protected $table = 'call_list_streaks';
    protected $primaryKey = 'call_list_id'; // 主キーを指定
    public $incrementing = false; // 主キーが自動増分ではないことを指定

    protected $fillable = [
        'call_list_id',
        'consecutive_missed_calls',
    ];

    public function callList(): BelongsTo
    {
        return $this->belongsTo(CallList::class);
    }
}