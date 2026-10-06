<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Journalist;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort' => ['nullable', 'in:newest,oldest,most_viewed'],
            'featured' => ['nullable', 'boolean'],
        ]);

        $q = trim($filters['q'] ?? '');
        $categoryId = $filters['category'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $sort = $filters['sort'] ?? 'newest';
        $featured = (bool) ($filters['featured'] ?? false);
        $hasSearch = $q !== ''
            || $categoryId
            || $dateFrom
            || $dateTo
            || $featured
            || $request->has('sort');

        $articlesQuery = Article::published()
            ->with(['category', 'journalist'])
            ->when($q !== '', fn ($query) => $query->where(function ($subQuery) use ($q) {
                $subQuery->where('title', 'like', "%{$q}%")
                    ->orWhere('summary', 'like', "%{$q}%")
                    ->orWhere('content', 'like', "%{$q}%");
            }))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->when($featured, fn ($query) => $query->where('is_featured', true))
            ->when($dateFrom, fn ($query) => $query->whereDate('published_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('published_at', '<=', $dateTo));

        match ($sort) {
            'oldest' => $articlesQuery->oldest('published_at'),
            'most_viewed' => $articlesQuery->orderByDesc('views')->latest('published_at'),
            default => $articlesQuery->latest('published_at'),
        };

        $articles = $hasSearch
            ? $articlesQuery->paginate(12)->withQueryString()
            : Article::query()->whereRaw('1 = 0')->paginate(12);

        $videos = $hasSearch && $q !== ''
            ? Video::published()->where('title', 'like', "%{$q}%")->latest()->limit(6)->get()
            : collect();
        $journalists = $hasSearch && $q !== ''
            ? Journalist::active()->where('name', 'like', "%{$q}%")->limit(6)->get()
            : collect();
        $matchedCategories = $hasSearch && $q !== ''
            ? Category::active()->where('name', 'like', "%{$q}%")->limit(6)->get()
            : collect();

        $categories = Category::active()->orderBy('name')->get(['id', 'name']);
        $total = $articles->total() + $videos->count() + $journalists->count() + $matchedCategories->count();

        return view('search.index', compact(
            'articles', 'videos', 'journalists', 'matchedCategories', 'categories',
            'total', 'q', 'categoryId', 'dateFrom', 'dateTo', 'sort', 'hasSearch'
        ));
    }
}
