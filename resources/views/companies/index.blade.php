{{-- resources/views/companies/index.blade.php --}}
<x-app-layout>
    {{-- オプション: ヘッダーを定義する場合 (layouts.app.blade.php に <x-slot name="header"> があれば) --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('会社一覧') }}
        </h2>
    </x-slot>

    {{-- ここからがメインコンテンツになります --}}
    <div class="py-12"> {{-- Tailwind CSS のスペーシングクラスの例 --}}
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8"> {{-- Tailwind CSS のコンテナクラスの例 --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg"> {{-- Tailwind CSS のカード風スタイルの例 --}}
                <div class="p-6 text-gray-900 dark:text-gray-100"> {{-- Tailwind CSS のパディングとテキスト色の例 --}}

                    @if (session('success'))
                        {{-- Tailwind CSSでスタイリングされたアラートの例 (既存のcall_list/index.blade.phpなどを参考に) --}}
                        <div class="mb-4 p-4 bg-green-100 dark:bg-green-700 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-200 rounded relative" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="mb-3 text-right"> {{-- Tailwind CSS で右寄せ --}}
                        <a href="{{ route('companies.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150"> {{-- Tailwind CSS のボタンスタイルの例 --}}
                            {{ __('新規会社登録') }}
                        </a>
                    </div>

                    @if($companies->isEmpty())
                        <p>{{ __('登録されている会社はありません。') }}</p>
                    @else
                        <div class="overflow-x-auto"> {{-- テーブルが横にはみ出る場合のスクロール対応 --}}
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"> {{-- Tailwind CSS のテーブルスタイル --}}
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('ID') }}</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('絵文字') }}</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('会社名') }}</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('登録日') }}</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('操作') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($companies as $company)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $company->id }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $company->emoji_identifier }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $company->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $company->created_at->format('Y-m-d') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2"> {{-- Tailwind CSS でボタン間のスペース --}}
                                                {{-- 編集・削除ボタンはまだ機能実装前とのことなので、スタイリングは後で調整 --}}
                                                {{-- <a href="{{ route('companies.edit', $company) }}" class="text-yellow-600 hover:text-yellow-900">編集</a> --}}
                                                {{-- <form action="{{ route('companies.destroy', $company) }}" method="POST" class="inline"> @csrf @method('DELETE') <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('本当に削除しますか？');">削除</button> </form> --}}
                                                <a href="{{ route('performance_data.import.create', $company) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-200">{{ __('CSVインポート') }}</a>
                                                <a href="{{ route('performance_data.index', $company) }}" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-200">{{ __('データ表示') }}</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>