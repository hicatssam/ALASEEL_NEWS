@php
    /*
     * صورة المقال + شعار واحد فقط.
     */

    $articleImageUrl = route('site.article-image', [
        'article' => $article->id,
        'v' => $article->updated_at?->timestamp ?? 1,
    ]);

    $articleImageStyle = $style ?? '';

    $articleLogoUrl = route('site.logo', [
        'v' => $siteSettings['_site_logo_version'] ?? 1,
    ]);
@endphp

<div
    class="article-image-with-logo"
    style="
        position:relative;
        width:100%;
        height:100%;
        overflow:hidden;
    "
>
    <img
        src="{{ $articleImageUrl }}"
        alt="{{ $alt ?? $article->title }}"
        class="{{ trim($class ?? '') }}"
        style="{{ $articleImageStyle }}"
        @if(($loading ?? 'lazy') !== false)
            loading="{{ $loading ?? 'lazy' }}"
        @endif
        onerror="
            this.onerror=null;
            this.style.display='none';
        "
    >

    <img
        src="{{ $articleLogoUrl }}"
        alt=""
        aria-hidden="true"
        class="article-image-logo"
        style="
            position:absolute;
            right:14px;
            bottom:14px;
            width:90px;
            max-width:22%;
            height:auto;
            max-height:55px;
            object-fit:contain;
            z-index:10;
            pointer-events:none;
            filter:drop-shadow(0 2px 5px rgba(0,0,0,.65));
        "
    >
</div>