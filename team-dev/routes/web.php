<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\GeminiController;
use App\Http\Controllers\DailyChallengeController;

Route::middleware('guest')->group(function () {
    // サインアップ
    Route::get('register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('register', [AuthController::class, 'register']);

    // ログイン
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
});

Route::post('logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// 認証後のホーム
Route::get('/', [GeminiController::class, 'index'])->middleware('auth')->name('home');

// いったんコメントアウト
Route::get('/', [GeminiController::class, 'entry'])->middleware('auth')->name('entry');

// Route::get('/gemini/list-models', [GeminiController::class, 'listAvailableModels']);

Route::middleware('auth')->group(function () {
    // // カレンダー用データ取得
    // Route::get('/daily-challenges', [DailyChallengeController::class, 'index'])
    //     ->name('daily-challenges.index');

    // // 今日のチャレンジを「完了」にする
    // Route::post('/daily-challenges/complete', [DailyChallengeController::class, 'complete'])
    //     ->name('daily-challenges.complete');

    // ホーム画面
    Route::get('/', [DailyChallengeController::class, 'index'])->name('home');

    // Ajax: 完了ボタン
    Route::post(
        '/daily-challenges/complete',
        [DailyChallengeController::class, 'complete']
    )->name('daily-challenges.complete');

    // Ajax: お題を変えるボタン ← ここを追加
    Route::post(
        '/daily-challenges/change',
        [DailyChallengeController::class, 'change']
    )->name('daily-challenges.change');
});
