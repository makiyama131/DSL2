<x-app-layout> {{-- ★★★ これがファイルの先頭にありますか？ ★★★ --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('架電リスト - CSVインポート') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    {{-- ... (セッションメッセージやエラー表示) ... --}}
                    @if (session('success'))
                        {{-- ... --}}
                    @endif
                    @if (session('error'))
                        {{-- ... --}}
                    @endif
                    @if ($errors->any())
                        {{-- ... --}}
                    @endif
                    {{-- インポート時の詳細エラー表示 (processImport実装後) --}}
                    @if (session('import_errors'))
                        <div class="mt-6 p-4 bg-orange-100 border border-orange-400 text-orange-700 rounded">
                            <h4 class="font-bold mb-2">{{ __('インポート処理中のエラー/スキップ詳細:') }}</h4>
                            <ul class="list-disc list-inside text-sm max-h-60 overflow-y-auto">
                                @foreach (session('import_errors') as $importError)
                                    <li>{{ $importError }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif


                    <form method="POST" action="{{ route('call-list.import.process') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="space-y-6">
                            <div>
                                <label for="csv_file"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('CSVファイルを選択') }}
                                    <span class="text-red-500">*</span></label>
                                <div class="mt-1">
                                    <input id="csv_file" name="csv_file" type="file" required accept=".csv"
                                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400">
                                </div>
                                {{-- ★★★ ここがユーザー様が言及された説明文ですね ★★★ --}}
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    対応CSVフォーマット: 1行目ヘッダー、2行目以降データ。<br>
                                    列の順番: 会社名, 電話番号(固定), 電話番号(携帯), 代表者名, 携帯番号主名, 所在地, <span
                                        class="font-bold text-indigo-600 dark:text-indigo-400">架電状況名</span>, 架電内容メモ,
                                    WEBサイトURL, Instagram URL, その他SNS URL, 備考<br>
                                    文字コードはUTF-8を推奨します。「<span
                                        class="font-bold text-indigo-600 dark:text-indigo-400">架電状況名</span>」は「新規」「担当者不在」などの登録済みの名前を入力してください。
                                </p>
                                @error('csv_file')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-8 flex justify-start space-x-3">
                            <button type="submit"
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                {{ __('インポート実行') }}
                            </button>
                            <a href="{{ route('call-list.index') }}"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-500 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                {{ __('キャンセル') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> {{-- ★★★ これがファイルの最後にありますか？ ★★★ --}}