@extends('layouts.app')
@section('title', $typeLabel)
@section('description', 'أحدث '.$typeLabel.' من وكالة الأصيل الإخبارية')
@section('content')
<div class="main-content"><div class="container" style="padding-block:32px">
    <div class="section-header"><h1 class="section-title"><i class="fa-solid fa-pen-nib"></i> {{ $typeLabel }}</h1></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px">
    @forelse($articles as $article)<article class="article-card">
        <a class="article-card-img" href="{{ route('articles.show',$article->slug) }}">@include('partials.article-image', ['article' => $article])</a>
        <div class="article-card-body"><span class="article-cat">{{ $article->content_type_label }}</span><h2 class="article-title"><a href="{{ route('articles.show',$article->slug) }}">{{ $article->title }}</a></h2>
        @if($article->summary)<p style="color:var(--text-muted);font-size:13px;line-height:1.7">{{ Str::limit(strip_tags($article->summary),120) }}</p>@endif
        <div class="article-meta">@if($article->journalist)<span><i class="fa-solid fa-user-pen"></i> {{ $article->journalist->name }}</span>@endif</div></div>
    </article>@empty<p>لا يوجد محتوى منشور حاليًا.</p>@endforelse
    </div>
    @if($articles->hasPages())<nav class="pagination" style="margin-top:24px">
      @if($articles->previousPageUrl())<a href="{{ $articles->previousPageUrl() }}">السابق</a>@endif
      <span class="active">صفحة {{ $articles->currentPage() }} من {{ $articles->lastPage() }}</span>
      @if($articles->nextPageUrl())<a href="{{ $articles->nextPageUrl() }}">التالي</a>@endif
    </nav>@endif
</div></div>
@endsection
