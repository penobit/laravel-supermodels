<?php

namespace Penobit\SuperModels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meta extends Model {
    use HasFactory;

    protected $table = 'metadata';

    protected $fillable = [
        'model_type',
        'model_id',
        'name',
        'value',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'date:Y-m-d H:i',
        'updated_at' => 'date:Y-m-d H:i',
    ];

    public function __construct() {
        $this->table = config('supermodels.tables.meta');
    }

    public function model() {
        return $this->morphTo();
    }
}
