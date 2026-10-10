@extends('layouts.app')
@section('title', $typeLabel)
@section('description', 'أحدث '.$typeLabel.' من وكالة الأصيل الإخبارية')
@section('content')
@php
    $showAuthorList = in_array($type, ['article', 'opinion', 'opinions-articles'], true);
@endphp
<div class="main-content"><div class="container" style="padding-block:32px">
    <div class="section-header"><h1 class="section-title"><i class="fa-solid fa-pen-nib"></i> {{ $typeLabel }}</h1></div>
    <div class="{{ $showAuthorList ? 'author-content-list' : 'content-cards-grid' }}">
    @forelse($articles as $article)
        @php
            $displayAuthorName = $article->content_type === 'opinion'
                ? ($article->content_owner_name ?: $article->journalist?->name)
                : $article->journalist?->name;
            $displayAuthorPhoto = $article->content_type === 'opinion'
                ? ($article->content_owner_photo_url ?: $article->journalist?->photo_url)
                : $article->journalist?->photo_url;
        @endphp

        @if($showAuthorList)
            <article class="author-content-item">
                <a class="author-content-avatar" href="{{ route('articles.show', $article->slug) }}" aria-label="{{ $article->title }}">
                    @if($displayAuthorPhoto)
                        <img src="{{ $displayAuthorPhoto }}" alt="{{ $displayAuthorName ?: 'كاتب المادة' }}" loading="lazy"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='grid';">
                        <span class="author-content-placeholder" style="display:none"><i class="fa-solid fa-user-pen"></i></span>
                    @else
                        <span class="author-content-placeholder"><i class="fa-solid fa-user-pen"></i></span>
                    @endif
                </a>
                <div class="author-content-body">
                    <span class="article-cat">{{ $article->content_type_label }}</span>
                    <h2><a href="{{ route('articles.show', $article->slug) }}">{{ $article->title }}</a></h2>
                    <div class="author-content-name">{{ $displayAuthorName ?: 'وكالة الأصيل' }}</div>
                    @if($article->summary)
                        <p>{{ Str::limit(strip_tags($article->summary), 150) }}</p>
                    @endif
                </div>
            </article>
        @else
            <article class="article-card">
                <a class="article-card-img" href="{{ route('articles.show',$article->slug) }}">@include('partials.article-image', ['article' => $article])</a>
                <div class="article-card-body"><span class="article-cat">{{ $article->content_type_label }}</span><h2 class="article-title"><a href="{{ route('articles.show',$article->slug) }}">{{ $article->title }}</a></h2>
                @if($article->summary)<p style="color:var(--text-muted);font-size:13px;line-height:1.7">{{ Str::limit(strip_tags($article->summary),120) }}</p>@endif
                <div class="article-meta">@if($article->journalist)<span><i class="fa-solid fa-user-pen"></i> {{ $article->journalist->name }}</span>@endif</div></div>
            </article>
        @endif
    @empty
        <p>لا يوجد محتوى منشور حاليًا.</p>
    @endforelse
    </div>
    @if($articles->hasPages())<nav class="pagination" style="margin-top:24px">
      @if($articles->previousPageUrl())<a href="{{ $articles->previousPageUrl() }}">السابق</a>@endif
      <span class="active">صفحة {{ $articles->currentPage() }} من {{ $articles->lastPage() }}</span>
      @if($articles->nextPageUrl())<a href="{{ $articles->nextPageUrl() }}">التالي</a>@endif
    </nav>@endif
</div></div>
@endsection

@push('styles')
<style>
.content-cards-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px}
.author-content-list{display:grid;gap:0;max-width:920px;margin-inline:auto;border-top:1px solid var(--border)}
.author-content-item{display:grid;grid-template-columns:76px minmax(0,1fr);gap:18px;align-items:center;padding:22px 10px;border-bottom:1px solid var(--border);transition:background .2s ease,transform .2s ease}
.author-content-item:hover{background:rgba(200,154,43,.045);transform:translateX(-3px)}
.author-content-avatar{position:relative;display:block;width:68px;height:68px;border-radius:50%;overflow:hidden;border:2px solid rgba(200,154,43,.7);background:var(--surface2)}
.author-content-avatar img,.author-content-placeholder{position:absolute;inset:0;width:100%;height:100%}
.author-content-avatar img{display:block;object-fit:cover}
.author-content-placeholder{display:grid;place-items:center;color:var(--gold);background:rgba(200,154,43,.1);font-size:22px}
.author-content-body{min-width:0}
.author-content-body h2{margin:5px 0 6px;font-size:clamp(16px,2vw,21px);line-height:1.55}
.author-content-body h2 a{color:var(--white)}
.author-content-body h2 a:hover{color:var(--gold)}
.author-content-name{color:var(--gold);font-size:13px;font-weight:700}
.author-content-body p{margin:8px 0 0;color:var(--text-muted);font-size:13px;line-height:1.7}
@media(max-width:640px){.author-content-item{grid-template-columns:58px minmax(0,1fr);gap:12px;padding:17px 4px}.author-content-avatar{width:54px;height:54px}.author-content-body p{display:none}}
</style>
@endpush
