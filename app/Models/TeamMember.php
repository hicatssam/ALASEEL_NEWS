<?php

namespace App\Models;

use App\Models\Concerns\HasAiTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TeamMember extends Model
{
    use HasAiTranslations, HasFactory, SoftDeletes;

    protected $with = ['contentTranslations'];

    protected $fillable = [
        'name',
        'job_title',
        'image',
        'display_order',
        'is_active',
    ];

    protected $appends = [
        'image_url',
    ];

    public function translatableFields(): array
    {
        return ['name', 'job_title'];
    }

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('display_order')
            ->orderBy('id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (blank($this->image)) {
            return null;
        }

        $image = trim(str_replace('\\', '/', (string) $this->image));

        if (
            Str::startsWith($image, [
                'http://',
                'https://',
                '//',
                'data:',
            ])
        ) {
            return $image;
        }

        $path = ltrim($image, '/');
        $path = preg_replace(
            '#^(?:storage/app/public/|public/|storage/)+#',
            '',
            $path
        );

        if (blank($path)) {
            return null;
        }

        return route('site.media', ['path' => $path]);
    }
}
