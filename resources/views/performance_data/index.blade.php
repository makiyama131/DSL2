{{-- resources/views/performance_data/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $company->name }} - {{ __('パフォーマンスデータ一覧') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 dark:bg-green-700 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-200 rounded relative" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="mb-4 p-4 bg-red-100 dark:bg-red-700 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-200 rounded relative" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif
                    @if (session('warning'))
                        <div class="mb-4 p-4 bg-yellow-100 dark:bg-yellow-700 border border-yellow-400 dark:border-yellow-600 text-yellow-700 dark:text-yellow-200 rounded relative" role="alert">
                            {{ session('warning') }}
                        </div>
                    @endif

                    {{-- CSVインポート画面へのリンクと会社一覧へ戻るボタン --}}
                    <div class="mb-6 flex flex-col sm:flex-row justify-between items-center space-y-2 sm:space-y-0 sm:space-x-3">
                        <a href="{{ route('performance_data.import.create', $company) }}"
                           class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150 w-full sm:w-auto">
                            {{ __('CSVデータをインポートする') }}
                        </a>
                        <a href="{{ route('companies.index') }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-100 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-500 active:bg-gray-400 dark:active:bg-gray-400 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150 w-full sm:w-auto">
                            {{ __('会社一覧に戻る') }}
                        </a>
                    </div>

                    <div x-data="{
                        startDate: '{{ $startDate ?? '' }}',
                        endDate: '{{ $endDate ?? '' }}',
                        
                        // YYYY-MM-DD形式で日付をフォーマットするヘルパー関数
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
                                    newStartDate = today;
                                    newEndDate = today;
                                    break;
                                case 'this_week': // 月曜始まりと仮定
                                    const currentDay = today.getDay(); // 0 (日曜) - 6 (土曜)
                                    const diffToMonday = currentDay === 0 ? -6 : 1 - currentDay;
                                    newStartDate = new Date(today.setDate(today.getDate() + diffToMonday));
                                    newEndDate = new Date(newStartDate);
                                    newEndDate.setDate(newStartDate.getDate() + 6);
                                    break;
                                case 'last_week': // 月曜始まりと仮定
                                    const lastWeekToday = new Date();
                                    const dayOfWeek = lastWeekToday.getDay();
                                    const diffToLastMonday = dayOfWeek === 0 ? -13 : - (dayOfWeek + 6);
                                    newStartDate = new Date(lastWeekToday.setDate(lastWeekToday.getDate() + diffToLastMonday));
                                    newEndDate = new Date(newStartDate);
                                    newEndDate.setDate(newStartDate.getDate() + 6);
                                    break;
                                case 'this_month':
                                    newStartDate = new Date(today.getFullYear(), today.getMonth(), 1);
                                    newEndDate = new Date(today.getFullYear(), today.getMonth() + 1, 0); // 月の最終日
                                    break;
                                case 'last_month':
                                    newStartDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                                    newEndDate = new Date(today.getFullYear(), today.getMonth(), 0); // 前月の最終日
                                    break;
                            }
                            this.startDate = this.formatDate(newStartDate);
                            this.endDate = this.formatDate(newEndDate);
                            // フォームを自動送信
                            this.$nextTick(() => { // DOM更新後に実行
                                this.$refs.filterForm.submit();
                            });
                        }
                    }">
                        <form method="GET" action="{{ route('performance_data.index', $company) }}" x-ref="filterForm" class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-md shadow">
                            <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('表示期間で絞り込み') }}</h4>
                            
                            {{-- ★★★ プリセット期間ボタン ★★★ --}}
                            <div class="mb-3 flex flex-wrap gap-2">
                                <button type="button" @click="setPresetDateRange('today')" class="px-3 py-1 text-xs bg-sky-500 hover:bg-sky-600 text-white rounded-md">{{ __('今日') }}</button>
                                <button type="button" @click="setPresetDateRange('this_week')" class="px-3 py-1 text-xs bg-teal-500 hover:bg-teal-600 text-white rounded-md">{{ __('今週') }}</button>
                                <button type="button" @click="setPresetDateRange('last_week')" class="px-3 py-1 text-xs bg-teal-500 hover:bg-teal-600 text-white rounded-md">{{ __('先週') }}</button>
                                <button type="button" @click="setPresetDateRange('this_month')" class="px-3 py-1 text-xs bg-cyan-500 hover:bg-cyan-600 text-white rounded-md">{{ __('今月') }}</button>
                                <button type="button" @click="setPresetDateRange('last_month')" class="px-3 py-1 text-xs bg-cyan-500 hover:bg-cyan-600 text-white rounded-md">{{ __('先月') }}</button>
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
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 ...">
                                        {{ __('適用') }}
                                    </button>
                                    <a href="{{ route('performance_data.index', $company) }}" class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-600 border ...">
                                        {{ __('リセット') }}
                                    </a>
                                </div>
                            </div>
                            {{-- ソート順維持用の隠しフィールド (もしあれば) --}}
                        </form>
                    </div>
                    {{-- ★★★ Alpine.js x-data スコープの閉じタグ ★★★ --}}

                    

                    {{-- ★★★ ここから主要KPIサマリーカードを追加 ★★★ --}}
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold leading-6 text-gray-900 dark:text-gray-100 mb-4">
                            {{ __('期間サマリー') }}
                            @if($startDate && $endDate)
                                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                                    ({{ \Carbon\Carbon::parse($startDate)->format('Y/m/d') }} - {{ \Carbon\Carbon::parse($endDate)->format('Y/m/d') }})
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
                            {{-- 総表示回数 --}}
                            <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg shadow">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">{{ __('総表示回数') }}</dt>
                                <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">{{ number_format($totalImpressions) }}</dd>
                            </div>
                            {{-- 総クリック数 --}}
                            <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg shadow">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">{{ __('総クリック数') }}</dt>
                                <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">{{ number_format($totalClicks) }}</dd>
                            </div>
                            {{-- 平均CTR --}}
                            <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg shadow">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">{{ __('平均クリック率 (CTR)') }}</dt>
                                <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">{{ number_format($averageCTR, 2) }}%</dd>
                            </div>
                            {{-- 総応募開始数 --}}
                            <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg shadow">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">{{ __('総応募開始数') }}</dt>
                                <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">{{ number_format($totalApplicationStarts) }}</dd>
                            </div>
                            {{-- 総応募数 --}}
                            <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg shadow">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">{{ __('総応募数') }}</dt>
                                <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">{{ number_format($totalApplications) }}</dd>
                            </div>
                            {{-- 平均応募完了率 --}}
                            <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg shadow">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">{{ __('平均応募完了率') }}</dt>
                                <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">{{ number_format($averageCompletionRate, 2) }}%</dd>
                            </div>
                        </div>
                    </div>
                    {{-- ★★★ KPIサマリーカードここまで ★★★ --}}

                    @if($performanceData->isEmpty())
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('データがありません') }}</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                @if(request()->has('start_date') || request()->has('end_date'))
                                    {{ __('指定された期間に該当するデータがありません。') }}
                                @else
                                    {{ __('まずはCSVデータをインポートしてください。') }}
                                @endif
                            </p>
                        </div>
                    @else
                        {{-- グラフ表示セクション --}}
                        <div class="mt-6 mb-8 p-4 border border-gray-200 dark:border-gray-700 rounded-lg shadow-md">
                            <h4 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">{{ __('パフォーマンス推移グラフ') }}</h4>
                            <div class="relative h-96 w-full">
                                <canvas id="performanceChart"></canvas>
                            </div>
                        </div>
                        <hr class="my-6 border-gray-200 dark:border-gray-700">

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('日付') }}</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('表示回数') }}</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('クリック率(CTR)') }}</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('クリック数') }}</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('応募開始率(ASR)') }}</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('応募開始数') }}</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('応募完了率') }}</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('応募数') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($performanceData as $data)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ \Carbon\Carbon::parse($data->date)->format('Y/m/d') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">{{ number_format($data->impressions) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">{{ number_format($data->ctr * 100, 2) }}%</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">{{ number_format($data->clicks) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">{{ number_format($data->asr * 100, 2) }}%</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">{{ number_format($data->application_starts) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">{{ number_format($data->completion_rate * 100, 2) }}%</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">{{ number_format($data->applications) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- ページネーションリンクの表示 --}}
                        <div class="mt-6">
                            {{-- フィルタリングされた結果にもページネーションが適用されるように、クエリパラメータを維持する --}}
                            {{ $performanceData->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- グラフ描画用のJavaScript --}}
    {{-- ... グラフ描画用JavaScript ... --}}
