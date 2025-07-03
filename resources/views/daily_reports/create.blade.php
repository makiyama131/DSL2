<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('日報作成') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100" x-data="dailyReportForm()">

                    <form method="POST" action="{{ route('daily-reports.store') }}">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <x-input-label for="report_date" :value="__('日付')" />
                                <x-text-input id="report_date" class="block mt-1 w-full bg-gray-100 dark:bg-gray-700" type="text" name="report_date" :value="$today->format('Y-m-d')" readonly />
                            </div>
                            <div>
                                <x-input-label for="title" :value="__('タイトル')" />
                                <x-text-input id="title" class="block mt-1 w-full" type="text" name="title" :value="old('title')" required autofocus />
                            </div>
                        </div>

                        <div class="mb-6">
                            <h3 class="text-lg font-medium mb-2">本日活動記録</h3>
                            <div class="space-y-2">
                                <template x-for="(activity, index) in activities" :key="index">
                                    <div class="flex items-center space-x-2 p-2 bg-gray-50 dark:bg-gray-700/50 rounded-md">
                                        <select :name="`activities[${index}][start_time]`" x-model="activity.start_time" class="form-select rounded-md shadow-sm dark:bg-gray-900 dark:border-gray-600">
                                            <template x-for="time in timeSlots" :key="time">
                                                <option :value="time" x-text="time"></option>
                                            </template>
                                        </select>
                                        <span>〜</span>
                                        <select :name="`activities[${index}][end_time]`" x-model="activity.end_time" class="form-select rounded-md shadow-sm dark:bg-gray-900 dark:border-gray-600">
                                            <template x-for="time in timeSlots" :key="time">
                                                <option :value="time" x-text="time"></option>
                                            </template>
                                        </select>
                                        <input type="text" :name="`activities[${index}][activity_content]`" x-model="activity.activity_content" class="form-input flex-grow rounded-md shadow-sm dark:bg-gray-900 dark:border-gray-600" placeholder="活動内容">
                                        <button type="button" @click="removeActivity(index)" class="text-red-500 hover:text-red-700">削除</button>
                                    </div>
                                </template>
                            </div>
                            <button type="button" @click="addActivity()" class="mt-2 text-sm text-indigo-600 hover:text-indigo-800">＋ 活動記録を追加</button>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
                             <div>
                                <x-input-label for="calls_count" :value="__('今日の架電数')" />
                                <x-text-input id="calls_count" class="block mt-1 w-full" type="number" name="calls_count" x-model.number="calls_count" />
                            </div>
                            <div>
                                <x-input-label for="prospect_appointments_count" :value="__('見込みアポ数')" />
                                <x-text-input id="prospect_appointments_count" class="block mt-1 w-full" type="number" name="prospect_appointments_count" :value="old('prospect_appointments_count', 0)" />
                            </div>
                            <div>
                                <x-input-label for="appointments_count" :value="__('アポ数')" />
                                <x-text-input id="appointments_count" class="block mt-1 w-full bg-gray-100 dark:bg-gray-700" type="number" name="appointments_count" :value="$appointmentsCount" readonly />
                            </div>
                             <div>
                                <x-input-label for="meetings_count" :value="__('商談数')" />
                                <x-text-input id="meetings_count" class="block mt-1 w-full" type="number" name="meetings_count" x-model.number="meetings_count" min="0" />
                            </div>
                        </div>
                        
                         <div class="mb-6">
                            <x-input-label for="appointment_companies" :value="__('アポ会社')" />
                            <x-text-input id="appointment_companies" class="block mt-1 w-full bg-gray-100 dark:bg-gray-700" type="text" :value="$appointmentCompanies" readonly />
                        </div>

                        <div class="mb-6">
                            <h3 class="text-lg font-medium mb-2">商談議事録</h3>
                            <div class="space-y-4">
                                <template x-for="(meeting, index) in meetings" :key="index">
                                    <div class="p-4 border border-gray-200 dark:border-gray-600 rounded-lg">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label :for="`meeting_company_${index}`" class="block text-sm font-medium">会社名</label>
                                                <input type="text" :id="`meeting_company_${index}`" :name="`meetings[${index}][company_name]`" x-model="meeting.company_name" class="mt-1 block w-full rounded-md shadow-sm dark:bg-gray-900 dark:border-gray-600">
                                            </div>
                                             <div>
                                                <label :for="`meeting_attendees_${index}`" class="block text-sm font-medium">出席者</label>
                                                <input type="text" :id="`meeting_attendees_${index}`" :name="`meetings[${index}][attendees]`" x-model="meeting.attendees" class="mt-1 block w-full rounded-md shadow-sm dark:bg-gray-900 dark:border-gray-600">
                                            </div>
                                        </div>
                                        <div class="mt-4">
                                            <label :for="`meeting_summary_${index}`" class="block text-sm font-medium">議事録</label>
                                            <textarea :id="`meeting_summary_${index}`" :name="`meetings[${index}][summary]`" x-model="meeting.summary" rows="5" class="mt-1 block w-full rounded-md shadow-sm dark:bg-gray-900 dark:border-gray-600"></textarea>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="mb-6">
                             <x-input-label for="reflection" :value="__('反省点・改善点')" />
                            <textarea id="reflection" name="reflection" rows="5" class="mt-1 block w-full rounded-md shadow-sm dark:bg-gray-900 dark:border-gray-600">{{ old('reflection') }}</textarea>
                        </div>
                        <div class="mb-6">
                            <x-input-label for="next_action" :value="__('明日の目標 (Next Action)')" />
                            <textarea id="next_action" name="next_action" rows="5" class="mt-1 block w-full rounded-md shadow-sm dark:bg-gray-900 dark:border-gray-600">{{ old('next_action') }}</textarea>
                        </div>
                        
                        <div class="mb-6">
                            <x-input-label for="memo" :value="__('メモ')" />
                            <textarea id="memo" name="memo" rows="5" class="mt-1 block w-full rounded-md shadow-sm dark:bg-gray-900 dark:border-gray-600">{{ old('memo') }}</textarea>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <x-primary-button>
                                {{ __('日報を登録する') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function dailyReportForm() {
            return {
                activities: [{ start_time: '09:00', end_time: '09:15', activity_content: '' }],
                timeSlots: [],
                calls_count: {{ $callsCount }},
                meetings_count: 0,
                meetings: [],

                init() {
                    // 6:00から23:00までの15分刻みの時間帯を生成
                    for (let h = 6; h < 23; h++) {
                        for (let m = 0; m < 60; m += 15) {
                            this.timeSlots.push(`${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`);
                        }
                    }
                    this.timeSlots.push('23:00');

                    // 商談数の変更を監視
                    this.$watch('meetings_count', (newValue, oldValue) => {
                         const diff = newValue - oldValue;
                        if (diff > 0) {
                            for(let i = 0; i < diff; i++) {
                                this.meetings.push({ company_name: '', attendees: '', summary: '' });
                            }
                        } else if (diff < 0) {
                            this.meetings.splice(newValue);
                        }
                    });
                },
                
                addActivity() {
                    const lastActivity = this.activities[this.activities.length - 1];
                    let nextStartTime = '09:00'; // デフォルトの開始時刻
                    
                    // 最後の活動記録が存在する場合
                    if (lastActivity && lastActivity.end_time) {
                        const lastEndTime = lastActivity.end_time;
                        const lastEndTimeIndex = this.timeSlots.indexOf(lastEndTime);

                        // 最後の終了時刻が時間帯リストに存在し、かつそれが最終時刻でなければ、次の開始時刻とする
                        if (lastEndTimeIndex > -1 && lastEndTimeIndex < this.timeSlots.length - 1) {
                            nextStartTime = lastEndTime;
                        }
                    }

                    // 新しい活動の終了時刻を、開始時刻の15分後に設定
                    const nextStartTimeIndex = this.timeSlots.indexOf(nextStartTime);
                    const nextEndTime = (nextStartTimeIndex > -1 && nextStartTimeIndex < this.timeSlots.length - 1)
                        ? this.timeSlots[nextStartTimeIndex + 1]
                        : nextStartTime;

                    this.activities.push({
                        start_time: nextStartTime,
                        end_time: nextEndTime,
                        activity_content: ''
                    });
                },

                removeActivity(index) {
                    this.activities.splice(index, 1);
                }
            }
        }
    </script>
</x-app-layout>