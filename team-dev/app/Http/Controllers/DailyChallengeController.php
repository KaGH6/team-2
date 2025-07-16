<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\DailyChallenge;
use Gemini\Laravel\Facades\Gemini;
use Carbon\Carbon;

class DailyChallengeController extends Controller {
    /**
     * ホーム画面表示
     * — 今日のお題と FullCalendar 用イベントをビューに渡す
     */
    public function index() {
        $user  = Auth::user();
        $today = now()->format('Y-m-d'); // 日付のみ

        Log::info('Index method start', [
            'user_id' => $user->id,
            'today' => $today
        ]);

        // whereDateを使用して日付部分のみで検索
        $challenge = DailyChallenge::where('user_id', $user->id)
            ->whereDate('challenge_date', $today)
            ->first();

        if (!$challenge) {
            try {
                // 存在しない場合のみ作成
                $challenge = DailyChallenge::create([
                    'user_id' => $user->id,
                    'challenge_date' => $today, // Y-m-d形式で保存
                    'challenge_text' => $this->generateChallengeText(),
                    'is_completed' => false
                ]);

                Log::info('New challenge created', ['id' => $challenge->id]);
            } catch (\Illuminate\Database\QueryException $e) {
                // 並行処理で重複した場合
                Log::warning('Duplicate key error caught, fetching existing', [
                    'error' => $e->getMessage()
                ]);

                // 再度whereDateで検索
                $challenge = DailyChallenge::where('user_id', $user->id)
                    ->whereDate('challenge_date', $today)
                    ->first();

                if (!$challenge) {
                    throw new \Exception('Failed to create or fetch challenge');
                }
            }
        }

        // 全チャレンジ記録を取得（カレンダー用）
        $records = $user->dailyChallenges()
            ->orderBy('challenge_date', 'desc')
            ->get();

        // FullCalendar が扱いやすい形式にマッピング
        $events = $records->map(function ($r) {
            // challenge_dateを確実にY-m-d形式に変換
            $dateStr = $r->challenge_date instanceof \DateTime
                ? $r->challenge_date->format('Y-m-d')
                : date('Y-m-d', strtotime($r->challenge_date));

            return [
                'id'    => $dateStr,
                'title' => $r->challenge_text,
                'start' => $dateStr,
                'color' => $r->is_completed ? 'green' : 'blue',
                'extendedProps' => [
                    'content'      => $r->challenge_text,
                    'is_completed' => $r->is_completed,
                ],
            ];
        });

        return view('home', compact('challenge', 'events'));
    }

    /**
     * 「完了」ボタン押下時の処理
     * — 指定日の is_completed を更新し、JSONを返す
     */
    public function complete(Request $request) {
        try {
            $request->validate([
                'date' => 'required|date',
            ]);

            $user = Auth::user();
            $date = date('Y-m-d', strtotime($request->input('date')));

            // whereDateを使用
            $challenge = DailyChallenge::where('user_id', $user->id)
                ->whereDate('challenge_date', $date)
                ->first();

            if (!$challenge) {
                $challenge = DailyChallenge::create([
                    'user_id' => $user->id,
                    'challenge_date' => $date,
                    'challenge_text' => $this->generateChallengeText(),
                    'is_completed' => false
                ]);
            }

            // 完了状態に更新
            $challenge->update([
                'is_completed' => true,
                'completed_at' => now(),
            ]);

            // カレンダー上の該当イベントだけ差し替えるためのJSON
            return response()->json([
                'id'    => $date,
                'title' => $challenge->challenge_text,
                'start' => $date,
                'color' => 'green',
                'extendedProps' => [
                    'content'      => $challenge->challenge_text,
                    'is_completed' => true,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Complete method error: ' . $e->getMessage());
            return response()->json(['error' => 'エラーが発生しました'], 500);
        }
    }

    /**
     * 「お題を変える」ボタン押下時の処理
     * — 指定日の challenge_text を再生成して JSON を返す
     */
    public function change(Request $request) {
        try {
            $request->validate([
                'date' => 'nullable|date',
            ]);

            $user  = Auth::user();
            $date  = $request->input('date') ?
                date('Y-m-d', strtotime($request->input('date'))) :
                now()->toDateString();

            // whereDateを使用して既存のレコードを検索
            $challenge = DailyChallenge::where('user_id', $user->id)
                ->whereDate('challenge_date', $date)
                ->first();

            if (!$challenge) {
                // 新規作成
                $challenge = DailyChallenge::create([
                    'user_id' => $user->id,
                    'challenge_date' => $date,
                    'challenge_text' => $this->generateChallengeText(),
                    'is_completed' => false
                ]);
            }

            // 必ず新しいお題に更新（「お題を変える」ボタンが押されたので）
            $challenge->update([
                'challenge_text' => $this->generateChallengeText(),
                'is_completed' => false,
                'completed_at' => null
            ]);

            // カレンダー上の該当イベントだけ差し替えるためのJSON
            return response()->json([
                'id'    => $date,
                'title' => $challenge->challenge_text,
                'start' => $date,
                'color' => 'blue',
                'extendedProps' => [
                    'content'      => $challenge->challenge_text,
                    'is_completed' => false,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Change method error: ' . $e->getMessage());
            return response()->json(['error' => 'エラーが発生しました'], 500);
        }
    }

    /**
     * Gemini API を使って新しいチャレンジ文を生成する
     */
    protected function generateChallengeText(): string {
        try {
            Log::info('Generating challenge text with Gemini');
            // Gemini APIが設定されているか確認
            if (!config('gemini.api_key')) {
                Log::warning('Gemini API key not found, using fallback');
                throw new \Exception('Gemini API key not configured');
            }
            $systemPrompt = "あなたはフィットネスコーチ。ダイエット中の人が毎日楽しく取り組める、1日限りの簡単な運動を提案。条件：具体的な内容40字以内。";

            $response = Gemini::generativeModel("gemini-2.0-flash-001")->generateContent($systemPrompt)->text();

            Log::info('Gemini response received', ['text' => $response]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Gemini API error: ' . $e->getMessage());

            // Gemini APIでエラーが発生した場合のフォールバック
            $fallbackChallenges = [
                '階段を使って2階まで3往復しよう',
                'スクワット20回を3セット挑戦',
                '腕立て伏せ10回×2セット',
                'プランク30秒を3回キープ',
                '早歩きで15分間散歩しよう',
            ];

            $selected = $fallbackChallenges[array_rand($fallbackChallenges)];
            Log::info('Using fallback challenge', ['text' => $selected]);

            return $selected;
        }
    }
}
