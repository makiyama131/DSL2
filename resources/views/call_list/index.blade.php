{{-- resources/views/call_list.index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('架電リスト一覧') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                {{-- ★★★ 親となる x-data コンポーネント ★★★ --}}
                
                <div class="p-6 text-gray-900 dark:text-gray-100" x-data="{
                        // --- 共通データ ---
                        allStatuses: [], // JavaScriptで利用するステータスリスト

                        // --- フィルター関連 (今回は一旦コメントアウトまたは省略) ---
                        // startDate: '',
                        // endDate: '',
                        // formatDateForInput(dateString) { /* ... */ },
                        // formatDateTimeForDisplay(dateTimeString) { /* ... */ },
                        // setPresetDateRange(preset) { /* ... */ },

                        // --- 架電記録モーダル関連 ---
                        isCallModalOpen: false,
                        callModalSelectedListId: null,
                        callModalCompanyName: '',
                        callModalActionUrl: '',
                        callModalStatusId: '',
                        callModalMemo: '',
                        callModalCalledAt: '',
                        callModalErrors: {},
                        callModalIsSubmitting: false,

                        // --- 履歴モーダル関連 ---
                        isHistoryModalOpen: false,
                        historyCallListId: null,
                        historyCompanyName: '',
                        histories: [],
                        isLoadingHistories: false,
                        historyError: '',

                        // --- 初期化メソッド ---
                        initPage() {
                            console.log('Call List Page Alpine component initialized.');
                            this.allStatuses = {{ Js::from($allStatuses ?? []) }};
                            // this.startDate = '{{ $startDate ?? '' }}';
                            // this.endDate = '{{ $endDate ?? '' }}';
                        },

                        // --- 日時フォーマットメソッド ---
                        formatDateTimeForDisplay(dateTimeString) {
                             if (!dateTimeString) return '';
                             try {
                                 const date = new Date(dateTimeString);
                                 if (isNaN(date.getTime())) { return dateTimeString; }
                                 const year = date.getFullYear();
                                 const month = (date.getMonth() + 1).toString().padStart(2, '0');
                                 const day = date.getDate().toString().padStart(2, '0');
                                 const hours = date.getHours().toString().padStart(2, '0');
                                 const minutes = date.getMinutes().toString().padStart(2, '0');
                                 return `${year}/${month}/${day} ${hours}:${minutes}`;
                             } catch (e) { return dateTimeString; }
                        },

                        // --- 架電記録モーダル用メソッド ---
                        openCallModalFor(listId, companyName) {
                            console.log('Opening Call Record Modal for ID:', listId, 'Company:', companyName);
                            this.callModalSelectedListId = listId;
                            this.callModalCompanyName = companyName;
                            this.callModalActionUrl = `/call-list/${listId}/record-call`;

                            let defaultStatusId = '';
                            if (this.allStatuses && this.allStatuses.length > 0) {
                                const unhandledStatus = this.allStatuses.find(s => s.status_name === '未対応');
                                if (unhandledStatus) {
                                    defaultStatusId = unhandledStatus.id;
                                } else {
                                    defaultStatusId = this.allStatuses[0].id;
                                }
                            }
                            this.callModalStatusId = defaultStatusId ? defaultStatusId.toString() : '';

                            this.callModalMemo = '';
                            const now = new Date();
                            const timezoneOffset = now.getTimezoneOffset() * 60000;
                            const localISOTime = new Date(now.getTime() - timezoneOffset).toISOString().slice(0, 16);
                            this.callModalCalledAt = localISOTime;
                            this.callModalErrors = {};
                            this.isCallModalOpen = true;
                        },
                        closeCallModal() {
                            this.isCallModalOpen = false;
                        },
                        async submitCallRecordForm() {
                            this.callModalIsSubmitting = true;
                            this.callModalErrors = {};

                            const formData = new FormData();
                            formData.append('call_status_id', this.callModalStatusId);
                            formData.append('call_memo', this.callModalMemo);
                            formData.append('called_at', this.callModalCalledAt);
                            
                            try {
                                const response = await fetch(this.callModalActionUrl, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content'),
                                        'Accept': 'application/json',
                                    },
                                    body: formData
                                });

                                const responseData = await response.json();

                                if (!response.ok) {
                                    if (response.status === 422 && responseData.errors) {
                                        this.callModalErrors = responseData.errors;
                                    } else {
                                        this.callModalErrors = { general: [responseData.message || 'エラーが発生しました。'] };
                                    }
                                    throw new Error(responseData.message || '記録に失敗しました。');
                                }

                                this.closeCallModal();
                                <!-- 5月5日　アラートを無くす修正をした -->
                                <!-- alert(responseData.message);  -->

                                const updatedData = responseData.updated_call_list_data;
                                if (updatedData && updatedData.id) {
                                    const rowToUpdate = document.getElementById(`call-list-row-${updatedData.id}`);
                                    if (rowToUpdate) {
                                        const statusCell = rowToUpdate.querySelector('.call-status-cell');
                                        if (statusCell) {
                                            statusCell.textContent = updatedData.latest_call_status_name || '未設定';
                                            if (updatedData.latest_call_status_classes && Array.isArray(updatedData.latest_call_status_classes)) {
                                                const baseStatusClasses = 'text-sm font-medium call-status-cell px-3 py-1 rounded-full whitespace-nowrap min-w-[100px] text-center'; // ★この定義が正しいか確認
                                                const newColorClasses = updatedData.latest_call_status_classes || 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200'; // フォールバック
                                            } else {
                                                const defaultColorClasses = ['bg-gray-200', 'dark:bg-gray-600', 'text-gray-700', 'dark:text-gray-200'];
                                                const baseClasses = 'text-sm font-medium call-status-cell px-3 py-1 rounded-full whitespace-nowrap min-w-[100px] text-center';
                                                statusCell.className = `${baseClasses} ${defaultColorClasses.join(' ')}`;
                                            }
                                        }
                                        
                                        const memoCellSpan = rowToUpdate.querySelector('.call-memo-cell span');
                                        if (memoCellSpan) memoCellSpan.textContent = updatedData.latest_call_memo_short || '';
                                        
                                        const updatedAtCell = rowToUpdate.querySelector('.call-updated_at-cell');
                                        if (updatedAtCell) {
                                            updatedAtCell.textContent = updatedData.updated_at_formatted || this.formatDateTimeForDisplay(updatedData.updated_at);
                                        }
                                    }
                                }

                            } catch (error) {
                                console.error('Submit Call Record Error:', error);
                                if (Object.keys(this.callModalErrors).length === 0) {
                                     this.callModalErrors = { general: [error.message || '通信エラーが発生しました。'] };
                                }
                            } finally {
                                this.callModalIsSubmitting = false;
                            }
                        },

                        // --- 履歴モーダル用メソッド ---
                        openHistoryModalFor(listId, companyName) {
                            console.log('[HistoryModal] Opening for ID:', listId, 'Company:', companyName);
                            this.historyCallListId = listId;
                            this.historyCompanyName = companyName;
                            this.histories = []; // クリア
                            this.historyError = ''; // クリア
                            this.isHistoryModalOpen = true;
                            this.fetchHistories(); // データ取得開始
                        },
                        closeHistoryModal() {
                            this.isHistoryModalOpen = false;
                        },
                        async fetchHistories() {
                            if (!this.historyCallListId) {
                                console.warn('[HistoryModal] historyCallListId is null. Cannot fetch.');
                                return;
                            }
                            this.isLoadingHistories = true;
                            this.historyError = '';
                            console.log('[HistoryModal] Fetching histories for ID:', this.historyCallListId);
                            try {
                                const response = await fetch(`/call-list/${this.historyCallListId}/histories`);
                                if (!response.ok) {
                                    const errorData = await response.json().catch(() => ({ message: '履歴データの取得に失敗しました。' }));
                                    throw new Error(errorData.message || `ステータス: ${response.status}`);
                                }
                                const data = await response.json();
                                this.histories = data; 
                                console.log('[HistoryModal] Histories fetched:', this.histories);
                            } catch (error) {
                                console.error('[HistoryModal] Error fetching histories:', error);
                                this.historyError = error.message || '不明なエラーが発生し、履歴を取得できませんでした。';
                            } finally {
                                this.isLoadingHistories = false;
                            }
                        }
                     }" x-init="initPage()">

                    <div x-data="{
                           async deleteCallList(listId, companyName) {
                        if (!confirm(`本当に「${companyName}」(ID: ${listId}) を削除してもよろしいですか？`)) {
                            return;
                        }
                        try {
                            const response = await fetch(`/call-list/${listId}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content'),
                                    'Accept': 'application/json',
                                }
                            });
                            const responseData = await response.json();
                            if (!response.ok) { throw new Error(responseData.message || '削除に失敗しました。'); }
                            

                            const rowToRemove = document.getElementById(`call-list-row-${listId}`);
                            if (rowToRemove) {
                                rowToRemove.style.transition = 'opacity 0.5s ease-out';
                                rowToRemove.style.opacity = '0';
                                setTimeout(() => { rowToRemove.remove(); }, 500);
                            }
                        } catch (error) {
                            console.error('Delete Error:', error);
                            alert(error.message || 'エラーが発生しました。');
                        }
                    }          
                    }">

                    <div x-data="{
                        startDate: '{{ $startDate ?? '' }}', // コントローラから渡された初期値を設定
                        endDate: '{{ $endDate ?? '' }}',

                        formatDateForInput(date) {
                            if (!date) return '';
                            const d = new Date(date);
                            if (isNaN(d.getTime())) return '';
                            const year = d.getFullYear();
                            const month = (d.getMonth() + 1).toString().padStart(2, '0');
                            const day = d.getDate().toString().padStart(2, '0');
                            return `${year}-${month}-${day}`;
                        },
                        setPresetDateRange(preset) {
                            const today = new Date();
                            let newStartDate, newEndDate;
                            // (日付計算ロジックは前回と同じ)
                            switch (preset) {
                                case 'today': newStartDate = new Date(today); newEndDate = new Date(today); break;
                                case 'this_week': newStartDate = new Date(today); const currentDay = newStartDate.getDay(); const diffToMonday = currentDay === 0 ? -6 : 1 - currentDay; newStartDate.setDate(newStartDate.getDate() + diffToMonday); newEndDate = new Date(newStartDate); newEndDate.setDate(newStartDate.getDate() + 6); break;
                                case 'last_week': newStartDate = new Date(today); newStartDate.setDate(today.getDate() - (newStartDate.getDay() === 0 ? 13 : newStartDate.getDay() + 6)); newEndDate = new Date(newStartDate); newEndDate.setDate(newStartDate.getDate() + 6); break;
                                case 'this_month': newStartDate = new Date(today.getFullYear(), today.getMonth(), 1); newEndDate = new Date(today.getFullYear(), today.getMonth() + 1, 0); break;
                                case 'last_month': newStartDate = new Date(today.getFullYear(), today.getMonth() - 1, 1); newEndDate = new Date(today.getFullYear(), today.getMonth(), 0); break;
                                default: return;
                            }
                            if (newStartDate && newEndDate) {
                                this.startDate = this.formatDateForInput(newStartDate);
                                this.endDate = this.formatDateForInput(newEndDate);
                                this.$nextTick(() => { this.$refs.callListFilterFormForDates.submit(); }); // フォームのref名を変更
                            }
                        }
                    }">

                    

                        {{-- セッションメッセージ、新規登録ボタン、CSVインポートボタン --}}
                        <div class="mb-4 flex flex-col sm:flex-row justify-between items-center space-y-3 sm:space-y-0">
                            <div>
                                <a href="{{ route('call-list.create') }}"
                                    class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 transition ease-in-out duration-150">
                                    {{ __('新規架電リスト登録') }}
                                </a>
                                <a href="{{ route('call-list.import.form') }}"
                                    class="ml-0 sm:ml-4 mt-2 sm:mt-0 inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                                    {{ __('CSVインポート') }}
                                </a>
                            </div>
                        </div>

                        {{-- フィルターフォーム --}}
                        <form method="GET" action="{{ route('call-list.index') }}" x-ref="callListFilterForm"
                            class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-md shadow">
                            <h3 class="text-lg font-semibold mb-3 text-gray-800 dark:text-gray-200">フィルター</h3>
                            {{-- プリセット期間ボタン --}}
                            <div class="mb-4 flex flex-wrap gap-2">
                                <button type="button" @click="setPresetDateRange('today')"
                                    class="px-3 py-1 text-xs bg-sky-500 hover:bg-sky-600 text-white rounded-md transition-colors">{{ __('今日') }}</button>
                                <button type="button" @click="setPresetDateRange('this_week')"
                                    class="px-3 py-1 text-xs bg-teal-500 hover:bg-teal-600 text-white rounded-md transition-colors">{{ __('今週') }}</button>
                                <button type="button" @click="setPresetDateRange('last_week')"
                                    class="px-3 py-1 text-xs bg-teal-500 hover:bg-teal-600 text-white rounded-md transition-colors">{{ __('先週') }}</button>
                                <button type="button" @click="setPresetDateRange('this_month')"
                                    class="px-3 py-1 text-xs bg-cyan-500 hover:bg-cyan-600 text-white rounded-md transition-colors">{{ __('今月') }}</button>
                                <button type="button" @click="setPresetDateRange('last_month')"
                                    class="px-3 py-1 text-xs bg-cyan-500 hover:bg-cyan-600 text-white rounded-md transition-colors">{{ __('先月') }}</button>
                            </div>
                            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                                <a href="{{ route('call-list.index', ['priority_list' => 'am']) }}"
                                    class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white font-semibold text-xs uppercase rounded-md transition-colors">
                                    <i class="fas fa-sun mr-2"></i> {{-- Font Awesomeアイコン (任意) --}}
                                    午前リスト
                                </a>
                                <a href="{{ route('call-list.index', ['priority_list' => 'pm']) }}"
                                    class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white font-semibold text-xs uppercase rounded-md transition-colors">
                                    <i class="fas fa-moon mr-2"></i> {{-- Font Awesomeアイコン (任意) --}}
                                    午後リスト
                                </a>
                                {{-- 会社名フィルター --}}
                                <div>
                                    <label for="filter_company_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('会社名') }}</label>
                                    <input type="text" name="filter_company_name" id="filter_company_name"
                                        value="{{ $filterCompanyName ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        placeholder="会社名を検索">
                                </div>

                                {{-- ★★★ 電話番号フィルターを追加 ★★★ --}}
                                <div>
                                    <label for="filter_phone_number"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('電話番号') }}</label>
                                    <input type="text" name="filter_phone_number" id="filter_phone_number"
                                        value="{{ $filterPhoneNumber ?? '' }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        placeholder="ハイフンなしで検索">
                                </div>
                                <div>
                                    <label for="sort_by_select"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('並び替え項目') }}</label>
                                    <select name="sort_by" id="sort_by_select" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        onchange="this.form.submit()">
                                        <option value="updated_at" @if($sortBy === 'updated_at') selected @endif>
                                            {{ __('最終更新日時') }}
                                        </option>
                                        <option value="created_at" @if($sortBy === 'created_at') selected @endif>
                                            {{ __('作成日時') }}
                                        </option>
                                        <option value="id" @if($sortBy === 'id') selected @endif>{{ __('ID') }}</option>
                                        <option value="company_name" @if($sortBy === 'company_name') selected @endif>
                                            {{ __('会社名') }}
                                        </option>
                                        <option value="latest_call_status_id" @if($sortBy === 'latest_call_status_id')
                                        selected @endif>{{ __('最新状況') }}</option>
                                        <option value="call_histories_count" @if($sortBy === 'call_histories_count')
                                        selected @endif>{{ __('架電回数') }}</option>
                                        {{-- ★★★ 新しいソートオプションを追加AWS実装後修正 ★★★ --}}
                                        <option value="latest_actual_call_date" @if($sortBy === 'latest_actual_call_date')
                                        selected @endif>{{ __('最終架電日') }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="sort_direction_select"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('並び順') }}</label>
                                    <select name="sort_direction" id="sort_direction_select"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        onchange="this.form.submit()">
                                        <option value="desc" @if($sortDirection === 'desc') selected @endif>
                                            {{ __('降順 (新しい順)') }}
                                        </option>
                                        <option value="asc" @if($sortDirection === 'asc') selected @endif>
                                            {{ __('昇順 (古い順)') }}
                                        </option>
                                    </select>
                                </div>

                            </div>
                            <div class="mt-1 space-y-2 max-h-40 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-md p-2 bg-white dark:bg-gray-900">
                                @if(isset($allStatuses) && $allStatuses->count() > 0)
                                    @foreach($allStatuses as $status)
                                        <label for="status_filter_{{ $status->id }}" class="flex items-center cursor-pointer">
                                            <input type="checkbox" name="filter_status_ids[]" id="status_filter_{{ $status->id }}" value="{{ $status->id }}"
                                            {{-- $filterStatusIds 配列に現在のステータスIDが含まれていればチェック済みに --}}
                                            @if(is_array($filterStatusIds) && in_array($status->id, $filterStatusIds)) checked @endif
                                            class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $status->status_name }}</span>
                                        </label>
                                    @endforeach
                                @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('ステータスがありません') }}</p>
                                @endif
                            </div>
                            <div class="space-y-1">
                                <div>
                                    <label for="start_date_ctrl"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('最終更新 開始日') }}</label>
                                    <input type="date" name="start_date" id="start_date_ctrl" x-model="startDate"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                {{-- 期間指定 終了日 --}}
                                <div>
                                    <label for="end_date_ctrl"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('最終更新 終了日') }}</label>
                                    <input type="date" name="end_date" id="end_date_ctrl" x-model="endDate"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                            </div>

                            {{-- 検索ボタンとリセットボタン --}}
                            <div class="flex space-x-2 pt-5 self-end">
                                <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                                    {{ __('検索・絞込') }}
                                </button>
                                <a href="{{ route('call-list.index') }}"
                                    class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-100 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                    {{ __('リセット') }}
                                </a>
                            </div>
                        </form>

                        {{-- 架電リスト表示 (カード形式) --}}
                        @if($callLists->isEmpty())
                            <p class="text-center text-gray-500 dark:text-gray-400 py-8">{{ __('表示する架電リストがありません。') }}</p>
                        @else
                            <div class="space-y-4">
                                @foreach ($callLists as $callList)
                                                    <div class="bg-gray-50 dark:bg-gray-700/50 shadow-md rounded-lg p-4 hover:shadow-xl transition-shadow duration-200 ease-in-out"
                                                        id="call-list-row-{{ $callList->id }}">
                                                        <div class="flex flex-col sm:flex-row justify-between items-start">
                                                            <div class="mb-2 sm:mb-0 flex-grow">
                                                                <h4
                                                                    class="text-lg font-semibold text-indigo-700 dark:text-indigo-300 hover:underline">
                                                                    <span>{{ $callList->company_name }}</span>
                                                                </h4>
                                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                                    ID: {{ $callList->id }} |
                                                                    {{ __('最終更新') }}: <span class="call-updated_at-cell"
                                                                        x-text="formatDateTimeForDisplay('{{ $callList->updated_at->toISOString() }}')"></span>
                                                                    |
                                                                    {{ __('作成者') }}: {{ $callList->user->name ?? __('不明') }}

                                                                </p>

                                                            </div>


                                                            <div
                                                                class="text-sm font-medium call-status-cell px-3 py-1 rounded-full whitespace-nowrap min-w-[100px] text-center {{
                                    match ($callList->latestCallStatus->status_name ?? '') {
                                        'アポイント' => 'bg-green-100 dark:bg-green-800 text-green-700 dark:text-green-200',
                                        '再度架電', '追いかけ', '留守番（折り返し）' => 'bg-yellow-100 dark:bg-yellow-800 text-yellow-700 dark:text-yellow-200',
                                        'NG', '求人なし', '電話番号なし' => 'bg-red-100 dark:bg-red-800 text-red-700 dark:text-red-200',
                                        default => 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200',
                                    }
                                                                                                                                                }}">
                                                                {{ $callList->latestCallStatus->status_name ?? __('未設定') }}
                                                            </div>
                                                        </div>
                                                        <div class="mt-3 border-t border-gray-200 dark:border-gray-600 pt-3">
                                                            {{-- 代表者名 --}}
                                                            <div class="sm:col-span-1">
                                                                <dt class="text-gray-500 dark:text-gray-400 font-medium">{{ __('代表者名') }}</dt>
                                                                <dd class="text-gray-800 dark:text-gray-200">
                                                                    {{ $callList->representative_name ?? '-' }}
                                                                </dd>
                                                            </div>

                                                            {{-- 固定電話 --}}
                                                            <div class="sm:col-span-1">
                                                                <dt class="text-gray-500 dark:text-gray-400 font-medium">{{ __('固定電話') }}</dt>
                                                                <dd class="text-gray-800 dark:text-gray-200">
                                                                    @php
                                                                        $fixedPhonesOutput = [];
                                                                        if ($callList->phone_number) {
                                                                            $numberForLink = preg_replace('/[^\d+]/', '', $callList->phone_number);
                                                                            $link = '<a href="tel:' . e($numberForLink) . '" class="text-indigo-600 dark:text-indigo-400 hover:underline">' . e($callList->phone_number) . '</a>';
                                                                            $fixedPhonesOutput[] = $link . ' (主)';
                                                                        }
                                                                        // phoneNumbersリレーションから「固定」電話を取得し、(主)と重複しないものを追加
                                                                        foreach ($callList->phoneNumbers->where('phone_type', '固定') as $phone) {
                                                                            if ($callList->phone_number !== $phone->phone_number) {
                                                                                $numberForLink = preg_replace('/[^\d+]/', '', $phone->phone_number);
                                                                                $link = '<a href="tel:' . e($numberForLink) . '" class="text-indigo-600 dark:text-indigo-400 hover:underline">' . e($phone->phone_number) . '</a>';
                                                                                $fixedPhonesOutput[] = $link;
                                                                            }
                                                                        }
                                                                        // もし(主)がなくリレーションにのみ固定電話がある場合
                                                                        if (empty($callList->phone_number) && $callList->phoneNumbers->where('phone_type', '固定')->isNotEmpty() && empty($fixedPhonesOutput)) {
                                                                            foreach ($callList->phoneNumbers->where('phone_type', '固定') as $phone) {
                                                                                $fixedPhonesOutput[] = e($phone->phone_number);
                                                                            }
                                                                        }
                                                                    @endphp
                                                                    @if(!empty($fixedPhonesOutput))
                                                                        {!! implode('<br>', $fixedPhonesOutput) !!}
                                                                    @else
                                                                        -
                                                                    @endif
                                                                </dd>
                                                            </div>

                                                            {{-- 携帯電話 --}}
                                                            <div class="sm:col-span-1">
                                                                <dt class="text-gray-500 dark:text-gray-400 font-medium">{{ __('携帯電話') }}</dt>
                                                                <dd class="text-gray-800 dark:text-gray-200">
                                                                    @php
                                                                        $mobilePhonesOutput = [];
                                                                        if ($callList->mobile_phone_number) {
                                                                            $numberForLink = preg_replace('/[^\d+]/', '', $callList->mobile_phone_number);
                                                                            $ownerInfo = $callList->mobile_phone_owner ? ' (' . e($callList->mobile_phone_owner) . ')' : ' (主)';
                                                                            $link = '<a href="tel:' . e($numberForLink) . '" class="text-indigo-600 dark:text-indigo-400 hover:underline">' . e($callList->mobile_phone_number) . '</a>';
                                                                            $mobilePhonesOutput[] = $link . $ownerInfo;
                                                                        }
                                                                        // phoneNumbersリレーションから「携帯」電話を取得し、(主)と重複しないものを追加
                                                                        foreach ($callList->phoneNumbers->where('phone_type', '携帯') as $phone) {
                                                                            if ($callList->mobile_phone_number !== $phone->phone_number) {
                                                                                $mobilePhonesOutput[] = e($phone->phone_number); // 携帯電話の所有者情報はここでは表示しない (必要なら追加)
                                                                                $numberForLink = preg_replace('/[^\d+]/', '', $phone->phone_number);
                                                                                $link = '<a href="tel:' . e($numberForLink) . '" class="text-indigo-600 dark:text-indigo-400 hover:underline">' . e($phone->phone_number) . '</a>';
                                                                                $mobilePhonesOutput[] = $link;
                                                                            }
                                                                        }
                                                                        // もし(主)がなくリレーションにのみ携帯電話がある場合
                                                                        if (empty($callList->mobile_phone_number) && $callList->phoneNumbers->where('phone_type', '携帯')->isNotEmpty() && empty($mobilePhonesOutput)) {
                                                                            foreach ($callList->phoneNumbers->where('phone_type', '携帯') as $phone) {
                                                                                $mobilePhonesOutput[] = e($phone->phone_number);
                                                                            }
                                                                        }
                                                                    @endphp
                                                                    @if(!empty($mobilePhonesOutput))
                                                                        {!! implode('<br>', $mobilePhonesOutput) !!}
                                                                    @else
                                                                        -
                                                                    @endif
                                                                </dd>
                                                            </div>
                                                            @if($callList->latest_call_memo)
                                                                <div class="mt-2 call-memo-cell">
                                                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mb-1">最新メモ:</p>
                                                                    <p
                                                                        class="text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 p-2 rounded whitespace-pre-wrap break-words">
                                                                        <span>{{ Str::limit($callList->latest_call_memo, 200) }}</span>
                                                                    </p>
                                                                </div>
                                                            @endif
                                                            
                                                            @if(isset($callList->dnc_info))
            <div class="mb-3 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 dark:bg-red-900/50 dark:text-red-300">
                <p class="font-bold">
                    <i class="fas fa-exclamation-triangle mr-2"></i> {{-- Font Awesomeアイコン (任意) --}}
                    架電禁止リスト対象
                </p>
                <p class="text-sm mt-1">
                    @if($callList->dnc_info->addedByUser)
                        <strong>{{ $callList->dnc_info->addedByUser->name }}</strong> さんが
                    @endif
                    <strong>{{ $callList->dnc_info->created_at->format('Y/m/d') }}</strong> に追加しました。
                </p>
                @if($callList->dnc_info->reason)
                    <p class="text-xs mt-1">理由: {{ $callList->dnc_info->reason }}</p>
                @endif
            </div>
        @endif
                                                            
                                                         
                                                        </div>
                                                        {{-- 操作ボタン --}}
                                                        <div
                                                            class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-600 flex flex-wrap gap-3 justify-end">
                                                            <button type="button"
                                                                @click="openCallModalFor({{ $callList->id }}, '{{ addslashes(e($callList->company_name)) }}')"
                                                                class="flex-grow sm:flex-grow-0 items-center justify-center text-center w-full sm:w-auto px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-500 dark:hover:bg-blue-600 dark:focus:ring-blue-800 transition-transform transform hover:scale-105">
                                                                {{ __('架電記録') }}
                                                            </button>
                                                            <a href="{{ route('call-list.edit', $callList) }}"
                                                                class="flex-grow sm:flex-grow-0 items-center justify-center text-center w-full sm:w-auto px-5 py-2.5 text-sm font-medium text-white bg-yellow-500 rounded-lg hover:bg-yellow-600 focus:ring-4 focus:ring-yellow-300 dark:focus:ring-yellow-900 transition-transform transform hover:scale-105">
                                                                {{ __('編集') }}
                                                            </a>
                                                            <button type="button"
                                                                @click="openHistoryModalFor({{ $callList->id }}, '{{ Str::limit(addslashes(e($callList->company_name)), 30) }}')"
                                                                class="flex-grow sm:flex-grow-0 items-center justify-center text-center w-full sm:w-auto px-5 py-2.5 text-sm font-medium text-white bg-green-500 rounded-lg hover:bg-green-600 focus:ring-4 focus:outline-none focus:ring-green-300 dark:bg-green-400 dark:hover:bg-green-500 dark:focus:ring-green-700 transition-transform transform hover:scale-105">
                                                                {{ __('履歴') }}
                                                            </button>
                                                            <button type="button"
                                                                @click="deleteCallList({{ $callList->id }}, '{{ addslashes(e($callList->company_name)) }}')"
                                                                class="flex-grow sm:flex-grow-0 w-full sm:w-auto items-center justify-center text-center px-5 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:ring-4 focus:ring-red-300 dark:focus:ring-red-900 transition-transform transform hover:scale-105">
                                                                {{ __('削除') }}
                                                            </button>
                                                        </div>
                                                    </div>
                                @endforeach
                            </div>
                            {{-- ページネーション --}}
                            @if ($callLists instanceof \Illuminate\Pagination\LengthAwarePaginator && $callLists->hasPages())
                                <div class="mt-6"> {{ $callLists->links() }} </div>
                            @endif
                        @endif

                        {{-- 架電記録モーダル --}}
                        <div x-show="isCallModalOpen" @keydown.escape.window="closeCallModal()" style="display: none;"
                            x-cloak class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="recordCallModalLabel"
                            role="dialog" aria-modal="true">
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75"
                                x-show="isCallModalOpen" x-transition:enter="ease-out duration-300"
                                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"></div>
                            <div
                                class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                                <span class="hidden sm:inline-block sm:align-middle sm:h-screen"
                                    aria-hidden="true">&#8203;</span>
                                <div x-show="isCallModalOpen" x-transition:enter="ease-out duration-300"
                                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave="ease-in duration-200"
                                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
                                    @click.outside="closeCallModal()">
                                    <form @submit.prevent="submitCallRecordForm()">
                                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                            <div class="sm:flex sm:items-start">
                                                <div
                                                    class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 dark:bg-indigo-700 sm:mx-0 sm:h-10 sm:w-10">
                                                    <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-300"
                                                        xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100"
                                                        id="recordCallModalLabel">
                                                        <span x-text="callModalCompanyName || '架電リスト'"></span> (ID:
                                                        <span x-text="callModalSelectedListId"></span>) -
                                                        {{ __('架電結果を記録') }}
                                                    </h3>
                                                    <div class="mt-4 space-y-4">
                                                        <template
                                                            x-if="callModalErrors.general && callModalErrors.general.length > 0">
                                                            <div
                                                                class="p-3 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-red-900 dark:text-red-200">
                                                                <ul class="list-disc list-inside"><template
                                                                        x-for="error in callModalErrors.general"
                                                                        :key="error">
                                                                        <li x-text="error"></li>
                                                                    </template></ul>
                                                            </div>
                                                        </template>
                                                        <div>
                                                            <label for="modal_call_status_id"
                                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('架電状況') }}
                                                                <span class="text-red-500">*</span></label>
                                                            <select id="modal_call_status_id" name="call_status_id"
                                                                x-model="callModalStatusId" required
                                                                class="mt-1 block w-full py-2 px-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm text-gray-900 dark:text-gray-200">
                                                                <option value="">{{ __('選択してください') }}</option>
                                                                <template x-for="status in allStatuses" :key="status . id">
                                                                    <option :value="status . id"
                                                                        x-text="status.status_name">
                                                                    </option>
                                                                </template>
                                                            </select>
                                                            <template x-if="callModalErrors.call_status_id">
                                                                <p class="mt-1 text-xs text-red-500"
                                                                    x-text="callModalErrors.call_status_id[0]"></p>
                                                            </template>
                                                        </div>
                                                        <div><label for="modal_call_memo"
                                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('架電内容メモ') }}</label><textarea
                                                                id="modal_call_memo" name="call_memo"
                                                                x-model="callModalMemo" rows="4"
                                                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm text-gray-900 dark:text-gray-200"></textarea><template
                                                                x-if="callModalErrors.call_memo">
                                                                <p class="mt-1 text-xs text-red-500"
                                                                    x-text="callModalErrors.call_memo[0]"></p>
                                                            </template></div>
                                                        <div><label for="modal_called_at"
                                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('架電日時') }}
                                                                <span class="text-red-500">*</span></label><input
                                                                type="datetime-local" id="modal_called_at"
                                                                name="called_at" x-model="callModalCalledAt" required
                                                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm text-gray-900 dark:text-gray-200"><template
                                                                x-if="callModalErrors.called_at">
                                                                <p class="mt-1 text-xs text-red-500"
                                                                    x-text="callModalErrors.called_at[0]"></p>
                                                            </template></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                            <button type="submit" :disabled="callModalIsSubmitting"
                                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                                                <span x-show="!callModalIsSubmitting">{{ __('記録する') }}</span>
                                                <span x-show="callModalIsSubmitting" class="inline-flex items-center">
                                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                                        xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                            stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor"
                                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                        </path>
                                                    </svg>
                                                    {{ __('送信中...') }}
                                                </span>
                                            </button>
                                            <button type="button" @click="closeCallModal()"
                                                :disabled="callModalIsSubmitting"
                                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-500 shadow-sm px-4 py-2 bg-white dark:bg-gray-600 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">{{ __('閉じる') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- ★★★ 履歴モーダルのHTML ★★★ --}}
                        <div x-show="isHistoryModalOpen" @keydown.escape.window="closeHistoryModal()"
                            style="display: none;" x-cloak class="fixed inset-0 z-[100] overflow-y-auto"
                            aria-labelledby="historyModalLabel" role="dialog" aria-modal="true">
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75"
                                x-show="isHistoryModalOpen" x-transition:enter="ease-out duration-300"
                                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"></div>

                            <div
                                class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                                <span class="hidden sm:inline-block sm:align-middle sm:h-screen"
                                    aria-hidden="true">&#8203;</span>

                                <div x-show="isHistoryModalOpen" x-transition:enter="ease-out duration-300"
                                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave="ease-in duration-200"
                                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full"
                                    @click.outside="closeHistoryModal()">

                                    {{-- モーダルヘッダー --}}
                                    <div
                                        class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200 dark:border-gray-700">
                                        <div class="sm:flex sm:items-start">
                                            <div
                                                class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-700 sm:mx-0 sm:h-10 sm:w-10">
                                                <svg class="h-6 w-6 text-green-600 dark:text-green-300"
                                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </div>
                                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100"
                                                    id="historyModalLabel">
                                                    <span x-text="historyCompanyName || '{{ __('企業名不明') }}'"></span> -
                                                    {{ __('架電履歴') }} (ID: <span x-text="historyCallListId"></span>)
                                                </h3>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- モーダルボディ --}}
                                    <div class="p-6 text-gray-900 dark:text-gray-100">
                                        {{-- ローディング表示 --}}
                                        <div x-show="isLoadingHistories" class="text-center py-6">
                                            <svg class="animate-spin h-8 w-8 text-indigo-600 dark:text-indigo-400 mx-auto"
                                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                    stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                                {{ __('履歴を読み込み中...') }}
                                            </p>
                                        </div>

                                        {{-- エラー表示 --}}
                                        <div x-show="!isLoadingHistories && historyError"
                                            class="p-4 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-red-900 dark:text-red-200"
                                            role="alert">
                                            <span class="font-medium">{{ __('エラー:') }}</span> <span
                                                x-text="historyError"></span>
                                        </div>

                                        {{-- 履歴がない場合の表示 --}}
                                        <div x-show="!isLoadingHistories && !historyError && histories.length === 0"
                                            class="text-center py-6">
                                            <svg class="h-12 w-12 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                                            </svg>
                                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                                {{ __('表示できる架電履歴がありません。') }}
                                            </p>
                                        </div>

                                        {{-- 履歴テーブルコンテナ --}}
                                        <div x-show="!isLoadingHistories && !historyError && histories.length > 0"
                                            class="overflow-x-auto max-h-96">
                                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                                <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
                                                    <tr>
                                                        <th scope="col"
                                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                            {{ __('架電日時') }}
                                                        </th>
                                                        <th scope="col"
                                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                            {{ __('担当者') }}
                                                        </th>
                                                        <th scope="col"
                                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                            {{ __('状況') }}
                                                        </th>
                                                        <th scope="col"
                                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                            {{ __('メモ') }}
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody
                                                    class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                                    <template x-for="historyItem in histories" :key="historyItem . id">
                                                        <tr>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"
                                                                x-text="formatDateTimeForDisplay(historyItem.called_at)">
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"
                                                                x-text="historyItem.user ? historyItem.user.name : '{{ __('不明') }}'">
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"
                                                                x-text="historyItem.status ? historyItem.status.status_name : (historyItem.call_status_master ? historyItem.call_status_master.status_name : '{{ __('不明') }}')">
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-pre-wrap break-words text-sm text-gray-700 dark:text-gray-300"
                                                                x-text="historyItem.call_memo || ''"></td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    {{-- モーダルフッター (閉じるボタン) --}}
                                    <div
                                        class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                        <button type="button" @click="closeHistoryModal()"
                                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-500 shadow-sm px-4 py-2 bg-white dark:bg-gray-600 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                            {{ __('閉じる') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> {{-- 親 x-data スコープの閉じタグ --}}
                </div>
            </div>
        </div>
        @pushOnce('scripts')
            <script>
                console.log('call_list.index page scripts stack executed.');
            </script>
        @endPushOnce
</x-app-layout>