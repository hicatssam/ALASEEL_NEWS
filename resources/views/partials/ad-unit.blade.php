@php
    $adClass = trim('site-ad-unit '.($class ?? ''));
@endphp

<article class="{{ $adClass }}" aria-label="إعلان: {{ $ad->title }}">
    @if($ad->link)
        <a href="{{ $ad->link }}" target="_blank" rel="noopener noreferrer sponsored">
    @endif

    @if($ad->image_url && $ad->type === 'video')
        <video controls muted playsinline preload="metadata" aria-label="{{ $ad->title }}">
            <source src="{{ $ad->image_url }}">
        </video>
    @elseif($ad->image_url)
        <img src="{{ $ad->image_url }}" alt="{{ $ad->title }}" loading="lazy">
    @else
        <div class="site-ad-fallback">
            <small>إعلان</small>
            <strong>{{ $ad->title }}</strong>
        </div>
    @endif

    @if($ad->link)
        </a>
    @endif
</article>
