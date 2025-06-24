<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallHistory extends Model
{
    use HasFactory;

    protected $table = 'call_history';


    /**
     * created_at 以外のタイムスタンプを無効にする場合 (updated_at が不要な場合)
     * public $timestamps = ['created_at'];
     * もしくは、マイグレーションで $table->timestamps() の代わりに $table->timestamp('created_at')->useCurrent(); とし、
     * このモデルで const UPDATED_AT = null; とすることもできます。
     * 今回は created_at のみマイグレーションで定義し、updated_at は定義していません。
     * Eloquentはデフォルトで updated_at を探すので、不要なら以下のようにします。
     */
    const UPDATED_AT = null;


    protected $fillable = [
        'call_list_id',
        'call_status_id',
        'call_memo',
        'called_at',
        'created_by_user_id',
    ];

    protected $casts = [
        'called_at' => 'datetime', // called_at を Carbon インスタンスとして扱う
    ];

    /**
     * この履歴が属する架電リスト情報を取得
     */
    public function callList(): BelongsTo
    {
        return $this->belongsTo(CallList::class, 'call_list_id', 'id');
        
    }

    /**
     * この履歴の架電状況を取得
     */
    public function callStatus(): BelongsTo
    {
        return $this->belongsTo(CallStatusMaster::class, 'call_status_id');
    }

    /**
     * この履歴を記録したユーザーを取得
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

        public function user()
    {
        // 'created_by_user_id' は call_history テーブルのユーザーIDを指す外部キー
        // User モデルの主キーは 'id' (デフォルト)
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * この架電履歴のステータスを取得 (CallStatusMasterモデルとのリレーション)
     */
    public function status() // リレーション名は 'status' とします
    {
        // 'call_status_id' は call_history テーブルのステータスIDを指す外部キー
        // CallStatusMaster モデルの主キーは 'id' (デフォルト)
        return $this->belongsTo(CallStatusMaster::class, 'call_status_id');
    }

    /**
     * この架電履歴が属する架電リストを取得 (CallListモデルとのリレーション)
     */
}