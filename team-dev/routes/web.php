<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\GeminiController;
use App\Http\Controllers\DailyChallengeController;
use App\Http\Controllers\WeightController;

// ゲスト用ルート
Route::middleware('guest')->group(function () {
    // サインアップ
    Route::get('register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('register', [AuthController::class, 'register']);

    // ログイン
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
});

// 認証が必要なルート
Route::middleware('auth')->group(function () {
    // ログアウト
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // ホーム画面（DailyChallengeController を使用）
    Route::get('/', [DailyChallengeController::class, 'index'])->name('home');

    // デバッグ用: GETでアクセスできるテストルート
    Route::get('/daily-challenges/test', function () {
        return response()->json([
            'message' => 'Daily challenges routes are accessible',
            'user_id' => auth()->id()
        ]);
    });

    // Ajax: 完了ボタン
    Route::post('/daily-challenges/complete', [DailyChallengeController::class, 'complete'])
        ->name('daily-challenges.complete');

    // Ajax: お題を変えるボタン
    Route::post('/daily-challenges/change', [DailyChallengeController::class, 'change'])
        ->name('daily-challenges.change');

    // 体重登録（POST）
    Route::post('/weight', [WeightController::class, 'store'])->name('weight.store');

    Route::get('/weight/chart', [WeightController::class, 'chart'])->name('weight.chart');

    // 月別データ取得（GET）
    // Route::get('/weights/data', [WeightController::class, 'data'])
    //     ->name('weights.data');
});
