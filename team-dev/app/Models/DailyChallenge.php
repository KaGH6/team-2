<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyChallenge extends Model {
    // ここに許可するカラムを列挙
    protected $fillable = [
        'user_id',
        'challenge_date',
        'challenge_text',
        'is_completed',
        'completed_at',
    ];

    // もし逆に「何もガードしない」なら guarded を空配列に
    // protected $guarded = [];

    // ここを追記
    protected $casts = [
        'challenge_date' => 'date',      // ← Carbon インスタンスになります
        'completed_at'   => 'datetime',  // 必要なら
        'is_completed'   => 'boolean',
    ];

    // 日付ミューテーターを追加
    public function setChallengeDataAttribute($value) {
        // 常にY-m-d形式で保存
        $this->attributes['challenge_date'] = date('Y-m-d', strtotime($value));
    }

    // ユーザーとのリレーション
    public function user() {
        return $this->belongsTo(User::class);
    }
}
