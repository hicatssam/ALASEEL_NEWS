<?php

namespace App\Models;

use App\Models\Concerns\HasAiTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journalist extends Model
{
    use HasAiTranslations, SoftDeletes;

    protected $with = ['contentTranslations'];

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'photo',
        'job_title',
        'bio',
        'facebook',
        'instagram',
        'youtube',
        'x_twitter',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    protected $appends = [
        'photo_url',
    ];

    public function translatableFields(): array
    {
        return ['name', 'job_title', 'bio'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function getArticleCountAttribute(): int
    {
        return $this->articles()->count();
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (blank($this->photo)) {
            return null;
        }

        $photo = trim(str_replace('\\', '/', (string) $this->photo));

        if (
            str_starts_with($photo, 'http://') ||
            str_starts_with($photo, 'https://') ||
            str_starts_with($photo, '//') ||
            str_starts_with($photo, 'data:')
        ) {
            return $photo;
        }

        $path = ltrim($photo, '/');
        $path = preg_replace('#^(?:storage/app/public/|public/|storage/)+#', '', $path);

        if (blank($path)) {
            return null;
        }

        return route('site.media', ['path' => $path]);
    }

    public function getTotalViewsAttribute(): int
    {
        return (int) $this->articles()->sum('views');
    }
}
