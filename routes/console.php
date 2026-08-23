<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\TranslateArticle;
use App\Jobs\TranslateContent;
use App\Models\AboutPage;
use App\Models\Advertisement;
use App\Models\Article;
use App\Models\Category;
use App\Models\Journalist;
use App\Models\LiveBroadcast;
use App\Models\LiveStream;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\TeamMember;
use App\Models\Video;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('articles:translate {--locale=}', function () {
    $locale = $this->option('locale') ?: null;
    if ($locale && !in_array($locale, ['en', 'fr'], true)) {
        $this->error('Supported locales: en, fr');
        return 1;
    }

    Article::query()->select('id')->chunkById(100, function ($articles) use ($locale) {
        foreach ($articles as $article) TranslateArticle::dispatch($article->id, $locale);
    });

    $this->info('Article translations have been queued.');
    return 0;
})->purpose('Translate all articles with OpenAI');

Artisan::command('content:translate {--locale=}', function () {
    $locale = $this->option('locale') ?: null;

    if ($locale && !in_array($locale, ['en', 'fr'], true)) {
        $this->error('Supported locales: en, fr');
        return 1;
    }

    $models = [
        Category::class,
        Tag::class,
        Journalist::class,
        TeamMember::class,
        AboutPage::class,
        Video::class,
        Advertisement::class,
        LiveStream::class,
        LiveBroadcast::class,
    ];

    foreach ($models as $modelClass) {
        $modelClass::query()->select('id')->chunkById(100, function ($records) use ($modelClass, $locale) {
            foreach ($records as $record) {
                foreach ($locale ? [$locale] : ['en', 'fr'] as $targetLocale) {
                    TranslateContent::dispatch($modelClass, $record->id, $targetLocale);
                }
            }
        });
    }

    Setting::query()
        ->whereIn('key', Setting::TRANSLATABLE_KEYS)
        ->select('id')
        ->chunkById(100, function ($records) use ($locale) {
            foreach ($records as $record) {
                foreach ($locale ? [$locale] : ['en', 'fr'] as $targetLocale) {
                    TranslateContent::dispatch(Setting::class, $record->id, $targetLocale);
                }
            }
        });

    $this->info('CMS content translations have been queued.');
    return 0;
})->purpose('Translate all non-article CMS content with OpenAI');

Artisan::command('translations:sync {--locale=}', function () {
    $locale = $this->option('locale') ?: null;

    if ($locale && !in_array($locale, ['en', 'fr'], true)) {
        $this->error('Supported locales: en, fr');
        return 1;
    }

    $parameters = $locale ? ['--locale' => $locale] : [];

    $articleExit = Artisan::call('articles:translate', $parameters);
    $contentExit = Artisan::call('content:translate', $parameters);

    $this->output->write(Artisan::output());

    return max($articleExit, $contentExit);
})->purpose('Queue translations for all old site content');
