<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Log;

class GeminiController extends Controller {
    public function index() {
        return view('home');
    }

    public function entry() {
        // ダイエット中の人向けに簡単な運動プランを提案するプロンプトを追加
        // $systemPrompt = "あなたはフィットネスコーチ。ダイエット中の人が毎日楽しく取り組める、1日限りの簡単な運動を提案。条件：具体的な内容40字以内。";
        $systemPrompt = "1日限りの簡単な運動を提案。条件：具体的な内容40字以内。";

        // Geminiへのコマンドを組み立て
        $toGeminiCommand = $systemPrompt;

        $response = Gemini::generativeModel("gemini-2.0-flash-001")->generateContent($toGeminiCommand)->text();
        // $response = Gemini::geminiPro()
        //     ->generateContent($toGeminiCommand)
        //     ->text();

        $result = [
            'task'    => $systemPrompt,
            'content' => Str::markdown($response),
        ];

        return view('home', compact('result'));
    }

    public function listAvailableModels() {
        try {
            $models = Gemini::models()->list(); // Gemini ファサードから直接リストを取得
            Log::info('Available Gemini Models: ' . json_encode($models->models, JSON_PRETTY_PRINT));
            dd($models->models); // ブラウザで確認するために dd() を使用
        } catch (\Exception $e) {
            Log::error('Failed to list Gemini models: ' . $e->getMessage());
            return "Failed to list models: " . $e->getMessage();
        }
    }
}
