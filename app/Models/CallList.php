<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // ソフトデリート用
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder; // ★ Builder を use に追加
use Carbon\Carbon; // ★ Carbon を use に追加
use Illuminate\Database\Eloquent\Relations\HasOne;



class CallList extends Model
{
    use HasFactory, SoftDeletes; // SoftDeletes を追加
    use HasFactory, SoftDeletes;
    

    /**
     * 電話番号で部分一致検索するローカルクエリスコープ
     * call_listテーブル本体の電話番号と、関連する複数電話番号テーブルの両方を検索対象とする
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $phoneNumber
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterByPhoneNumber(Builder $query, ?string $phoneNumber): Builder // ★ このメソッドを追加
    {
        if ($phoneNumber) {
            // 検索文字列からハイフンなどの記号を除去して、数字のみにする
            $searchDigits = preg_replace('/[^0-9]/', '', $phoneNumber);

            if (!empty($searchDigits)) {
                return $query->where(function ($q) use ($searchDigits) {
                    // call_listテーブル本体の電話番号を検索
                    $q->where('phone_number', 'like', '%' . $searchDigits . '%')
                      ->orWhere('mobile_phone_number', 'like', '%' . $searchDigits . '%')
                      // phoneNumbersリレーション先の電話番号も検索 (orWhereHas)
                      ->orWhereHas('phoneNumbers', function ($subQuery) use ($searchDigits) {
                          $subQuery->where('phone_number', 'like', '%' . $searchDigits . '%');
                      });
                });
            }
        }
        return $query;
    }

    protected $table = 'call_list';

    protected $fillable = [
        'old_id', // ★追加
        'user_id', // ★追加
        'company_name',
        'address',
        'phone_number',
        'mobile_phone_number',
        'mobile_phone_owner',
        'representative_name',
        'latest_call_status_id',
        'latest_call_memo',
        'url_instagram', // instagram_url から変更したDBカラム名
        'email',
        'url_website',   // website_url から変更したDBカラム名
        'source_of_data',
        'remarks',       // company_remarks から変更したDBカラム名
        // 'fax_number', // CSVにないのでコメントアウトまたは削除
        // 'contracted_company_id', // CSVにないのでコメントアウトまたは削除
        'url_sns_other', // CSVにないがDBにはあるのでfillableには入れておく (nullになる)
        'created_at',    // ★追加 (もしfillableで制御する場合)
        'updated_at',    // ★追加 (もしfillableで制御する場合)
        'deleted_at',    // ★追加 (ソフトデリート用だが、fillableで明示的に扱う場合)
    ];

    /**
     * 論理削除のために $dates プロパティに deleted_at を追加
     */
    protected $dates = ['deleted_at'];

    /**
     * 最新の架電状況を取得 (CallStatusMaster とのリレーション)
     */
    public function latestCallStatus(): BelongsTo
    {
        return $this->belongsTo(CallStatusMaster::class, 'latest_call_status_id');
    }

    public function phoneNumbers(): HasMany // ★ このリレーションを追加
    {
        return $this->hasMany(CallListPhoneNumber::class);
    }
    /**
     * この架電リストを作成したユーザーを取得
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この架電リストに紐づく架電履歴を取得 (CallHistory とのリレーション)
     */
    public function callHistories(): HasMany
    {
        return $this->hasMany(CallHistory::class, 'call_list_id', 'id');
    }

    /**
     * 契約に至った会社情報を取得 (Company とのリレーション)
     */
    public function contractedCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'contracted_company_id');
    }

     /**
     * 指定された期間で絞り込むローカルクエリスコープ
     * (updated_at を基準とする)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $startDate
     * @param  string|null  $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterByDateRange(Builder $query, ?string $startDate, ?string $endDate): Builder
    {
        if ($startDate && $endDate) {
            // Carbon::parse で日付文字列を Carbon インスタンスに変換
            $query->whereBetween('updated_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()]);
        } elseif ($startDate) {
            $query->where('updated_at', '>=', Carbon::parse($startDate)->startOfDay());
        } elseif ($endDate) {
            $query->where('updated_at', '<=', Carbon::parse($endDate)->endOfDay());
        }
        return $query;
    }

    /**
     * 会社名で部分一致検索する
     */
    public function scopeFilterByCompanyName(Builder $query, ?string $companyName): Builder
    {
        if ($companyName) {
            return $query->where('company_name', 'like', '%' . $companyName . '%');
        }
        return $query;
    }

    /**
     * ステータスID (複数可) で絞り込む
     */
    public function scopeFilterByStatusIds(Builder $query, ?array $statusIds): Builder
    {
        if (!empty($statusIds) && is_array($statusIds)) {
            // 配列内の空の要素やnullを除去
            $validStatusIds = array_filter($statusIds, fn($value) => !is_null($value) && $value !== '');
            if (!empty($validStatusIds)) {
                return $query->whereIn('latest_call_status_id', $validStatusIds);
            }
        }
        return $query;
    }

    public function streak(): HasOne // ★ このメソッドを追加
    {
        return $this->hasOne(CallListStreak::class);
    }

    // CompanyPhoneNumbers や CompanyAliases とのリレーションも後で追加できます
}
