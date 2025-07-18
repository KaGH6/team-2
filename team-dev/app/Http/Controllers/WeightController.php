<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Weight;

class WeightController extends Controller {
    // 体重管理ページ表示
    public function index() {
        // // 月のデータを取得
        // $month = now()->format('Y-m');
        // $weightData = Auth::user()
        //     ->weights()
        //     ->where('date', 'like', "$month%")
        //     ->orderBy('date')
        //     ->get(['date', 'weight']);

        return view('weight');
    }

    public function getWeights(Request $request) {
        // 月のデータを取得
        $month = now()->format('Y-m');
        $weightData = Auth::user()
            ->weights()
            ->where('date', 'like', "$month%")
            ->orderBy('date')
            ->get(['date', 'weight']);

        return response()->json($weightData);
    }

    public function store(Request $request) {
        try {
            // リクエストログ
            Log::info('Weight store request', $request->all());

            $request->validate([
                'date' => 'required|date',
                'weight' => 'required|numeric|min:0|max:999.9',
            ]);

            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'ユーザーが認証されていません'
                ], 401);
            }

            // 同じ日付のデータは上書き
            $weight = Weight::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'recorded_at' => Carbon::parse($request->date)->format('Y-m-d H:i:s')
                ],
                ['weight' => $request->weight]
            );

            Log::info('Weight saved', ['weight' => $weight->toArray()]);

            // ユーザーモデルにweightsリレーションが定義されているか確認
            if (!method_exists($user, 'weights')) {
                Log::error('User model does not have weights relationship');

                // リレーションがない場合の代替処理
                $allWeights = Weight::where('user_id', $user->id)
                    ->orderBy('recorded_at', 'asc')
                    ->get(['recorded_at', 'weight'])
                    ->map(function ($record) {
                        return [
                            'date' => $record->recorded_at->format('Y-m-d'),
                            'weight' => (float) $record->weight
                        ];
                    });
            } else {
                // 保存後、最新のデータを含む全データを返す
                $allWeights = $user->weights()
                    ->orderBy('recorded_at', 'asc')
                    ->get(['recorded_at', 'weight'])
                    ->map(function ($record) {
                        return [
                            'date' => $record->recorded_at->format('Y-m-d'),
                            'weight' => (float) $record->weight
                        ];
                    });
            }

            return response()->json([
                'success' => true,
                'weight' => $weight,
                'allWeights' => $allWeights
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);

            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラー',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Weight store error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'エラーが発生しました: ' . $e->getMessage()
            ], 500);
        }
    }

    public function chart() {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'ユーザーが認証されていません'
                ], 401);
            }

            // ユーザーモデルにweightsリレーションが定義されているか確認
            if (!method_exists($user, 'weights')) {
                $weights = Weight::where('user_id', $user->id)
                    ->orderBy('recorded_at', 'asc')
                    ->get(['recorded_at', 'weight']);
            } else {
                $weights = $user->weights()
                    ->orderBy('recorded_at', 'asc')
                    ->get(['recorded_at', 'weight']);
            }

            $weightData = $weights->map(function ($record) {
                return [
                    'date' => $record->recorded_at->format('Y-m-d'),
                    'weight' => (float) $record->weight
                ];
            });

            return view('weight-chart', compact(['weights' => $weightData]));
        } catch (\Exception $e) {
            Log::error('Weight chart error: ' . $e->getMessage());

            // ビューを返す場合のエラーハンドリング
            return view('weight-chart')->with('error', 'データの取得に失敗しました');
        }
    }
}
