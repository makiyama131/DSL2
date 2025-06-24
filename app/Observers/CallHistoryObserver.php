<?php

namespace App\Observers;

use App\Models\CallHistory;
use App\Models\CallListStreak; // Streakモデルをuse
use Illuminate\Support\Facades\DB; // DBファサードをuse

class CallHistoryObserver
{
    /**
     * Handle the CallHistory "created" event.
     *
     * @param  \App\Models\CallHistory  $callHistory
     * @return void
     */
    public function created(CallHistory $callHistory)
    {
        $callList = $callHistory->callList;
        if (!$callList) {
            return;
        }

        $missedCallStatusId = 2; // 「不在着信」のID

        // 該当リストのstreak記録を取得または新規作成
        $streak = CallListStreak::firstOrCreate(
            ['call_list_id' => $callList->id]
        );

        // 今回の架電ステータスが「不在着信」の場合
        if ($callHistory->call_status_id == $missedCallStatusId) {
            // 連続不在カウントをインクリメント（+1）する
            $streak->increment('consecutive_missed_calls');
        } 
        // 「不在着信」以外のステータスの場合
        else {
            // 連続不在カウントを0にリセットする
            if ($streak->consecutive_missed_calls > 0) {
                $streak->consecutive_missed_calls = 0;
                $streak->save();
            }
        }
    }
}