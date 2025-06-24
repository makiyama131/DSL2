<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // 追加

class PerformanceData extends Model
{
    use HasFactory;

    /**
     * 複数代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [ // 追加
        'company_id',
        'date',
        'impressions',
        'ctr',
        'clicks',
        'asr',
        'application_starts',
        'completion_rate',
        'applications',
    ];

    /**
     * このパフォーマンスデータが属する会社を取得
     */
    public function company(): BelongsTo // 追加
    {
        return $this->belongsTo(Company::class);
    }
}