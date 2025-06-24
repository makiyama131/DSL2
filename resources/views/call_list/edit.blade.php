<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('架電リスト編集 (ID: ') . $callList->id . ')' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100 mb-4">
                        {{ $callList->company_name . __(' の情報を編集') }}
                    </h3>

                    {{-- エラー表示 --}}
                    @if ($errors->any())
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <strong class="font-bold">{{ __('入力内容にエラーがあります。') }}</strong>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('call-list.update', $callList) }}">
                        @csrf
                        @method('PUT') {{-- ★★★ 更新処理なのでPUTメソッドを指定 ★★★ --}}

                        <div class="space-y-6">
                            {{-- 会社名 --}}
                            <div>
                                <label for="company_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('会社名') }} <span class="text-red-500">*</span></label>
                                <div class="mt-1">
                                    <input id="company_name" name="company_name" type="text" value="{{ old('company_name', $callList->company_name) }}" required autofocus {{-- ★ value に既存データを設定 --}}
                                           class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('company_name') border-red-500 @enderror">
                                </div>
                                @error('company_name')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 電話番号 (固定) --}}
                            <div>
                                <label for="phone_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('電話番号 (固定)') }}</label>
                                <div class="mt-1">
                                    <input id="phone_number" name="phone_number" type="text" value="{{ old('phone_number', $callList->phone_number) }}" {{-- ★ value に既存データを設定 --}}
                                           class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('phone_number') border-red-500 @enderror">
                                </div>
                                @error('phone_number')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 電話番号 (携帯) --}}
                            <div>
                                <label for="mobile_phone_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('電話番号 (携帯)') }}</label>
                                <div class="mt-1">
                                    <input id="mobile_phone_number" name="mobile_phone_number" type="text" value="{{ old('mobile_phone_number', $callList->mobile_phone_number) }}" {{-- ★ value に既存データを設定 --}}
                                           class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('mobile_phone_number') border-red-500 @enderror">
                                </div>
                                @error('mobile_phone_number')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 代表者名 --}}
                            <div>
                                <label for="representative_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('代表者名') }}</label>
                                <div class="mt-1">
                                    <input id="representative_name" name="representative_name" type="text" value="{{ old('representative_name', $callList->representative_name) }}" {{-- ★ value に既存データを設定 --}}
                                           class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('representative_name') border-red-500 @enderror">
                                </div>
                                @error('representative_name')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 携帯番号主名 --}}
                            <div>
                                <label for="mobile_phone_owner" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('携帯番号主名') }}</label>
                                <div class="mt-1">
                                    <input id="mobile_phone_owner" name="mobile_phone_owner" type="text" value="{{ old('mobile_phone_owner', $callList->mobile_phone_owner) }}" {{-- ★ value に既存データを設定 --}}
                                           class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('mobile_phone_owner') border-red-500 @enderror">
                                </div>
                                @error('mobile_phone_owner')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 所在地 --}}
                            <div>
                                <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('所在地') }}</label>
                                <div class="mt-1">
                                    <input id="address" name="address" type="text" value="{{ old('address', $callList->address) }}" {{-- ★ value に既存データを設定 --}}
                                           class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('address') border-red-500 @enderror">
                                </div>
                                @error('address')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 架電状況 (ドロップダウン) - これは編集画面では「最新の架電状況」として表示するが、CallList自体を編集する際は、通常は履歴から最新が反映されるため、直接編集はさせない方が良いかもしれない。ここでは例として編集可能にしているが、要件に応じて変更を検討。 --}}
                            {{-- 今回は CallList の latest_call_status_id と latest_call_memo を直接編集する想定で残します。 --}}
                            <div>
                                <label for="latest_call_status_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('最新架電状況') }} <span class="text-red-500">*</span></label>
                                <div class="mt-1">
                                    <select id="latest_call_status_id" name="latest_call_status_id" required
                                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('latest_call_status_id') border-red-500 @enderror">
                                        <option value="">{{ __('選択してください') }}</option>
                                        @if(isset($statuses) && $statuses->count() > 0)
                                            @foreach ($statuses as $status)
                                                <option value="{{ $status->id }}" {{ old('latest_call_status_id', $callList->latest_call_status_id) == $status->id ? 'selected' : '' }}> {{-- ★ selected 属性を適切に設定 --}}
                                                    {{ $status->status_name }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                @error('latest_call_status_id')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 架電内容 (最新) --}}
                            <div>
                                <label for="latest_call_memo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('最新架電内容') }}</label>
                                <div class="mt-1">
                                    <textarea id="latest_call_memo" name="latest_call_memo" rows="3"
                                              class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('latest_call_memo') border-red-500 @enderror">{{ old('latest_call_memo', $callList->latest_call_memo) }}</textarea> {{-- ★ textarea の中身に既存データを設定 --}}
                                </div>
                                @error('latest_call_memo')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end space-x-3">
                            <a href="{{ route('call-list.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-500 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                {{ __('キャンセル') }}
                            </a>
                            <button type="submit"
                                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                {{ __('更新する') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>