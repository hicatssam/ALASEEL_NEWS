@if($items->isNotEmpty())
<div class="home-section">
  <div class="section-header">
    <div class="section-title"><i class="fa-solid {{ $icon }}"></i> {{ $title }}</div>
    <a href="{{ route('content.index',$type) }}" class="section-more">عرض الكل <i class="fa-solid fa-chevron-left"></i></a>
  </div>
  @include('partials.home-news-section', [
    'items' => $items,
    'title' => $title,
    'icon' => $icon,
    'moreUrl' => route('content.index', $type),
    'embedded' => true,
  ])
</div>
@endif
