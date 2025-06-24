<?php

namespace App\Http\Controllers;

use App\Models\CallHistory;
use App\Models\CallStatusMaster;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
    private const APPOINTMENT_STATUS_ID = 8; // ★ 要確認: call_status_master テーブルの実際のIDに合わせる

    /**
     * 指定された期間とユーザーID(任意)の主要な架電KPIを計算する
     */
    private function _calculateKpisForPeriod(?Carbon $periodStart, ?Carbon $periodEnd, ?int $userId = null): array
    {
        try {
            $query = CallHistory::query();

            // 期間で絞り込み
            if ($periodStart && $periodEnd) {
                $query->whereBetween('called_at', [$periodStart, $periodEnd]);
            } elseif ($periodStart) {
                $query->where('called_at', '>=', $periodStart);
            } elseif ($periodEnd) {
                $query->where('called_at', '<=', $periodEnd);
            }

            // ユーザーIDで絞り込み
            if ($userId) {
                $query->where('created_by_user_id', $userId);
            }

            $totalCalls = (clone $query)->count();
            $totalAppointments = (clone $query)->where('call_status_id', self::APPOINTMENT_STATUS_ID)->count();
            $appointmentRate = ($totalCalls > 0) ? round(($totalAppointments / $totalCalls) * 100, 2) : 0;

            Log::debug("[_calculateKpisForPeriod] UserID: " . ($userId ?? 'All') . ", Period: " . 
                ($periodStart ? $periodStart->toDateTimeString() : 'N/A') . " to " . 
                ($periodEnd ? $periodEnd->toDateTimeString() : 'N/A') . 
                ", Calls: $totalCalls, Appts: $totalAppointments, Rate: $appointmentRate%");

            return [
                'totalCalls' => $totalCalls,
                'totalAppointments' => $totalAppointments,
                'appointmentRate' => $appointmentRate,
            ];
        } catch (\Exception $e) {
            Log::error("[_calculateKpisForPeriod] Error: " . $e->getMessage(), [
                'userId' => $userId,
                'periodStart' => $periodStart ? $periodStart->toDateTimeString() : null,
                'periodEnd' => $periodEnd ? $periodEnd->toDateTimeString() : null,
            ]);
            return ['totalCalls' => 0, 'totalAppointments' => 0, 'appointmentRate' => 0];
        }
    }

    /**
     * 固定期間 (今日、今週、今月) のKPIデータを取得する
     */
    private function _getFixedPeriodKpis(?int $userId = null): array
    {
        $now = Carbon::now();
        $todayKpis = $this->_calculateKpisForPeriod($now->copy()->startOfDay(), $now->copy()->endOfDay(), $userId);
        $thisWeekKpis = $this->_calculateKpisForPeriod($now->copy()->startOfWeek(CarbonInterface::MONDAY), $now->copy()->endOfDay(), $userId);
        $thisMonthKpis = $this->_calculateKpisForPeriod($now->copy()->startOfMonth(), $now->copy()->endOfDay(), $userId);

        return compact('todayKpis', 'thisWeekKpis', 'thisMonthKpis');
    }

    /**
     * 担当者別のKPIデータを取得する (営業ロールのユーザー対象)
     */
    private function _getPerUserKpis(?Carbon $periodStart, ?Carbon $periodEnd): \Illuminate\Support\Collection
    {
        try {
            Log::info("[AnalyticsController_getPerUserKpis] Starting. Period: " . 
                ($periodStart ? $periodStart->toDateString() : 'Any') . " to " . 
                ($periodEnd ? $periodEnd->toDateString() : 'Any'));

            // ★ ロール名を要確認: 実際のDBのrole値に合わせる (例: 'sales', 'Eigyo' など)
            $salesUsers = User::where('role', 'eigyo')->orderBy('name')->get(['id', 'name']);
            Log::info("[AnalyticsController_getPerUserKpis] Found " . $salesUsers->count() . " users with role 'eigyo'.", 
                ['users' => $salesUsers->pluck('name', 'id')->toArray()]);

            if ($salesUsers->isEmpty()) {
                Log::warning("[AnalyticsController_getPerUserKpis] No users with role 'eigyo' found. Returning empty collection.");
                return collect([]);
            }

            // デバッグ: call_history テーブルのデータ存在確認
            $callHistoryCheck = CallHistory::select('created_by_user_id')
                ->groupBy('created_by_user_id')
                ->pluck('created_by_user_id')
                ->toArray();
            Log::debug("[AnalyticsController_getPerUserKpis] Users found in call_history: ", $callHistoryCheck);

            $perUserKpisData = [];
            foreach ($salesUsers as $user) {
                $kpis = $this->_calculateKpisForPeriod($periodStart, $periodEnd, $user->id);
                $perUserKpisData[] = (object) [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'total_calls' => $kpis['totalCalls'],
                    'total_appointments' => $kpis['totalAppointments'],
                    'appointment_rate' => $kpis['appointmentRate'],
                ];
                Log::info("[AnalyticsController_getPerUserKpis] KPIs for User ID {$user->id} ({$user->name}): " .
                    "Calls={$kpis['totalCalls']}, Appts={$kpis['totalAppointments']}, Rate={$kpis['appointmentRate']}%");
            }

            $finalCollection = collect($perUserKpisData);
            Log::info("[AnalyticsController_getPerUserKpis] Finished. Calculated perUserKpis collection count: " . $finalCollection->count());
            if ($finalCollection->isNotEmpty()) {
                Log::info("[AnalyticsController_getPerUserKpis] First user KPI data in collection: ", (array) $finalCollection->first());
            }

            return $finalCollection;
        } catch (\Exception $e) {
            Log::error("[AnalyticsController_getPerUserKpis] Error: " . $e->getMessage(), [
                'periodStart' => $periodStart ? $periodStart->toDateTimeString() : null,
                'periodEnd' => $periodEnd ? $periodEnd->toDateTimeString() : null,
            ]);
            return collect([]);
        }
    }

    /**
     * 指定された期間の架電状況の内訳データを取得する (円グラフ用)
     */
    private function _getStatusBreakdownChartData(?Carbon $periodStart, ?Carbon $periodEnd, ?int $userId = null): array
    {
        try {
            $statusBreakdownQuery = CallHistory::query()
                ->join('call_status_master', 'call_history.call_status_id', '=', 'call_status_master.id')
                ->select('call_status_master.status_name', DB::raw('count(call_history.id) as total'))
                ->groupBy('call_history.call_status_id', 'call_status_master.status_name', 'call_status_master.sort_order')
                ->orderBy('call_status_master.sort_order', 'asc');

            if ($periodStart && $periodEnd) {
                $statusBreakdownQuery->whereBetween('call_history.called_at', [$periodStart, $periodEnd]);
            } elseif ($periodStart) {
                $statusBreakdownQuery->where('call_history.called_at', '>=', $periodStart);
            } elseif ($periodEnd) {
                $statusBreakdownQuery->where('call_history.called_at', '<=', $periodEnd);
            }

            if ($userId) {
                $statusBreakdownQuery->where('call_history.created_by_user_id', $userId);
            }

            $statusCounts = $statusBreakdownQuery->get();
            Log::debug("[_getStatusBreakdownChartData] Status breakdown: ", $statusCounts->toArray());

            $labels = $statusCounts->pluck('status_name')->all();
            $data = $statusCounts->pluck('total')->all();
            $baseColors = [
                'rgba(255, 99, 132, 0.7)', 'rgba(54, 162, 235, 0.7)', 'rgba(255, 206, 86, 0.7)',
                'rgba(75, 192, 192, 0.7)', 'rgba(153, 102, 255, 0.7)', 'rgba(255, 159, 64, 0.7)',
                'rgba(120, 180, 100, 0.7)', 'rgba(230, 130, 230, 0.7)', 'rgba(70, 130, 180, 0.7)',
                'rgba(255, 120, 70, 0.7)', 'rgba(128, 128, 128, 0.7)', 'rgba(0, 128, 128, 0.7)'
            ];
            $colors = [];
            for ($i = 0; $i < count($data); $i++) {
                $colors[] = $baseColors[$i % count($baseColors)];
            }

            return ['labels' => $labels, 'data' => $data, 'colors' => $colors];
        } catch (\Exception $e) {
            Log::error("[_getStatusBreakdownChartData] Error: " . $e->getMessage(), [
                'periodStart' => $periodStart ? $periodStart->toDateTimeString() : null,
                'periodEnd' => $periodEnd ? $periodEnd->toDateTimeString() : null,
                'userId' => $userId,
            ]);
            return ['labels' => [], 'data' => [], 'colors' => []];
        }
    }

    /**
     * 架電状況アナリティクス画面を表示する
     */
    public function callStatusAnalytics(Request $request)
    {
        try {
            // ログインしているユーザーのIDを取得
            $currentUserId = Auth::id();
            if (!$currentUserId) {
                Log::warning("[callStatusAnalytics] No authenticated user found.");
                return back()->with('error', 'ログインが必要です。');
            }

            // 期間フィルターの処理
            $validatedData = $request->validate([
                'start_date' => 'nullable|date|date_format:Y-m-d|before_or_equal:end_date',
                'end_date'   => 'nullable|date|date_format:Y-m-d|after_or_equal:start_date',
            ]);

            $viewStartDateStr = $validatedData['start_date'] ?? null;
            $viewEndDateStr = $validatedData['end_date'] ?? null;

            $filterStartDateForCalc = $viewStartDateStr ? Carbon::parse($viewStartDateStr)->startOfDay() : null;
            $filterEndDateForCalc = $viewEndDateStr ? Carbon::parse($viewEndDateStr)->endOfDay() : null;

            // ログイン中のユーザーのKPIデータを取得
            $selectedPeriodKpis = $this->_calculateKpisForPeriod($filterStartDateForCalc, $filterEndDateForCalc, $currentUserId);
            $fixedPeriodKpis = $this->_getFixedPeriodKpis($currentUserId);
            $perUserKpis = $this->_getPerUserKpis($filterStartDateForCalc, $filterEndDateForCalc);
            $statusBreakdownChartData = $this->_getStatusBreakdownChartData($filterStartDateForCalc, $filterEndDateForCalc, $currentUserId);

            $chartData = [
                'status_breakdown' => $statusBreakdownChartData,
                'activity_trend'  => ['labels' => [], 'data' => []],
                'hourly_analysis' => ['labels' => [], 'data' => []],
                'weekly_analysis' => ['labels' => [], 'data' => []],
            ];

            // デバッグ用ログ
            Log::info("[callStatusAnalytics] Rendering view for user ID: " . $currentUserId, [
                'startDate' => $viewStartDateStr,
                'endDate' => $viewEndDateStr,
                'selectedPeriodKpis' => $selectedPeriodKpis,
                'perUserKpisCount' => $perUserKpis->count(),
            ]);

            return view('analytics.call_status', [
                'startDate'          => $viewStartDateStr,
                'endDate'            => $viewEndDateStr,
                'selectedPeriodKpis' => $selectedPeriodKpis,
                'todayKpis'          => $fixedPeriodKpis['todayKpis'],
                'thisWeekKpis'       => $fixedPeriodKpis['thisWeekKpis'],
                'thisMonthKpis'      => $fixedPeriodKpis['thisMonthKpis'],
                'perUserKpis'        => $perUserKpis,
                'chartData'          => $chartData,
                'currentUser'        => Auth::user(), // ログイン中のユーザー情報をビューに渡す
            ]);
        } catch (\Exception $e) {
            Log::error("[callStatusAnalytics] Error: " . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'データの取得に失敗しました。管理者にお問い合わせください。');
        }
    }
}