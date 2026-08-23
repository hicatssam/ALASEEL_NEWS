<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class OpenAiArticleTranslator
{
    public function translate(Article $article, string $locale): array
    {
        $language = ['en' => 'English', 'fr' => 'French'][$locale] ?? null;
        if (!$language) {
            throw new InvalidArgumentException('Unsupported translation language.');
        }

        $key = (string) config('services.openai.key');
        if ($key === '') {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $source = [
            'title' => $article->getRawOriginal('title'),
            'summary' => $article->getRawOriginal('summary') ?? '',
            'content' => $article->getRawOriginal('content'),
            'seo_title' => $article->getRawOriginal('seo_title') ?? '',
            'seo_description' => $article->getRawOriginal('seo_description') ?? '',
            'meta_keywords' => $article->getRawOriginal('meta_keywords') ?? '',
        ];

        $response = Http::withToken($key)
            ->acceptJson()
            ->timeout(120)
            ->retry(2, 1000)
            ->post(rtrim(config('services.openai.base_url'), '/').'/responses', [
                'model' => config('services.openai.translation_model', 'gpt-5-mini'),
                'store' => false,
                'instructions' => "You are the professional translator for Al-Aseel News Agency. Translate Arabic journalism into {$language}. Preserve facts, names, tone, HTML tags, URLs, image markup and paragraph structure exactly. Never add or remove information. Return only the requested structured object.",
                'input' => json_encode($source, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'max_output_tokens' => 16000,
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'article_translation',
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

        $translated = is_string($text) ? json_decode($text, true) : null;

        if (!is_array($translated) || blank($translated['title'] ?? null) || blank($translated['content'] ?? null)) {
            throw new RuntimeException('OpenAI returned an incomplete article translation.');
        }

        return $translated;
    }
}
