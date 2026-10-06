<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MediaFile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'file_name',
        'file_path',
        'file_type',
        'mime_type',
        'size',
        'alt_text',
        'caption',
        'folder',
    ];

    protected $appends = [
        'url',
        'formatted_size',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * المقالات التي تستخدم هذه الصورة كصورة رئيسية.
     */
    public function mainImageArticles(): HasMany
    {
        return $this->hasMany(
            Article::class,
            'main_image_media_id'
        );
    }

    public function scopeImages($query)
    {
        return $query->where('file_type', 'image');
    }

    public function scopeVideos($query)
    {
        return $query->where('file_type', 'video');
    }

    public function getUrlAttribute(): string
    {
        if (filter_var($this->file_path, FILTER_VALIDATE_URL)) {
            return $this->file_path;
        }

        $path = str_replace('\\', '/', trim((string) $this->file_path));
        $path = preg_replace('#^.*?/storage/app/public/#', '', $path);
        $path = preg_replace('#^.*?/public/storage/#', '', $path);
        $path = preg_replace('#^/?storage/#', '', $path);
        $path = preg_replace('#^/?public/#', '', $path);

        return route('site.media', [
            'path' => ltrim((string) $path, '/'),
        ]);
    }

    public function getFormattedSizeAttribute(): string
    {
        $size = $this->size ?? 0;

        if ($size >= 1073741824) {
            return number_format($size / 1073741824, 2) . ' GB';
        }

        if ($size >= 1048576) {
            return number_format($size / 1048576, 2) . ' MB';
        }

        if ($size >= 1024) {
            return number_format($size / 1024, 2) . ' KB';
        }

        return $size . ' B';
    }
}
