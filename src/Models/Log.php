<?php

namespace Penobit\SuperModels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model {
    use HasFactory;

    protected $fillable = [
        'action',
        'user_id',
        'model_type',
        'model_id',
        'content',
        'ip',
        'user_agent',
        'params',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'params' => 'object',
        'created_at' => 'date:Y-m-d H:i',
    ];

    public function model() {
        return $this->morphTo();
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
