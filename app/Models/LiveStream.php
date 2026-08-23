<?php

namespace App\Models;

use App\Models\Concerns\HasAiTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LiveStream extends Model
{
    use HasAiTranslations, HasFactory;

    protected $with = ['contentTranslations'];

    protected $fillable = [
        'title',
        'embed_url',
        'description',
        'viewers_label',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function translatableFields(): array
    {
        return ['title', 'description', 'viewers_label'];
    }

    /**
     * Return the currently active stream or null.
     */
    public static function active(): ?self
    {
        return static::where('is_active', true)->latest()->first();
    }
}
