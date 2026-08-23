<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class OpenAiContentTranslator
{
    public function translate(Model $model, string $locale): array
    {
        $language = ['en' => 'English', 'fr' => 'French'][$locale] ?? null;

        if (!$language) {
            throw new InvalidArgumentException('Unsupported translation language.');
        }

        $key = (string) config('services.openai.key');

        if ($key === '') {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $source = [];

        foreach ($model->translatableFields() as $field) {
            $source[$field] = (string) ($model->getRawOriginal($field) ?? '');
        }

        $response = Http::withToken($key)
            ->acceptJson()
            ->timeout(120)
            ->retry(2, 1000)
            ->post(rtrim(config('services.openai.base_url'), '/').'/responses', [
                'model' => config('services.openai.translation_model', 'gpt-5-mini'),
                'store' => false,
                'instructions' => "You are the professional translator for Al-Aseel News Agency. Translate the supplied Arabic CMS content into {$language}. Preserve facts, proper names, tone, HTML tags, URLs and paragraph structure. Transliterate a person's name naturally when needed. Never translate or alter technical identifiers. Never add or remove information. Return only the requested structured object.",
                'input' => json_encode($source, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'max_output_tokens' => 16000,
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'cms_content_translation',
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'properties' => collect(array_keys($source))->mapWithKeys(
                                fn ($field) => [$field => ['type' => 'string']]
                            )->all(),
                            'required' => array_keys($source),
                        ],
                    ],
                ],
            ]);

        $response->throw();

        $payload = $response->json();
        $text = null;
        $refusal = null;

        foreach ((array) data_get($payload, 'output', []) as $outputItem) {
            if (data_get($outputItem, 'type') !== 'message') {
                continue;
            }

            foreach ((array) data_get($outputItem, 'content', []) as $contentItem) {
                $contentType = data_get($contentItem, 'type');

                if ($contentType === 'output_text' && is_string(data_get($contentItem, 'text'))) {
                    $text = data_get($contentItem, 'text');
                    break 2;
                }

                if ($contentType === 'refusal') {
                    $refusal = data_get($contentItem, 'refusal');
                }
            }
        }

        if (!is_string($text) || trim($text) === '') {
            throw new RuntimeException(
                filled($refusal)
                    ? 'OpenAI refused the translation request: '.$refusal
                    : 'OpenAI returned no translation text. Response status: '.data_get($payload, 'status', 'unknown')
            );
        }

        $translated = json_decode($text, true);

        if (!is_array($translated)) {
            throw new RuntimeException('OpenAI returned an invalid content translation.');
        }

        foreach (array_keys($source) as $field) {
            if (!array_key_exists($field, $translated) || !is_string($translated[$field])) {
                throw new RuntimeException("OpenAI omitted the translated field: {$field}");
            }
        }

        return $translated;
    }
}
