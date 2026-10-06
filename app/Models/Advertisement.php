<?php

namespace App\Models;

use App\Models\Concerns\HasAiTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Advertisement extends Model
{
    use HasAiTranslations, SoftDeletes;

    protected $with = ['contentTranslations'];

    protected $fillable = [
        'user_id',
        'title',
        'image',
        'link',
        'position',
        'type',
        'status',
        'starts_at',
        'ends_at',
        'views',
        'clicks',
    ];

    protected $appends = [
        'image_url',
        'is_expired',
    ];

    public function translatableFields(): array
    {
        return ['title'];
    }

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'views' => 'integer',
            'clicks' => 'integer',
        ];
    }

    /**
     * المستخدم الذي أضاف الإعلان.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * الإعلانات المفعلة والواقعة ضمن فترة العرض.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', true)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }

    /**
     * تصفية الإعلانات حسب موضع العرض.
     */
    public function scopeForPosition(
        Builder $query,
        string $position
    ): Builder {
        return $query->where('position', $position);
    }

    /**
     * إنشاء الرابط الكامل لصورة أو فيديو الإعلان.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (blank($this->image)) {
            return null;
        }

        $media = trim($this->image);

        if (
            str_starts_with($media, 'http://') ||
            str_starts_with($media, 'https://') ||
            str_starts_with($media, 'data:')
        ) {
            return $media;
        }

        $path = str_replace('\\', '/', $media);

        $path = preg_replace('#^.*?/storage/app/public/#', '', $path);
        $path = preg_replace('#^.*?/public/storage/#', '', $path);
        $path = preg_replace('#^/?(?:public/|storage/)+#', '', $path);

        if (blank($path)) {
            return null;
        }

        return route('site.media', ['path' => $path]);
    }

    /**
     * تحديد ما إذا كانت فترة الإعلان قد انتهت.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->ends_at?->isPast() ?? false;
    }
}
