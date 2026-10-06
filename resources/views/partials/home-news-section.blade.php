@php
    $sectionItems = collect($items ?? [])->take(5)->values();
    $leadArticle = $sectionItems->first();
    $sideArticles = $sectionItems->skip(1);
@endphp

@if($sectionItems->isNotEmpty())
<section class="home-news-block{{ !empty($embedded) ? ' is-embedded' : '' }}">
    @if(empty($embedded))
    <div class="section-header">
        <div class="section-title">
            <i class="fa-solid {{ $icon ?? 'fa-newspaper' }}"></i>
            {{ $title }}
        </div>
    </div>
    @endif

    <div class="home-five-grid">
        <article class="home-five-lead article-card">
            <a href="{{ route('articles.show', $leadArticle->slug) }}" class="home-five-lead-image">
                @include('partials.article-image', [
                    'article' => $leadArticle,
                    'loading' => false,
                    'style' => 'width:100%;height:100%;object-fit:cover',
                ])
            </a>
            <div class="article-card-body">
                <div class="article-cat">{{ $leadArticle->category?->name }}</div>
                <h2 class="article-title home-five-lead-title">
                    <a href="{{ route('articles.show', $leadArticle->slug) }}">{{ $leadArticle->title }}</a>
                </h2>
                @if($leadArticle->summary)
                    <p class="home-five-summary">{{ Str::limit(strip_tags($leadArticle->summary), 150) }}</p>
                @endif
                <div class="article-meta">
                    @if($leadArticle->journalist)
                        <span><i class="fa-solid fa-user-pen"></i>{{ $leadArticle->journalist->name }}</span>
                    @endif
                    <span><i class="fa-solid fa-clock"></i>{{ $leadArticle->published_at?->diffForHumans() }}</span>
                </div>
            </div>
        </article>

        <div class="home-five-side">
            @foreach($sideArticles as $article)
                <article class="home-five-item article-card">
                    <a href="{{ route('articles.show', $article->slug) }}" class="home-five-thumb">
                        @include('partials.article-image', [
                            'article' => $article,
                            'style' => 'width:100%;height:100%;object-fit:cover',
                        ])
                    </a>
                    <div class="home-five-item-body">
                        <div class="article-cat">{{ $article->category?->name }}</div>
                        <h3 class="article-title">
                            <a href="{{ route('articles.show', $article->slug) }}">{{ Str::limit($article->title, 78) }}</a>
                        </h3>
                        <div class="article-meta">
                            <span><i class="fa-solid fa-clock"></i>{{ $article->published_at?->diffForHumans() }}</span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>

    <div class="home-news-action">
        <a href="{{ $moreUrl }}" class="btn btn-outline home-more-button">
            {{ $moreLabel ?? 'عرض المزيد' }}
            <i class="fa-solid fa-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}"></i>
        </a>
    </div>
</section>
@endif
