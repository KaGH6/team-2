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

    // ユーザーとのリレーション
    public function user() {
        return $this->belongsTo(User::class);
    }
}
