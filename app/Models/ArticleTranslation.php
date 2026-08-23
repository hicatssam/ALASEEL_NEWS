<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleTranslation extends Model
{
    protected $fillable = [
        'locale', 'title', 'summary', 'content', 'seo_title',
        'seo_description', 'meta_keywords', 'source_hash',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
