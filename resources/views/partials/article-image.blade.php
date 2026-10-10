@php
    /* صورة المقال المولدة من السيرفر وبداخلها الشعار. */

    $articleImageUrl = route('site.article-image', [
        'article' => $article->id,
        'iv' => 3,
        'v' => $article->updated_at?->timestamp ?? 1,
    ]);

    $articleImageStyle = $style ?? '';

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
</div>
