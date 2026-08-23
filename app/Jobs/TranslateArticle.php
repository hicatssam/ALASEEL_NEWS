<?php

namespace App\Jobs;

use App\Models\Article;
use App\Services\OpenAiArticleTranslator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TranslateArticle implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 180;

    public function __construct(public int $articleId, public ?string $locale = null) {}

    public function handle(OpenAiArticleTranslator $translator): void
    {
        $article = Article::find($this->articleId);
        if (!$article) return;

        $hash = $article->translationSourceHash();
        foreach ($this->locale ? [$this->locale] : ['en', 'fr'] as $locale) {
            $existing = $article->translations()->where('locale', $locale)->first();
            if ($existing?->source_hash === $hash) continue;

            $article->translations()->updateOrCreate(
                ['locale' => $locale],
                [...$translator->translate($article, $locale), 'source_hash' => $hash]
            );
        }
    }
}
