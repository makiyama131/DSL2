<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('新規会社登録') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8"> {{-- フォームなので少し幅を狭くしました --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100 mb-6"> {{-- マージン調整 --}}
                        {{ __('会社情報を入力してください') }}
                    </h3>

                    {{-- エラー表示 --}}
                    @if ($errors->any())
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative"
                            role="alert">
                            <strong class="font-bold">{{ __('入力内容にエラーがあります。') }}</strong>
                            <ul class="mt-1 list-disc list-inside text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('companies.store') }}">
                        @csrf

                        <div class="space-y-6">
                            {{-- 会社名 --}}
                            <div>
                                <label for="name"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('会社名') }}
                                    <span class="text-red-500">*</span></label>
                                <div class="mt-1">
                                    <input id="name" name="name" type="text"
                                        value="{{ old('name') }}" required autofocus
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('name') border-red-500 @enderror">
                                </div>
                                @error('name')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 絵文字 --}}
                            <div>
                                <label for="emoji_identifier"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('絵文字') }}</label>
                                <div class="mt-1">
                                    <input id="emoji_identifier" name="emoji_identifier" type="text"
                                        value="{{ old('emoji_identifier') }}"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('emoji_identifier') border-red-500 @enderror"
                                        placeholder="例: ✨">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('会社を表す絵文字を任意で入力できます。') }}</p>
                                </div>
                                @error('emoji_identifier')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end space-x-3">
                            <a href="{{ route('companies.index') }}"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-500 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                {{ __('一覧に戻る') }}
                            </a>
                            <button type="submit"
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                {{ __('登録する') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>