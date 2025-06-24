<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CallListController; // ファイルの先頭に追加
use App\Http\Controllers\Admin\DoNotCallListController; // ★ コントローラのuse宣言を追加
use App\Http\Controllers\CompanyController; // ファイルの先頭に追加
use App\Http\Controllers\PerformanceDataController; // ★ PerformanceDataController を use (なければ作成)
use App\Http\Controllers\AnalyticsController; // ★ AnalyticsController を use に追加







Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // プロフィール関連ルート
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/analytics/call-status', [AnalyticsController::class, 'callStatusAnalytics'])
         ->name('analytics.call_status');

       // パフォーマンスデータ関連 (Company にネストする例)
    Route::prefix('companies/{company}/performance-data')->name('performance_data.')->group(function () {
        Route::get('/', [PerformanceDataController::class, 'index'])->name('index'); // データ表示
        Route::get('/import', [PerformanceDataController::class, 'showImportForm'])->name('import.create'); // ★ CSVインポートフォーム表示
        Route::post('/import', [PerformanceDataController::class, 'processImport'])->name('import.store'); // CSVインポート処理
        // 他にもパフォーマンスデータ関連のルートがあればここに追加
    });
    Route::resource('companies', CompanyController::class);


     // ★★★ CSVインポート用ルート ★★★
    Route::get('/call-list-import', [CallListController::class, 'showImportForm'])->name('call-list.import.form');
    Route::post('/call-list-import', [CallListController::class, 'processImport'])->name('call-list.import.process');
    // ★★★ ここまで ★★★


    // 架電リスト関連ルート
    Route::resource('call-list', CallListController::class);
    Route::post('/call-list/{callList}/record-call', [CallListController::class, 'recordCall'])->name('call-list.record-call');
    Route::get('/call-list/{callListId}/histories', [CallListController::class, 'getHistories'])->name('call-list.histories'); // 重複していた 'auth' ミドルウェアを削除 (親グループで適用済みのため)

    // --- 管理者向け機能のルートグループ ---
    Route::middleware(['can:is-admin']) // ★ 認可ミドルウェアをここに適用 (authは親グループで適用済み)
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            // 架電禁止リスト管理
            Route::resource('dnc-lists', DoNotCallListController::class)->parameters([
                'dnc-lists' => 'doNotCallList'
            ]);
            // DNCリアルタイムチェック用ルート
            Route::get('/dnc-check', [DoNotCallListController::class, 'checkDncStatus'])->name('dnc.check');

            // 他の管理者向けルートもここに追加できます
            // 例: Route::get('/users', [UserController::class, 'index'])->name('users.index');
        });
    // --- 管理者向け機能のルートグループここまで ---
});

Route::middleware('auth')->group(function () {
    // ... (profile, call-list routes) ...

    // --- Admin Routes ---
    Route::prefix('admin')->name('admin.')->group(function () {
        // DNC Realtime Check (accessible by any authenticated user who can create call lists)
        // This route is inside prefix('admin') and name('admin.')
        // but NOT inside the 'can:is-admin' middleware group below.
        Route::get('/dnc-check', [DoNotCallListController::class, 'checkDncStatus'])->name('dnc.check');

        // DNC List Management CRUD (admin-only)
        Route::middleware(['can:is-admin']) // Gate applied only to this sub-group
            ->group(function () {
                Route::resource('dnc-lists', DoNotCallListController::class)->parameters([
                    'dnc-lists' => 'doNotCallList'
                ]);
                // Other strictly admin-only routes for DNC could go here
            });
    });
    // --- End Admin Routes ---
});

require __DIR__.'/auth.php';
