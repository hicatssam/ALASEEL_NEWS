<?php

namespace App\Models;

use App\Models\Concerns\HasAiTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasAiTranslations, SoftDeletes;

    protected $with = ['contentTranslations'];

    protected $fillable = ['name', 'slug', 'status'];

    protected $casts = ['status' => 'boolean'];

    public function translatableFields(): array
    {
        return ['name'];
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_tag');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
