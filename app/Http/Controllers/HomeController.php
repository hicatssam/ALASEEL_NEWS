<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\Article;
use App\Models\Category;
use App\Models\NewsletterSubscriber;
use App\Models\Notification;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function index()
    {
        $articleRelations = ['category', 'journalist', 'mainImageMedia'];

        $latestUpdates = Article::published()
            ->with($articleRelations)
            ->latest('published_at')
            ->limit(8)
            ->get();

        $articlesContent = Article::published()->ofType('article')->with($articleRelations)->latest('published_at')->limit(5)->get();
        $stories = Article::published()->ofType('story')->with($articleRelations)->latest('published_at')->limit(5)->get();
        $articlesAndStories = Article::published()
            ->whereIn('content_type', ['article', 'story'])
            ->with($articleRelations)
            ->latest('published_at')
            ->limit(5)
            ->get();
        $reports = Article::published()->ofType('report')->with($articleRelations)->latest('published_at')->limit(5)->get();
        $opinions = Article::published()->ofType('opinion')->with($articleRelations)->latest('published_at')->limit(6)->get();
        $opinionAndArticles = Article::published()
            ->whereIn('content_type', ['opinion', 'article'])
            ->with($articleRelations)
            ->latest('published_at')
            ->limit(6)
            ->get();

        $featuredArticles = Article::featured()
            ->with($articleRelations)
            ->latest('published_at')
            ->limit(5)
            ->get();

        $latestArticles = Article::published()
            ->whereNotIn('id', $featuredArticles->pluck('id'))
            ->with($articleRelations)
            ->latest('published_at')
            ->limit(5)
            ->get();

        $categorySections = Category::query()
            ->active()
            ->root()
            ->whereHas('articles', fn ($query) => $query->published())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->sortBy(function (Category $category): int {
                $name = Str::lower($category->name.' '.$category->slug);

                return Str::contains($name, ['محلي', 'local']) ? 0 : 1;
            })
            ->values()
            ->map(function (Category $category) use ($articleRelations): Category {
                $category->setRelation(
                    'articles',
                    Article::published()
                        ->where('category_id', $category->id)
                        ->with($articleRelations)
                        ->latest('published_at')
                        ->limit(5)
                        ->get()
                );

                return $category;
            });

        $localCategorySection = $categorySections->first(function (Category $category): bool {
            $name = Str::lower($category->name.' '.$category->slug);

            return Str::contains($name, ['محلي', 'local']);
        });
        $remainingCategorySections = $categorySections
            ->reject(fn (Category $category): bool => $localCategorySection?->is($category) ?? false)
            ->values();

        $editorPicks = Article::editorPick()
            ->with($articleRelations)
            ->latest('published_at')
            ->limit(4)
            ->get();

        $trendingArticles = Article::published()
            ->with($articleRelations)
            ->orderByDesc('views')
            ->latest('published_at')
            ->limit(8)
            ->get();

        $featuredVideos = Video::published()
            ->featured()
            ->with('category')
            ->latest()
            ->limit(4)
            ->get();

        $homepageAds = Advertisement::query()
            ->active()
            ->forPosition('homepage')
            ->latest()
            ->get();

        $sidebarAds = Advertisement::query()
            ->active()
            ->forPosition('sidebar')
            ->latest()
            ->get();

        return view('home', compact(
            'latestUpdates',
            'articlesContent',
            'stories',
            'articlesAndStories',
            'reports',
            'opinions',
            'opinionAndArticles',
            'featuredArticles',
            'latestArticles',
            'localCategorySection',
            'remainingCategorySections',
            'editorPicks',
            'trendingArticles',
            'featuredVideos',
            'sidebarAds',
            'homepageAds'
        ));
    }

    public function content(string $type)
    {
        $isArticlesAndStories = $type === 'articles-stories';
        $isOpinionsAndArticles = $type === 'opinions-articles';

        abort_unless(
            $isArticlesAndStories
                || $isOpinionsAndArticles
                || (array_key_exists($type, Article::contentTypes()) && $type !== 'news'),
            404
        );

        $articlesQuery = Article::published();

        if ($isArticlesAndStories) {
            $articlesQuery->whereIn('content_type', ['article', 'story']);
        } elseif ($isOpinionsAndArticles) {
            $articlesQuery->whereIn('content_type', ['opinion', 'article']);
        } else {
            $articlesQuery->ofType($type);
        }

        $articles = $articlesQuery
            ->with(['category', 'journalist', 'mainImageMedia'])
            ->latest('published_at')
            ->paginate(12);

        $typeLabel = match (true) {
            $isArticlesAndStories => 'المقالات والقصص',
            $isOpinionsAndArticles => 'الآراء والمقالات',
            default => Article::contentTypes()[$type],
        };

        return view('content.index', [
            'articles' => $articles,
            'type' => $type,
            'typeLabel' => $typeLabel,
        ]);
    }

    public function subscribeNewsletter(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $subscriber = NewsletterSubscriber::firstOrCreate(
            ['email' => $validated['email']],
            [
                'status' => 'active',
                'subscribed_at' => now(),
            ]
        );

        if ($subscriber->wasRecentlyCreated) {
            Notification::create([
                'title' => 'مشترك جديد في النشرة البريدية',
                'message' => $validated['email'] . ' اشترك في النشرة البريدية.',
                'type' => 'newsletter',
            ]);
        }

        return back()->with('success', 'تم الاشتراك في النشرة البريدية بنجاح.');
    }

    public function unsubscribeNewsletter(Request $request)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired unsubscribe link.');
        }

        NewsletterSubscriber::where('email', $request->query('email'))->update([
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);

        return view('newsletter.unsubscribed');
    }
}
