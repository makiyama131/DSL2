<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('架電禁止リスト - 編集 (ID: ') . $doNotCallList->id . ')' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

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

                    <form method="POST" action="{{ route('admin.dnc-lists.update', $doNotCallList) }}">
                        @csrf
                        @method('PUT') {{-- 更新なのでPUTメソッドを指定 --}}

                        <div class="space-y-6">
                            {{-- 電話番号 --}}
                            <div>
                                <label for="phone_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('電話番号') }}</label>
                                <div class="mt-1">
                                    <input id="phone_number" name="phone_number" type="text" value="{{ old('phone_number', $doNotCallList->phone_number) }}"
                                           class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('phone_number') border-red-500 @enderror">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('会社名を指定する場合は、電話番号は任意です。') }}</p>
                                </div>
                                @error('phone_number')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 会社名 --}}
                            <div>
                                <label for="company_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('会社名') }}</label>
                                <div class="mt-1">
                                    <input id="company_name" name="company_name" type="text" value="{{ old('company_name', $doNotCallList->company_name) }}"
                                           class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('company_name') border-red-500 @enderror">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('電話番号を指定する場合は、会社名は任意です。') }}</p>
                                </div>
                                @error('company_name')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ __('電話番号と会社名の少なくとも一方は入力してください。') }}
                            </p>

                            {{-- 禁止理由 --}}
                            <div>
                                <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('禁止理由') }}</label>
                                <div class="mt-1">
                                    <textarea id="reason" name="reason" rows="3"
                                              class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('reason') border-red-500 @enderror">{{ old('reason', $doNotCallList->reason) }}</textarea>
                                </div>
                                @error('reason')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 備考 --}}
                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('備考') }}</label>
                                <div class="mt-1">
                                    <textarea id="notes" name="notes" rows="3"
                                              class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('notes') border-red-500 @enderror">{{ old('notes', $doNotCallList->notes) }}</textarea>
                                </div>
                                @error('notes')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end space-x-3">
                            <a href="{{ route('admin.dnc-lists.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-500 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
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