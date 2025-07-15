<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\DailyChallenge;
use Gemini\Laravel\Facades\Gemini;

class DailyChallengeController extends Controller {
    /**
     * ホーム画面表示
     * — 今日のお題と FullCalendar 用イベントをビューに渡す
     */
    public function index() {
        $user  = Auth::user();
        $today = now()->toDateString();

        // 今日のチャレンジを取得 or 新規作成
        $challenge = DailyChallenge::where('user_id', $user->id)
            ->where('challenge_date', $today)
            ->first();

        if (!$challenge) {
            // 新規作成して保存
            $challenge = DailyChallenge::create([
                'user_id' => $user->id,
                'challenge_date' => $today,
                'challenge_text' => $this->generateChallengeText(),
                'is_completed' => false
            ]);

            Log::info('New challenge created', [
                'user_id' => $user->id,
                'date' => $today,
                'id' => $challenge->id
            ]);
        }

        // 全チャレンジ記録を取得（カレンダー用）
        $records = $user->dailyChallenges()
            ->get(['id', 'challenge_date', 'challenge_text', 'is_completed']);

        // FullCalendar が扱いやすい形式にマッピング
        $events = $records->map(function ($r) {
            return [
                'id'    => $r->challenge_date->format('Y-m-d'),
                'title' => $r->challenge_text,
                'start' => $r->challenge_date->format('Y-m-d'),
                'color' => $r->is_completed ? 'green' : 'blue',
                'extendedProps' => [
                    'content'      => $r->challenge_text,
                    'is_completed' => $r->is_completed,
                ],
            ];
        });

        // home.blade.php に $challenge と $events を渡す
        return view('home', compact('challenge', 'events'));
    }

    /**
     * 「完了」ボタン押下時の処理
     * — 指定日の is_completed を更新し、JSONを返す
     */
    public function complete(Request $request) {
        Log::info('Complete method called', $request->all());

        $request->validate([
            'date' => 'required|date',
        ]);

        $user = Auth::user();
        $date = $request->input('date');

        Log::info('Looking for challenge', [
            'user_id' => $user->id,
            'date' => $date
        ]);

        // 日付を確実に文字列形式で比較
        $challenge = DailyChallenge::where('user_id', $user->id)
            ->whereDate('challenge_date', $date)
            ->first();

        if (!$challenge) {
            // チャレンジが見つからない場合は作成
            Log::warning('Challenge not found, creating new one', [
                'user_id' => $user->id,
                'date' => $date
            ]);

            $challenge = DailyChallenge::create([
                'user_id' => $user->id,
                'challenge_date' => $date,
                'challenge_text' => $this->generateChallengeText(),
                'is_completed' => false
            ]);
        }

        $challenge->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        Log::info('Challenge completed', [
            'challenge_id' => $challenge->id,
            'date' => $date
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
    }

    /**
     * 「お題を変える」ボタン押下時の処理
     * — 指定日の challenge_text を再生成して JSON を返す
     */
    public function change(Request $request) {
        Log::info('Change method called', $request->all());

        $request->validate([
            'date' => 'nullable|date',
        ]);

        $user  = Auth::user();
        $date  = $request->input('date') ?? now()->toDateString();

        Log::info('Processing change', [
            'user_id' => $user->id,
            'date' => $date
        ]);

        // 日付を確実に文字列形式で比較
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
        } else {
            // 既存のチャレンジを更新
            $challenge->update([
                'challenge_text' => $this->generateChallengeText(),
                'is_completed' => false,
                'completed_at' => null
            ]);
        }

        Log::info('Challenge changed', [
            'challenge_id' => $challenge->id,
            'new_text' => $challenge->challenge_text
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
