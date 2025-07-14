<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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

        // 今日のチャレンジを取得 or 新規作成（DBにはまだ保存しない）
        $challenge = DailyChallenge::firstOrNew(
            ['user_id' => $user->id, 'challenge_date' => $today],
            ['challenge_text' => $this->generateChallengeText()]
        );

        // 初回アクセスなら保存しておく
        if (! $challenge->exists) {
            $challenge->save();
        }

        // 全チャレンジ記録を取得（カレンダー用）
        $records = $user->dailyChallenges()
            ->get(['challenge_date', 'challenge_text', 'is_completed']);

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
        $request->validate([
            'date' => 'required|date',
        ]);

        $user = Auth::user();
        $date = $request->input('date');

        $challenge = $user->dailyChallenges()
            ->where('challenge_date', $date)
            ->firstOrFail();

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
    }

    /**
     * 「お題を変える」ボタン押下時の処理
     * — 指定日の challenge_text を再生成して JSON を返す
     */
    public function change(Request $request) {
        $request->validate([
            'date' => 'nullable|date',
        ]);

        $user  = Auth::user();
        $date  = $request->input('date') ?? now()->toDateString();

        // レコードを取得 or 新規インスタンス
        $challenge = DailyChallenge::firstOrNew(
            ['user_id' => $user->id, 'challenge_date' => $date]
        );

        // 新しいお題テキストを取得＆完了フラグをリセット
        $challenge->challenge_text = $this->generateChallengeText();
        $challenge->is_completed   = false;
        $challenge->completed_at   = null;
        $challenge->save();

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
        $systemPrompt = "あなたはフィットネスコーチ。ダイエット中の人が毎日楽しく取り組める、1日限りの簡単な運動を提案。条件：具体的な内容40字以内。";

        // Geminiへのコマンドを組み立て
        $toGeminiCommand = $systemPrompt;

        $response = Gemini::generativeModel("gemini-1.5-flash")->generateContent($toGeminiCommand)->text();

        return $response;
    }
}