@if(isset($chartLabels) && !$performanceData->isEmpty())
    @push('scripts')
    {{-- Chart.jsライブラリ (必要に応じて) --}}
    {{-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            console.log('Chart script DOMContentLoaded.'); // ★ログ1: スクリプト開始確認
            const ctx = document.getElementById('performanceChart');
            console.log('Canvas element:', ctx); // ★ログ2: canvas要素取得確認

            if (ctx) {
                const chartCtx = ctx.getContext('2d');

                const chartLabels = @json($chartLabels ?? []);
                const chartImpressionsData = @json($chartImpressionsData ?? []);
                const chartApplicationsData = @json($chartApplicationsData ?? []);

                console.log('Chart Labels:', chartLabels); // ★ログ3: ラベルデータ確認
                console.log('Impressions Data:', chartImpressionsData); // ★ログ4: 表示回数データ確認
                console.log('Applications Data:', chartApplicationsData); // ★ログ5: 応募数データ確認

                // データが空でないか、または要素数が一致しているかなども確認するとより良い
                if (chartLabels.length === 0) {
                    console.warn('Chart labels are empty. Graph will not render meaningfully.');
                    // ここで処理を中断するか、空のグラフの代わりにメッセージを表示するなどの対応も可能
                }

                const isDarkMode = document.documentElement.classList.contains('dark');
                const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
                const ticksColor = isDarkMode ? 'rgba(255, 255, 255, 0.7)' : 'rgba(0, 0, 0, 0.7)';
                const legendColor = isDarkMode ? '#FFF' : '#333';

                try { // ★ Chart.jsの初期化をtry-catchで囲む
                    new Chart(chartCtx, {
                        type: 'line',
                        data: {
                            labels: chartLabels,
                            datasets: [ /* ... データセット ... */ ]
                        },
                        options: { /* ... オプション ... */ }
                    });
                    console.log('Chart initialized successfully.'); // ★ログ6: チャート初期化成功確認
                } catch (e) {
                    console.error('Error initializing Chart:', e); // ★ログ7: チャート初期化エラー確認
                }
            } else {
                console.error('Canvas element with ID "performanceChart" not found.');
            }
        });
    </script>
    @endpush
@endif
</x-app-layout>