<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoNotCallList extends Model
{
    use HasFactory;

    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'do_not_call_lists'; // Laravelの命名規則に従っていれば不要な場合もあるが明示

    /**
     * 大量代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'phone_number',
        'company_name',
        'reason',
        'notes',
        'added_by_user_id',
    ];

    /**
     * ネイティブなタイプへキャストする属性
     *
     * @var array<string, string>
     */
    // protected $casts = []; // 必要に応じて追加

    /**
     * この禁止リスト項目を追加したユーザー (Userモデルとのリレーション)
     */
    public function addedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by_user_id');
    }
}