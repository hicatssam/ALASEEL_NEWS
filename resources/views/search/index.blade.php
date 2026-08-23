@extends('layouts.app')
@push('meta')
<meta name="robots" content="noindex, follow">
@endpush
@section('title', __('messages.search_page_title', ['q' => $q]))

@push('styles')
<style>
.search-panel{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:22px;margin-bottom:26px}
.search-filters{display:grid;grid-template-columns:minmax(230px,2fr) repeat(4,minmax(130px,1fr));gap:10px;align-items:end}
.search-field label{display:block;margin-bottom:6px;color:rgba(255,255,255,.68);font-size:12px;font-weight:700}
.search-control{width:100%;height:43px;padding:0 12px;border:1px solid var(--border);border-radius:8px;background:var(--surface2);color:var(--white);font-family:inherit;outline:none}
.search-control:focus{border-color:var(--gold)}
.search-actions{display:flex;gap:8px;margin-top:14px;flex-wrap:wrap}
.search-summary{margin-top:14px;color:rgba(255,255,255,.68);font-size:13px}
.search-empty{text-align:center;padding:64px 18px;color:rgba(255,255,255,.55)}
.search-empty i{display:block;margin-bottom:14px;color:var(--gold);font-size:44px;opacity:.6}
.search-mini-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:28px}
.search-mini{display:flex;align-items:center;gap:10px;padding:14px;border:1px solid var(--border);border-radius:10px;background:var(--surface)}
@media(max-width:1050px){.search-filters{grid-template-columns:repeat(2,minmax(0,1fr))}.search-field:first-child{grid-column:1/-1}}
@media(max-width:640px){.search-filters,.search-mini-grid{grid-template-columns:1fr}.search-field:first-child{grid-column:auto}.search-panel{padding:15px}}
</style>
@endpush

@section('content')
<div class="main-content"><div class="container">
  <div class="search-panel">
    <form action="{{ route('search') }}" method="GET">
      <div class="search-filters">
        <div class="search-field"><label>{{ __('messages.search_placeholder') }}</label><input class="search-control" type="search" name="q" value="{{ $q }}" placeholder="{{ __('messages.search_placeholder') }}"></div>
        <div class="search-field"><label>القسم</label><select class="search-control" name="category"><option value="">كل الأقسام</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)$categoryId === (string)$category->id)>{{ $category->name }}</option>@endforeach</select></div>
        <div class="search-field"><label>من تاريخ</label><input class="search-control" type="date" name="date_from" value="{{ $dateFrom }}"></div>
        <div class="search-field"><label>إلى تاريخ</label><input class="search-control" type="date" name="date_to" value="{{ $dateTo }}"></div>
        <div class="search-field"><label>الترتيب</label><select class="search-control" name="sort"><option value="newest" @selected($sort==='newest')>الأحدث</option><option value="oldest" @selected($sort==='oldest')>الأقدم</option><option value="most_viewed" @selected($sort==='most_viewed')>الأكثر مشاهدة</option></select></div>
      </div>
      <div class="search-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> بحث وتصفية</button><a class="btn btn-outline" href="{{ route('search') }}"><i class="fa-solid fa-rotate-left"></i> مسح الفلاتر</a></div>
    </form>
    @error('date_to')<div class="alert alert-error" style="margin-top:12px">{{ $message }}</div>@enderror
    @if($hasSearch)<p class="search-summary">تم العثور على <strong style="color:var(--gold)">{{ number_format($total) }}</strong> نتيجة.</p>@endif
  </div>

  @if($articles->count())
    <div class="section-header"><div class="section-title"><i class="fa-solid fa-newspaper"></i> المقالات ({{ $articles->total() }})</div></div>
    <div class="articles-grid" style="margin-bottom:28px">
      @foreach($articles as $article)
      <article class="article-card">
        <a class="article-card-img" href="{{ route('articles.show', $article->slug) }}">
          @include('partials.article-image', ['article' => $article])
        </a>
        <div class="article-card-body"><div class="article-cat">{{ $article->category?->name }}</div><div class="article-title"><a href="{{ route('articles.show', $article->slug) }}">{{ Str::limit($article->title, 90) }}</a></div><div class="article-meta"><span><i class="fa-solid fa-clock"></i>{{ $article->published_at?->diffForHumans() }}</span><span><i class="fa-solid fa-eye"></i>{{ number_format($article->views) }}</span></div></div>
      </article>
      @endforeach
    </div>
    {{ $articles->links() }}
  @endif

  @if($videos->count())
    <div class="section-header" style="margin-top:28px"><div class="section-title"><i class="fa-solid fa-video"></i> الفيديوهات ({{ $videos->count() }})</div></div>
    <div class="articles-grid-4">@foreach($videos as $video)<a href="{{ route('videos.show',$video->slug) }}" class="article-card"><div class="article-card-img" style="height:150px;display:flex;align-items:center;justify-content:center"><i class="fa-solid fa-circle-play" style="font-size:40px;color:var(--gold)"></i></div><div class="article-card-body"><div class="article-title">{{ Str::limit($video->title,70) }}</div></div></a>@endforeach</div>
  @endif

  @if($journalists->count() || $matchedCategories->count())
    <div class="section-header" style="margin-top:28px"><div class="section-title"><i class="fa-solid fa-link"></i> نتائج مرتبطة</div></div>
    <div class="search-mini-grid">
      @foreach($matchedCategories as $category)<a class="search-mini" href="{{ route('categories.show',$category->slug) }}"><i class="fa-solid fa-folder" style="color:var(--gold)"></i><span>{{ $category->name }}</span></a>@endforeach
      @foreach($journalists as $journalist)<div class="search-mini"><i class="fa-solid fa-user-pen" style="color:var(--gold)"></i><span>{{ $journalist->name }}</span></div>@endforeach
    </div>
  @endif

  @if(!$hasSearch)<div class="search-empty"><i class="fa-solid fa-magnifying-glass"></i><p>{{ __('messages.search_enter_keyword') }}</p></div>@elseif($total === 0)<div class="search-empty"><i class="fa-regular fa-face-frown"></i><p>{{ __('messages.search_no_results',['q'=>$q]) }}</p><small>{{ __('messages.search_try_different') }}</small></div>@endif
</div></div>
@endsection
