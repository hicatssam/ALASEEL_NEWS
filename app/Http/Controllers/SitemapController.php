<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SitemapController extends Controller
{
    private function xml(string $content): Response
    {
        return response($content, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    public function index(): Response
    {
        $last = Carbon::parse(Article::without('translations')->published()->where('is_indexable', true)->max('updated_at') ?? now());
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ([route('sitemap.pages'), route('sitemap.articles'), route('sitemap.news')] as $url) {
            $xml .= '<sitemap><loc>'.$this->escape($url).'</loc><lastmod>'.$this->escape($last->toAtomString()).'</lastmod></sitemap>';
        }
        return $this->xml($xml.'</sitemapindex>');
    }

    public function pages(): Response
    {
        $pages = [
            route('home'),
            route('about'),
            route('contact'),
            route('markets.index'),
            route('videos.index'),
            route('content.index', 'article'),
            route('content.index', 'story'),
            route('content.index', 'report'),
            route('content.index', 'opinion'),
        ];

        Category::without('translations')
            ->active()
            ->orderBy('id')
            ->pluck('slug')
            ->each(fn (string $slug) => $pages[] = route('categories.show', $slug));
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($pages as $url) $xml .= '<url><loc>'.$this->escape($url).'</loc><changefreq>daily</changefreq><priority>0.8</priority></url>';
        return $this->xml($xml.'</urlset>');
    }

    public function articles(): Response
    {
        $articles = Article::without('translations')->with('mainImageMedia')->published()->where('is_indexable', true)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->latest('updated_at')->limit(50000)->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
        foreach ($articles as $article) {
            $xml .= '<url><loc>'.$this->escape(route('articles.show', $article->slug)).'</loc><lastmod>'.$this->escape($article->updated_at->toAtomString()).'</lastmod><changefreq>daily</changefreq><priority>0.9</priority>';
            if ($article->main_image_url) $xml .= '<image:image><image:loc>'.$this->escape($article->main_image_url).'</image:loc><image:title>'.$this->escape($article->getRawOriginal('title')).'</image:title></image:image>';
            $xml .= '</url>';
        }
        return $this->xml($xml.'</urlset>');
    }

    public function news(): Response
    {
        $articles = Article::without('translations')->published()->where('is_indexable', true)
            ->where('published_at', '>=', now()->subDays(2))->latest('published_at')->limit(1000)->get();
        $publication = $this->escape(Setting::get('site_name', 'وكالة الأصيل الإخبارية'));
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">';
        foreach ($articles as $article) {
            $xml .= '<url><loc>'.$this->escape(route('articles.show', $article->slug)).'</loc><news:news><news:publication><news:name>'.$publication.'</news:name><news:language>ar</news:language></news:publication><news:publication_date>'.$this->escape($article->published_at->toAtomString()).'</news:publication_date><news:title>'.$this->escape($article->getRawOriginal('title')).'</news:title></news:news></url>';
        }
        return $this->xml($xml.'</urlset>');
    }
}
