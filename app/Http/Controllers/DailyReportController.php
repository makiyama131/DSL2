<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\DailyReport;
use App\Models\ActivityLog;
use App\Models\Meeting;
use App\Models\CallHistory;
use Illuminate\Support\Facades\DB;

class DailyReportController extends Controller
{
    /**
     * 日報作成画面を表示します。
     */
    public function create()
    {
        $today = Carbon::today();
        $user = Auth::user();

        // 今日の架電数を取得
        $callsCount = CallHistory::where('created_by_user_id', $user->id)
            ->whereDate('called_at', $today)
            ->count();
            
        // 今日のアポイント情報を取得 (アポイントのステータスIDを8と仮定)
        $appointments = CallHistory::with('callList')
            ->where('created_by_user_id', $user->id)
            ->where('call_status_id', 8) // 'アポイント'のID
            ->whereDate('called_at', $today)
            ->get();
            
        $appointmentsCount = $appointments->count();
        $appointmentCompanies = $appointments->map(function ($appointment) {
            return $appointment->callList->company_name ?? '不明';
        })->unique()->implode(', ');

        return view('daily_reports.create', compact(
            'today',
            'callsCount',
            'appointmentsCount',
            'appointmentCompanies'
        ));
    }

    /**
     * 新しい日報を保存します。
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'report_date' => 'required|date',
            'calls_count' => 'required|integer',
            'prospect_appointments_count' => 'required|integer',
            'appointments_count' => 'required|integer',
            'meetings_count' => 'required|integer',
            'reflection' => 'nullable|string',
            'memo' => 'nullable|string',
            'next_action' => 'nullable|string', // 👈 バリデーションルールを追加
            'activities' => 'required|array|min:1',
            'activities.*.start_time' => 'required',
            'activities.*.end_time' => 'required',
            'activities.*.activity_content' => 'required|string|max:255',
            'meetings' => 'nullable|array',
            'meetings.*.company_name' => 'required_with:meetings|string|max:255',
            'meetings.*.attendees' => 'nullable|string',
            'meetings.*.summary' => 'required_with:meetings|string',
        ]);

        DB::transaction(function () use ($request, $validated) {
            $dailyReport = DailyReport::create([
                'user_id' => Auth::id(),
                'report_date' => $validated['report_date'],
                'title' => $validated['title'],
                'calls_count' => $validated['calls_count'],
                'prospect_appointments_count' => $validated['prospect_appointments_count'],
                'appointments_count' => $validated['appointments_count'],
                'meetings_count' => $validated['meetings_count'],
                'reflection' => $validated['reflection'],
                'memo' => $validated['memo'],
                'next_action' => $validated['next_action'], // 👈 保存処理を追加

            ]);

            foreach ($validated['activities'] as $activity) {
                $dailyReport->activityLogs()->create($activity);
            }

            if (isset($validated['meetings'])) {
                foreach ($validated['meetings'] as $meeting) {
                    $dailyReport->meetings()->create($meeting);
                }
            }
        });

        return redirect()->route('dashboard')->with('success', '日報を登録しました。');
    }
}