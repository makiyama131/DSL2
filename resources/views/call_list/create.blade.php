{{-- resources/views/call_list/create.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('新規架電リスト登録') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                {{-- ★★★ Alpine.js の x-data スコープをここから開始 ★★★ --}}
                <div class="p-6 text-gray-900 dark:text-gray-100"
                     x-data="{
                         phoneNumber: '{{ old('phone_number', '') }}',
                         companyName: '{{ old('company_name', '') }}',
                         dncPhoneNumberWarning: '',
                         dncCompanyNameWarning: '',
                         isLoadingPhoneNumberDnc: false,
                         isLoadingCompanyNameDnc: false,
                         debounceTimer: null,

                         async checkDnc(type) {
                             let value;
                             let url;

                             if (type === 'phone') {
                                 value = this.phoneNumber.trim();
                                 if (!value) {
                                     this.dncPhoneNumberWarning = '';
                                     return;
                                 }
                                 this.isLoadingPhoneNumberDnc = true;
                                 this.dncPhoneNumberWarning = '';
                                 url = `/admin/dnc-check?phone_number=${encodeURIComponent(value)}`;
                             } else if (type === 'company') {
                                 value = this.companyName.trim();
                                 if (!value) {
                                     this.dncCompanyNameWarning = '';
                                     return;
                                 }
                                 this.isLoadingCompanyNameDnc = true;
                                 this.dncCompanyNameWarning = '';
                                 url = `/admin/dnc-check?company_name=${encodeURIComponent(value)}`;
                             } else {
                                 return;
                             }

                             try {
                                 const response = await fetch(url);
                                 if (!response.ok) {
                                     throw new Error('DNCチェックAPIの呼び出しに失敗しました。');
                                 }
                                 const data = await response.json();

                                 if (type === 'phone') {
                                     if (data.is_dnc) {
                                         this.dncPhoneNumberWarning = data.message;
                                     }
                                 } else if (type === 'company') {
                                     if (data.is_dnc) {
                                         this.dncCompanyNameWarning = data.message;
                                     }
                                 }
                             } catch (error) {
                                 console.error('DNC Check Error:', error);
                                 if (type === 'phone') {
                                     this.dncPhoneNumberWarning = '🈲架電禁止リストに登録されている電話番号です。';
                                 } else if (type === 'company') {
                                     this.dncCompanyNameWarning = '🈲架電禁止リストに登録されている会社名です。';
                                 }
                             } finally {
                                 if (type === 'phone') {
                                     this.isLoadingPhoneNumberDnc = false;
                                 } else if (type === 'company') {
                                     this.isLoadingCompanyNameDnc = false;
                                 }
                             }
                         },

                         handleInput(type) {
                             clearTimeout(this.debounceTimer);
                             this.debounceTimer = setTimeout(() => {
                                 this.checkDnc(type);
                             }, 800); // 800ミリ秒待ってから実行
                         }
                     }"
                >
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100 mb-4">
                        {{ __('新規架電リスト登録') }}
                    </h3>

                    {{-- エラー表示 --}}
                    @if ($errors->any() && !$errors->has('dnc_check')) {{-- dnc_check以外の通常エラー --}}
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <strong class="font-bold">{{ __('入力内容にエラーがあります。') }}</strong>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    @if ($error != $errors->first('dnc_check')) {{-- dnc_checkメッセージは別途表示するため除外 --}}
                                        <li>{{ $error }}</li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- DNCチェックのサーバーサイドエラー表示 (POST時) --}}
                    @error('dnc_check')
                        <div class="mb-4 p-4 bg-yellow-100 border border-yellow-500 text-yellow-700 rounded relative" role="alert">
                            <strong class="font-bold">{{ __('警告:') }}</strong>
                            <span class="block sm:inline">{{ $message }}</span>
                        </div>
                    @enderror

                    <form method="POST" action="{{ route('call-list.store') }}">
                        @csrf

                        <div class="space-y-6">
                            {{-- 会社名 --}}
                            <div>
                                <label for="company_name"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('会社名') }}
                                    <span class="text-red-500">*</span></label>
                                <div class="mt-1">
                                    <input id="company_name" name="company_name" type="text"
                                        {{-- value="{{ old('company_name') }}" --}} {{-- x-modelで初期化されるためコメントアウト可 --}}
                                        x-model="companyName"
                                        @input="handleInput('company')"
                                        required autofocus
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('company_name') border-red-500 @enderror">
                                    {{-- リアルタイムDNC警告表示 --}}
                                    <div x-show="isLoadingCompanyNameDnc" class="mt-1 text-xs text-gray-500 dark:text-gray-400">チェック中...</div>
                                    <div x-show="dncCompanyNameWarning" x-text="dncCompanyNameWarning" class="mt-1 text-xs text-yellow-600 dark:text-yellow-400"></div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('電話番号を指定する場合は、会社名は任意です。') }}</p>
                                </div>
                                @error('company_name') {{-- サーバーサイドバリデーションエラー --}}
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 電話番号 (固定) --}}
                            <div>
                                <label for="phone_number"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('電話番号 (固定)') }}</label>
                                <div class="mt-1">
                                    <input id="phone_number" name="phone_number" type="text"
                                        {{-- value="{{ old('phone_number') }}" --}} {{-- x-modelで初期化されるためコメントアウト可 --}}
                                        x-model="phoneNumber"
                                        @input="handleInput('phone')"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('phone_number') border-red-500 @enderror">
                                    {{-- リアルタイムDNC警告表示 --}}
                                    <div x-show="isLoadingPhoneNumberDnc" class="mt-1 text-xs text-gray-500 dark:text-gray-400">チェック中...</div>
                                    <div x-show="dncPhoneNumberWarning" x-text="dncPhoneNumberWarning" class="mt-1 text-xs text-yellow-600 dark:text-yellow-400"></div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('会社名を指定する場合は、電話番号は任意です。') }}</p>
                                </div>
                                @error('phone_number') {{-- サーバーサイドバリデーションエラー --}}
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ __('電話番号(固定)と会社名の少なくとも一方は入力してください。(これはサーバーサイドでもチェックされます)') }}
                            </p>

                            {{-- 電話番号 (携帯) --}}
                            <div>
                                <label for="mobile_phone_number"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('電話番号 (携帯)') }}</label>
                                <div class="mt-1">
                                    <input id="mobile_phone_number" name="mobile_phone_number" type="text"
                                        value="{{ old('mobile_phone_number') }}"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('mobile_phone_number') border-red-500 @enderror">
                                </div>
                                @error('mobile_phone_number')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 代表者名 --}}
                            <div>
                                <label for="representative_name"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('代表者名') }}</label>
                                <div class="mt-1">
                                    <input id="representative_name" name="representative_name" type="text"
                                        value="{{ old('representative_name') }}"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('representative_name') border-red-500 @enderror">
                                </div>
                                @error('representative_name')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 携帯番号主名 --}}
                            <div>
                                <label for="mobile_phone_owner"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('携帯番号主名') }}</label>
                                <div class="mt-1">
                                    <input id="mobile_phone_owner" name="mobile_phone_owner" type="text"
                                        value="{{ old('mobile_phone_owner') }}"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('mobile_phone_owner') border-red-500 @enderror">
                                </div>
                                @error('mobile_phone_owner')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 所在地 --}}
                            <div>
                                <label for="address"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('所在地') }}</label>
                                <div class="mt-1">
                                    <input id="address" name="address" type="text" value="{{ old('address') }}"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('address') border-red-500 @enderror">
                                </div>
                                @error('address')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>


                            {{-- 架電状況 (ドロップダウン) --}}
                            <div>
                                <label for="latest_call_status_id"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('架電状況') }}
                                    <span class="text-red-500">*</span></label>
                                <div class="mt-1">
                                    <select id="latest_call_status_id" name="latest_call_status_id" required
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('latest_call_status_id') border-red-500 @enderror">
                                        <option value="">{{ __('選択してください') }}</option>
                                        @if(isset($statuses) && $statuses->count() > 0)
                                            @foreach ($statuses as $status)
                                                <option value="{{ $status->id }}" {{ old('latest_call_status_id') == $status->id ? 'selected' : '' }}>
                                                    {{ $status->status_name }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="" disabled>{{ __('ステータスがありません') }}</option>
                                        @endif
                                    </select>
                                </div>
                                @error('latest_call_status_id')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 架電内容 (初回) --}}
                            <div>
                                <label for="latest_call_memo"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('架電内容 (初回)') }}</label>
                                <div class="mt-1">
                                    <textarea id="latest_call_memo" name="latest_call_memo" rows="3"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('latest_call_memo') border-red-500 @enderror">{{ old('latest_call_memo', '一回目の架電') }}</textarea>
                                </div>
                                @error('latest_call_memo')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end space-x-3">
                            <a href="{{ route('call-list.index') }}" {{-- キャンセルボタンの遷移先をindexに変更 --}}
                                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-500 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                {{ __('キャンセル') }} {{-- dashboardからindexへ変更したので、文言も「一覧に戻る」等の方が良いかもしれませんが、一旦「キャンセル」のまま --}}
                            </a>
                            <button type="submit"
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                {{ __('登録する') }}
                            </button>
                        </div>
                    </form>
                </div> {{-- Alpine.js x-data スコープの閉じタグ --}}
            </div>
        </div>
    </div>
</x-app-layout>