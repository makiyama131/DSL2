<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // 追加

class Company extends Model
{
    use HasFactory;

    /**
     * 複数代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [ // 追加
        'name',
        'emoji_identifier',
    ];

    /**
     * 会社が持つパフォーマンスデータを取得
     */
    public function performanceData(): HasMany // 追加
    {
        return $this->hasMany(PerformanceData::class);
    }
}