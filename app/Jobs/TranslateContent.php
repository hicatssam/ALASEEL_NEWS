<?php

namespace App\Jobs;

use App\Models\ContentTranslation;
use App\Services\OpenAiContentTranslator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class TranslateContent implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 180;

    public function __construct(
        public string $modelClass,
        public int|string $modelId,
        public string $locale,
    ) {}

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(OpenAiContentTranslator $translator): void
    {
        if (!is_subclass_of($this->modelClass, Model::class)) {
            throw new RuntimeException('Invalid translatable model class.');
        }

        $model = $this->modelClass::query()->find($this->modelId);

        if (!$model || !method_exists($model, 'translatableFields')) {
            return;
        }

        $hash = $model->translationSourceHash();
        $translation = $model->contentTranslations()
            ->where('locale', $this->locale)
            ->first();

        if ($translation?->source_hash === $hash && $translation->status === 'completed') {
            return;
        }

        $translation = $model->contentTranslations()->updateOrCreate(
            ['locale' => $this->locale],
            [
                'data' => $translation?->data ?? [],
                'source_hash' => $hash,
                'status' => 'processing',
                'error' => null,
            ]
        );

        try {
            $translation->update([
                'data' => $translator->translate($model, $this->locale),
                'source_hash' => $hash,
                'status' => 'completed',
                'error' => null,
                'translated_at' => now(),
            ]);

            Cache::forget('site_settings_'.$this->locale);
        } catch (Throwable $exception) {
            $translation->update([
                'status' => 'failed',
                'error' => mb_substr($exception->getMessage(), 0, 2000),
            ]);

            throw $exception;
        }
    }
}
