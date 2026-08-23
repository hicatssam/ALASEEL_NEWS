@php
    $articleImageUrl = $article->main_image_url ?: route('site.logo');
    $articleImageFallback = blank($article->main_image_url);
    $articleImageStyle = $style ?? '';
@endphp
<img
    src="{{ $articleImageUrl }}"
    alt="{{ $alt ?? $article->title }}"
    class="{{ trim(($class ?? '').($articleImageFallback ? ' article-fallback-logo' : '')) }}"
    style="{{ $articleImageStyle }}{{ $articleImageFallback ? ';object-fit:contain;background:#111;padding:18px' : '' }}"
    @if(($loading ?? 'lazy') !== false) loading="{{ $loading ?? 'lazy' }}" @endif
    onerror="this.onerror=null;this.src='{{ route('site.logo') }}';this.style.objectFit='contain';this.style.background='#111';this.style.padding='18px';"
>
