@if($items->isNotEmpty())
<section class="home-section">
  <div class="section-header">
    <div class="section-title"><i class="fa-solid {{ $icon }}"></i> {{ $title }}</div>
    <a href="{{ route('content.index',$type) }}" class="section-more">عرض الكل <i class="fa-solid fa-chevron-left"></i></a>
  </div>
  <div class="articles-grid">
  @foreach($items->take(3) as $item)
    <article class="article-card{{ $type === 'opinion' ? ' opinion-card' : '' }}">
      <a href="{{ route('articles.show',$item->slug) }}" class="article-card-img">@include('partials.article-image', ['article' => $item])</a>
      <div class="article-card-body">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
          @if($item->journalist?->photo_url)
            <img src="{{ $item->journalist->photo_url }}" alt="صاحب الرأي: {{ $item->journalist->name }}" loading="lazy" style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid var(--gold);flex-shrink:0">
          @else
            <span aria-hidden="true" style="width:44px;height:44px;border-radius:50%;display:grid;place-items:center;background:rgba(200,154,43,.12);color:var(--gold);border:2px solid rgba(200,154,43,.35);flex-shrink:0"><i class="fa-solid fa-user-pen"></i></span>
          @endif
          <span style="display:flex;flex-direction:column;gap:2px;min-width:0">
            @if($type === 'opinion')<small style="font-size:10px;color:var(--gold);font-weight:800">صاحب الرأي</small>@endif
            <strong style="font-size:12px;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $item->journalist?->name ?? 'وكالة الأصيل الإخبارية' }}</strong>
          </span>
        </div>
        <h3 class="article-title"><a href="{{ route('articles.show',$item->slug) }}">{{ Str::limit($item->title,78) }}</a></h3>
        @if($item->summary)<p style="font-size:12px;color:var(--text-muted);line-height:1.7;margin-top:7px">{{ Str::limit(strip_tags($item->summary),90) }}</p>@endif
        <a href="{{ route('articles.show',$item->slug) }}" style="display:inline-flex;align-items:center;gap:6px;color:var(--gold);font-size:12px;font-weight:800;margin-top:11px">اقرأ المزيد <i class="fa-solid fa-arrow-left"></i></a>
      </div>
    </article>
  @endforeach
  </div>
</section>
@endif
