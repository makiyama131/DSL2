<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('ホーム') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6">
                        {{ __("ようこそ、") }} {{ Auth::user()->name }} {{ __("さん！") }}
                        <span class="text-sm text-gray-500 dark:text-gray-400">({{ Auth::user()->role }})</span> {{--
                        ロール表示（デバッグ用など） --}}
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {{-- 架電リスト関連 (全認証ユーザー向け) --}}
                        <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-lg shadow">
                            <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-3">{{ __('架電リスト') }}
                            </h4>
                            <ul class="space-y-2">
                                <li>
                                    <a href="{{ route('call-list.index') }}"
                                        class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ __('架電リスト一覧・操作') }}
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('call-list.import.form') }}"
                                        class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ __('架電リストをCSVでインポート') }}
                                    </a>
                                </li>
                            </ul>
                        </div>

                        {{-- 会社・運用データ関連 (全認証ユーザー向け) --}}
                        <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-lg shadow">
                            <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-3">{{ __('会社・運用データ') }}
                            </h4>
                            <ul class="space-y-2">
                                <li>
                                    <a href="{{ route('companies.index') }}"
                                        class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ __('会社一覧') }}
                                    </a>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">（各会社からパフォーマンスデータ表示・インポートへ）
                                    </p>
                                </li>
                                {{-- 運用アナリティクス画面へのリンクはここに配置 --}}
                                <li>
                                    <a href="{{ route('analytics.call_status') }}"
                                        class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ __('架電状況アナリティクス') }}
                                    </a>
                                </li>
                            </ul>
                        </div>

                        {{-- 管理者向け機能 --}}
                        @can('is-admin') {{-- ★ 'is-admin' Gateで囲む --}}
                            <div
                                class="bg-red-50 dark:bg-red-900 p-6 rounded-lg shadow border border-red-200 dark:border-red-700">
                                {{-- 背景色などで管理者用とわかるように（任意） --}}
                                <h4 class="text-md font-semibold text-red-800 dark:text-red-200 mb-3">{{ __('管理者機能') }}</h4>
                                <ul class="space-y-2">
                                    <li>
                                        <a href="{{ route('admin.dnc-lists.index') }}"
                                            class="text-red-600 dark:text-red-400 hover:underline">
                                            {{ __('架電禁止リスト管理') }}
                                        </a>
                                    </li>
                                    <li>
                                        {{-- TODO: アカウント作成画面のルートが定義されたら修正 --}}
                                        <a href="{{ route('register') }}"
                                            class="text-red-600 dark:text-red-400 hover:underline">
                                            {{ __('新規アカウント作成') }}
                                        </a>
                                    </li>
                                    {{-- 他の管理者機能があればここに追加 --}}
                                </ul>
                            </div>
                        @endcan

                        {{-- アカウント関連 (全認証ユーザー向け) --}}
                        <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-lg shadow">
                            <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-3">{{ __('アカウント') }}
                            </h4>
                            <ul class="space-y-2">
                                <li>
                                    <a href="{{ route('profile.edit') }}"
                                        class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ __('プロフィール編集') }}
                                    </a>
                                </li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <a href="{{ route('logout') }}"
                                            onclick="event.preventDefault(); this.closest('form').submit();"
                                            class="text-gray-600 dark:text-gray-400 hover:underline">
                                            {{ __('ログアウト') }}
                                        </a>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>