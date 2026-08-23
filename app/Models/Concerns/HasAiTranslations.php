<?php

namespace App\Models\Concerns;

use App\Jobs\TranslateContent;
use App\Models\ContentTranslation;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasAiTranslations
{
    public static function bootHasAiTranslations(): void
    {
        static::saved(function ($model): void {
            if (
                method_exists($model, 'shouldTranslateAutomatically') &&
                !$model->shouldTranslateAutomatically()
            ) {
                return;
            }

            $fields = $model->translatableFields();

            if ($model->wasRecentlyCreated || $model->wasChanged($fields)) {
                foreach (['en', 'fr'] as $locale) {
                    TranslateContent::dispatch($model::class, $model->getKey(), $locale)
                        ->afterCommit();
                }
            }
        });
    }

    abstract public function translatableFields(): array;

    public function contentTranslations(): MorphMany
    {
        return $this->morphMany(ContentTranslation::class, 'translatable');
    }

    public function translationSourceHash(): string
    {
        $source = [];

        foreach ($this->translatableFields() as $field) {
            $source[$field] = (string) ($this->getRawOriginal($field) ?? '');
        }

        return hash('sha256', json_encode(
            $source,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
    }

    public function getAttribute($key): mixed
    {
        $original = parent::getAttribute($key);

        if (!is_string($key) || !in_array($key, $this->translatableFields(), true)) {
            return $original;
        }

        $locale = app()->getLocale();

        if (!in_array($locale, ['en', 'fr'], true)) {
            return $original;
        }

        $translations = $this->relationLoaded('contentTranslations')
            ? $this->getRelation('contentTranslations')
            : $this->contentTranslations()->get();

        if (!$this->relationLoaded('contentTranslations')) {
            $this->setRelation('contentTranslations', $translations);
        }

        $translation = $translations
            ->where('status', 'completed')
            ->firstWhere('locale', $locale);
        $translated = data_get($translation?->data, $key);

        return filled($translated) ? $translated : $original;
    }
}
