<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Jobs\TranslateArticle;

class Article extends Model
{
    use SoftDeletes;

    /** Load saved translations with articles to avoid one query per card. */
    protected $with = ['translations'];

    protected $fillable = [
        'category_id',
        'journalist_id',
        'content_owner_name',
        'content_owner_photo',
        'user_id',
        'title',
        'slug',
        'summary',
        'content',
        'content_type',
        'main_image',
        'status',
        'is_breaking',
        'is_featured',
        'is_editor_pick',
        'is_indexable',
        'verification_status',
        'verified_by',
        'verified_at',
        'verification_notes',
        'main_image_media_id',
        'views',
        'published_at',
        'scheduled_at',
        'seo_title',
        'seo_description',
        'meta_keywords',
    ];

    protected $casts = [
        'is_breaking' => 'boolean',
        'is_featured' => 'boolean',
        'is_editor_pick' => 'boolean',
        'is_indexable' => 'boolean',
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'verified_at' => 'datetime',
        'views' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->title) . '-' . Str::random(6);
            }
        });

        static::saved(function (Article $article) {
            if ($article->wasRecentlyCreated || $article->wasChanged([
                'title', 'summary', 'content', 'seo_title', 'seo_description', 'meta_keywords',
            ])) {
                TranslateArticle::dispatch($article->id)->afterCommit();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function articleViews(): HasMany
    {
        return $this->hasMany(ArticleView::class);
    }

    public function journalist(): BelongsTo
    {
        return $this->belongsTo(Journalist::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function mainImageMedia(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'main_image_media_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'article_tag');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ArticleImage::class)->orderBy('sort_order');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ArticleRevision::class)->latest();
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ArticleTranslation::class);
    }

    public function translationSourceHash(): string
    {
        return hash('sha256', implode('|', array_map(
            fn ($field) => (string) $this->getRawOriginal($field),
            ['title', 'summary', 'content', 'seo_title', 'seo_description', 'meta_keywords']
        )));
    }

    protected function localizedValue(string $field): mixed
    {
        $original = $this->getRawOriginal($field);
        $locale = app()->getLocale();
        if ($locale === 'ar' || !in_array($locale, ['en', 'fr'], true)) return $original;

        $translation = $this->relationLoaded('translations')
            ? $this->translations->firstWhere('locale', $locale)
            : $this->translations()->where('locale', $locale)->first();

        return filled($translation?->{$field}) ? $translation->{$field} : $original;
    }

    public function getTitleAttribute(): ?string { return $this->localizedValue('title'); }
    public function getSummaryAttribute(): ?string { return $this->localizedValue('summary'); }
    public function getContentAttribute(): ?string { return $this->localizedValue('content'); }
    public function getSeoTitleAttribute(): ?string { return $this->localizedValue('seo_title'); }
    public function getSeoDescriptionAttribute(): ?string { return $this->localizedValue('seo_description'); }
    public function getMetaKeywordsAttribute(): ?string { return $this->localizedValue('meta_keywords'); }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function approvedComments(): HasMany
    {
        return $this->hasMany(Comment::class)->where('status', 'approved');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('content_type', $type);
    }

    public static function contentTypes(): array
    {
        return [
            'news' => 'خبر',
            'article' => 'مقال',
            'story' => 'قصة',
            'report' => 'تقرير',
            'opinion' => 'رأي',
        ];
    }

    public function getContentTypeLabelAttribute(): string
    {
        return static::contentTypes()[$this->content_type] ?? 'خبر';
    }

    public function getDisplayAuthorNameAttribute(): ?string
    {
        if (in_array($this->content_type, ['article', 'story', 'opinion'], true)) {
            return $this->content_owner_name ?: $this->journalist?->name;
        }

        return $this->journalist?->name;
    }

    public function getDisplayAuthorPhotoUrlAttribute(): ?string
    {
        if (in_array($this->content_type, ['article', 'story', 'opinion'], true)) {
            return $this->content_owner_photo_url ?: $this->journalist?->photo_url;
        }

        return $this->journalist?->photo_url;
    }

    public function scopeBreaking($query)
    {
        return $query->where('is_breaking', true)->where('status', 'published');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('status', 'published');
    }

    public function scopeEditorPick($query)
    {
        return $query->where('is_editor_pick', true)->where('status', 'published');
    }

    public function incrementViews(): void
    {
        $this->increment('views');
    }

    public function getReadingTimeAttribute(): int
    {
        $words = str_word_count(strip_tags($this->content));

        return (int) ceil($words / 200);
    }

    public function getMainImageUrlAttribute(): ?string
    {
        if ($this->mainImageMedia?->file_path) {
            return $this->mainImageMedia->url;
        }

        if (blank($this->main_image)) {
            return null;
        }

        if (filter_var($this->main_image, FILTER_VALIDATE_URL)) {
            return $this->main_image;
        }

        $path = ltrim($this->main_image, '/');

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return route('site.media', ['path' => $path]);
    }

    public function getContentOwnerPhotoUrlAttribute(): ?string
    {
        if (blank($this->content_owner_photo)) return null;
        if (filter_var($this->content_owner_photo, FILTER_VALIDATE_URL)) return $this->content_owner_photo;

        $path = preg_replace('#^/?(?:storage/app/public|public/storage|storage|public)/#', '', str_replace('\\\\', '/', $this->content_owner_photo));
        return route('site.media', ['path' => ltrim((string) $path, '/')]);
    }
}
