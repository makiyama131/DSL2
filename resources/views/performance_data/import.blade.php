<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $company->name }} - {{ __('パフォーマンスデータCSVインポート') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8"> {{-- max-w-4xl に変更して少し幅を調整 --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 md:p-8 text-gray-900 dark:text-gray-100">

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
                    @if ($errors->any())
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <strong class="font-bold">{{ __('入力内容にエラーがあります。') }}</strong>
                            <ul class="mt-2 list-disc list-inside text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('performance_data.import.store', $company) }}" enctype="multipart/form-data">
                        @csrf

                        <div class="space-y-6">
                            <div>
                                <label for="csv_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('CSVファイル') }} <span class="text-red-500">*</span></label>
                                <div class="mt-1">
                                    <input id="csv_file" name="csv_file" type="file" required accept=".csv"
                                           class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 @error('csv_file') border-red-500 @enderror">
                                </div>
                                @error('csv_file')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mt-8 flex flex-col sm:flex-row justify-start space-y-3 sm:space-y-0 sm:space-x-3">
                                <button type="submit"
                                        class="inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                                    {{ __('インポート実行') }}
                                </button>
                                <a href="{{ route('companies.index') }}"
                                   class="inline-flex justify-center items-center px-4 py-2 bg-gray-200 dark:bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-100 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-500 active:bg-gray-400 dark:active:bg-gray-400 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                                    {{ __('会社一覧に戻る') }}
                                </a>
                                {{--
                                <a href="{{ route('performance_data.index', $company) }}" class="btn btn-link">{{ __('パフォーマンスデータ一覧に戻る') }}</a>
                                --}}
                            </div>
                        </div>
                    </form>

                    <hr class="my-8 border-gray-200 dark:border-gray-700">

                    <div>
                        <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('CSVファイルの形式について:') }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('以下のヘッダー行で始まるUTF-8エンコードのCSVファイルをアップロードしてください。') }}
                        </p>
                        <div class="mt-2 p-3 bg-gray-50 dark:bg-gray-700 rounded-md text-xs text-gray-700 dark:text-gray-300 overflow-x-auto">
                            <code>日付,表示回数,クリック率（CTR）,クリック数,応募開始率 (ASR),応募開始数,応募完了率,応募数</code>
                        </div>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('例:') }}</p>
                        <pre class="mt-1 p-3 bg-gray-50 dark:bg-gray-700 rounded-md text-xs text-gray-700 dark:text-gray-300 overflow-x-auto"><code>日付,表示回数,クリック率（CTR）,クリック数,応募開始率 (ASR),応募開始数,応募完了率,応募数
2025-03-04,60,0.11666666666666667,7,0,0,0,0
2025-03-05,154,0.09090909090909091,14,0,0,0,0</code></pre>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>