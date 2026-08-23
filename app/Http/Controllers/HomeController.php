<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\Article;
use App\Models\NewsletterSubscriber;
use App\Models\Notification;
use App\Models\Video;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $articleRelations = ['category', 'journalist', 'mainImageMedia'];

        $breakingNews = Article::breaking()
            ->with($articleRelations)
            ->latest('published_at')
            ->limit(5)
            ->get();

        $latestUpdates = Article::published()
            ->with($articleRelations)
            ->latest('published_at')
            ->limit(8)
            ->get();

        $articlesContent = Article::published()->ofType('article')->with($articleRelations)->latest('published_at')->limit(4)->get();
        $stories = Article::published()->ofType('story')->with($articleRelations)->latest('published_at')->limit(4)->get();
        $reports = Article::published()->ofType('report')->with($articleRelations)->latest('published_at')->limit(4)->get();
        $opinions = Article::published()->ofType('opinion')->with($articleRelations)->latest('published_at')->limit(6)->get();

        $featuredArticles = Article::featured()
            ->with($articleRelations)
            ->latest('published_at')
            ->limit(6)
            ->get();

        $latestArticles = Article::published()
            ->with($articleRelations)
            ->latest('published_at')
            ->limit(12)
            ->get();

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
            ->where(function ($query): void {
                $query->where('position', 'homepage')
                    ->orWhere(function ($query): void {
                        $query->where('position', 'video')
                            ->where('type', 'video');
                    });
            })
            ->latest()
            ->get();

        $sidebarAds = Advertisement::query()
            ->active()
            ->forPosition('sidebar')
            ->latest()
            ->get();

        return view('home', compact(
            'breakingNews',
            'latestUpdates',
            'articlesContent',
            'stories',
            'reports',
            'opinions',
            'featuredArticles',
            'latestArticles',
            'editorPicks',
            'trendingArticles',
            'featuredVideos',
            'sidebarAds',
            'homepageAds'
        ));
    }

    public function content(string $type)
    {
        abort_unless(array_key_exists($type, Article::contentTypes()) && $type !== 'news', 404);

        $articles = Article::published()
            ->ofType($type)
            ->with(['category', 'journalist', 'mainImageMedia'])
            ->latest('published_at')
            ->paginate(12);

        return view('content.index', [
            'articles' => $articles,
            'type' => $type,
            'typeLabel' => Article::contentTypes()[$type],
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
