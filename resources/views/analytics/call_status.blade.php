{{-- resources/views/analytics/call_status.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('架電状況アナリティクス') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    {{-- エラーメッセージ表示 --}}
                    @if (session('error'))
                        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-200 rounded-md">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div x-data="{
                        startDate: '{{ $startDate ?? '' }}',
                        endDate: '{{ $endDate ?? '' }}',
                        formatDate(date) {
                            const year = date.getFullYear();
                            const month = (date.getMonth() + 1).toString().padStart(2, '0');
                            const day = date.getDate().toString().padStart(2, '0');
                            return `${year}-${month}-${day}`;
                        },
                        setPresetDateRange(preset) {
                            const today = new Date();
                            let newStartDate, newEndDate;
                            switch (preset) {
                                case 'today':
                                    newStartDate = new Date(today);
                                    newEndDate = new Date(today);
                                    break;
                                case 'this_week':
                                    newStartDate = new Date(today);
                                    const currentDay = newStartDate.getDay();
                                    const diffToMonday = currentDay === 0 ? -6 : 1 - currentDay;
                                    newStartDate.setDate(newStartDate.getDate() + diffToMonday);
                                    newEndDate = new Date(newStartDate);
                                    newEndDate.setDate(newStartDate.getDate() + 6);
                                    break;
                                case 'last_week':
                                    newStartDate = new Date(today);
                                    newStartDate.setDate(today.getDate() - (today.getDay() === 0 ? 13 : today.getDay() + 6));
                                    newEndDate = new Date(newStartDate);
                                    newEndDate.setDate(newStartDate.getDate() + 6);
                                    break;
                                case 'this_month':
                                    newStartDate = new Date(today.getFullYear(), today.getMonth(), 1);
                                    newEndDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                                    break;
                                case 'last_month':
                                    newStartDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                                    newEndDate = new Date(today.getFullYear(), today.getMonth(), 0);
                                    break;
                            }
                            this.startDate = this.formatDate(newStartDate);
                            this.endDate = this.formatDate(newEndDate);
                            this.$nextTick(() => {
                                this.$refs.analyticsFilterForm.submit();
                            });
                        }
                    }">
                        <form method="GET" action="{{ route('analytics.call_status') }}" x-ref="analyticsFilterForm"
                            class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-md shadow">
                            <h3 class="text-lg font-semibold mb-3 text-gray-800 dark:text-gray-200">フィルター</h3>
                            <div class="mb-3 flex flex-wrap gap-2">
                                <button type="button" @click="setPresetDateRange('today')"
                                    class="px-3 py-1 text-xs bg-sky-500 hover:bg-sky-600 text-white rounded-md">{{ __('今日') }}</button>
                                <button type="button" @click="setPresetDateRange('this_week')"
                                    class="px-3 py-1 text-xs bg-teal-500 hover:bg-teal-600 text-white rounded-md">{{ __('今週') }}</button>
                                <button type="button" @click="setPresetDateRange('last_week')"
                                    class="px-3 py-1 text-xs bg-teal-500 hover:bg-teal-600 text-white rounded-md">{{ __('先週') }}</button>
                                <button type="button" @click="setPresetDateRange('this_month')"
                                    class="px-3 py-1 text-xs bg-cyan-500 hover:bg-cyan-600 text-white rounded-md">{{ __('今月') }}</button>
                                <button type="button" @click="setPresetDateRange('last_month')"
                                    class="px-3 py-1 text-xs bg-cyan-500 hover:bg-cyan-600 text-white rounded-md">{{ __('先月') }}</button>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 items-end">
                                <div>
                                    <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('開始日') }}</label>
                                    <input type="date" name="start_date" id="start_date" x-model="startDate"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('終了日') }}</label>
                                    <input type="date" name="end_date" id="end_date" x-model="endDate"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div class="flex space-x-2">
                                    <button type="submit"
                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                                        {{ __('適用') }}
                                    </button>
                                    <a href="{{ route('analytics.call_status') }}"
                                        class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-100 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                        {{ __('リセット') }}
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="mb-8">
                        <h3 class="text-lg font-semibold leading-6 text-gray-900 dark:text-gray-100 mb-4">
                            主要KPIサマリー (指定期間)
                            @if($startDate && $endDate)
                                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                                    ({{ \Carbon\Carbon::parse($startDate)->format('Y/m/d') }} -
                                    {{ \Carbon\Carbon::parse($endDate)->format('Y/m/d') }})
                                </span>
                            @elseif($startDate)
                                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                                    ({{ \Carbon\Carbon::parse($startDate)->format('Y/m/d') }} 以降)
                                </span>
                            @elseif($endDate)
                                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                                    ({{ \Carbon\Carbon::parse($endDate)->format('Y/m/d') }} 以前)
                                </span>
                            @else
                                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                                    (全期間)
                                </span>
                            @endif
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg shadow">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    {{ __('総架電数') }}
                                </dt>
                                <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">
                                    {{ number_format($selectedPeriodKpis['totalCalls']) }}
                                </dd>
                            </div>
                            <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg shadow">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    {{ __('総アポイント獲得数') }}
                                </dt>
                                <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">
                                    {{ number_format($selectedPeriodKpis['totalAppointments']) }}
                                </dd>
                            </div>
                            <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg shadow">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    {{ __('アポイント獲得率') }}
                                </dt>
                                <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">
                                    {{ number_format($selectedPeriodKpis['appointmentRate'], 2) }}%
                                </dd>
                            </div>
                        </div>

                        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">担当者別 詳細</h3>
                        @if(isset($perUserKpis) && $perUserKpis->count() > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                @foreach($perUserKpis as $userKpi)
                                    <div class="bg-white dark:bg-gray-800 shadow-lg rounded-xl p-6">
                                        <h4 class="text-lg font-semibold text-indigo-600 dark:text-indigo-400 mb-3">{{ $userKpi->user_name }}</h4>
                                        <div class="space-y-3">
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm text-gray-500 dark:text-gray-400">総架電数:</span>
                                                <span class="text-xl font-bold text-gray-700 dark:text-gray-200">{{ number_format($userKpi->total_calls) }}</span>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm text-gray-500 dark:text-gray-400">総アポイント獲得数:</span>
                                                <span class="text-xl font-bold text-gray-700 dark:text-gray-200">{{ number_format($userKpi->total_appointments) }}</span>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm text-gray-500 dark:text-gray-400">アポイント獲得率:</span>
                                                <span class="text-xl font-bold text-gray-700 dark:text-gray-200">{{ number_format($userKpi->appointment_rate, 2) }}%</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-xl p-6">
                                <p class="text-gray-500 dark:text-gray-400 text-center">{{ __('表示できる担当者別のデータがありません。') }}</p>
                                <p class="text-sm text-gray-400 dark:text-gray-500 text-center mt-2">
                                    デバッグ情報: ユーザー数={{ $perUserKpis->count() }}
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                        <div class="bg-white dark:bg-gray-700 p-4 rounded-lg shadow-md">
                            <h4 class="text-md font-semibold mb-3 text-gray-800 dark:text-gray-200">{{ __('架電状況の内訳') }}</h4>
                            <div class="relative h-80 w-full sm:h-96">
                                <canvas id="statusBreakdownChart"></canvas>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-700 p-4 rounded-lg shadow-md">
                            <h4 class="text-md font-semibold mb-3 text-gray-800 dark:text-gray-200">架電活動の推移 (折れ線グラフ予定)</h4>
                            <div class="h-80 sm:h-96 flex items-center justify-center text-gray-400 dark:text-gray-300">
                                ここにグラフ2</div>
                        </div>
                        <div class="bg-white dark:bg-gray-700 p-4 rounded-lg shadow-md">
                            <h4 class="text-md font-semibold mb-3 text-gray-800 dark:text-gray-200">時間帯別分析 (棒グラフ予定)</h4>
                            <div class="h-80 sm:h-96 flex items-center justify-center text-gray-400 dark:text-gray-300">
                                ここにグラフ3</div>
                        </div>
                        <div class="bg-white dark:bg-gray-700 p-4 rounded-lg shadow-md">
                            <h4 class="text-md font-semibold mb-3 text-gray-800 dark:text-gray-200">曜日別分析 (棒グラフ予定)</h4>
                            <div class="h-80 sm:h-96 flex items-center justify-center text-gray-400 dark:text-gray-300">
                                ここにグラフ4</div>
                        </div>
                    </div>

                    <div class="mt-8 text-right">
                        <button type="button"
                            class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white font-semibold text-xs uppercase tracking-widest rounded-md transition ease-in-out duration-150">
                            {{ __('データをエクスポート (予定)') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const statusBreakdownCtx = document.getElementById('statusBreakdownChart');
            if (statusBreakdownCtx) {
                const statusLabels = @json($chartData['status_breakdown']['labels'] ?? []);
                const statusData = @json($chartData['status_breakdown']['data'] ?? []);
                const statusColors = @json($chartData['status_breakdown']['colors'] ?? []);

                if (statusLabels.length > 0 && statusData.length > 0) {
                    new Chart(statusBreakdownCtx, {
                        type: 'pie',
                        data: {
                            labels: statusLabels,
                            datasets: [{
                                label: '架電状況件数',
                                data: statusData,
                                backgroundColor: statusColors,
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                title: {
                                    display: false,
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            if (context.parsed !== null) {
                                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                                const percentage = total > 0 ? (context.parsed / total * 100).toFixed(1) : 0;
                                                label += `${context.raw}件 (${percentage}%)`;
                                            }
                                            return label;
                                        }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    const ctx = statusBreakdownCtx.getContext('2d');
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillStyle = document.documentElement.classList.contains('dark') ? '#CBD5E0' : '#718096';
                    ctx.font = '16px sans-serif';
                    ctx.fillText('表示するデータがありません', statusBreakdownCtx.width / 2, statusBreakdownCtx.height / 2);
                }
            }
        });
    </script>
    @endpush
</x-app-layout>