<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentTranslation extends Model
{
    protected $fillable = [
        'locale',
        'data',
        'source_hash',
        'status',
        'error',
        'translated_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'translated_at' => 'datetime',
        ];
    }

    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }
}
