<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Weight extends Model {
    protected $fillable = [
        'user_id',
        'weight',
        'recorded_at',
    ];

    protected $casts = [
        'weight' => 'float',
        'recorded_at' => 'date',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
