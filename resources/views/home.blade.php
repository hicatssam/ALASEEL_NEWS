@extends('layouts.app')
@section('title', __('messages.site_name'))
@section('description', __('messages.site_tagline'))
@section('canonical', route('home'))
@push('meta')
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<meta property="og:type" content="website">
<meta property="og:locale" content="{{ app()->getLocale() === 'ar' ? 'ar_AR' : 'en_US' }}">
<meta property="og:url" content="{{ route('home') }}">
<meta property="og:title" content="{{ __('messages.site_name') }}">
<meta property="og:description" content="{{ __('messages.site_tagline') }}">
<meta property="og:site_name" content="{{ __('messages.site_name') }}">
@php
    $homeShareImage = isset($featuredArticles) && $featuredArticles->first()
        ? route('site.article-image', [
            'article' => $featuredArticles->first()->id,
            'v' => $featuredArticles->first()->updated_at?->timestamp ?? 1,
            'lv' => $siteSettings['_site_logo_version'] ?? 1,
        ])
        : route('site.logo', [
            'v' => $siteSettings['_site_logo_version'] ?? 1,
        ]);
@endphp
<meta property="og:image" content="{{ $homeShareImage }}">
<meta property="og:image:secure_url" content="{{ $homeShareImage }}">
<meta property="og:image:type" content="image/jpeg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ isset($featuredArticles) && $featuredArticles->first() ? $featuredArticles->first()->title : __('messages.site_name') }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ __('messages.site_name') }}">
<meta name="twitter:description" content="{{ __('messages.site_tagline') }}">
<meta name="twitter:image" content="{{ $homeShareImage }}">
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'NewsMediaOrganization',
    'name' => __('messages.site_name'),
    'url' => route('home'),
    'logo' => route('site.icon', ['size' => 512]),
    'image' => asset('images/social-share.png'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush
@section('content')

@php
  /*
   * قيم احتياطية تمنع انهيار الصفحة أثناء النشر إذا وصل قالب Home
   * قبل نسخة HomeController الجديدة أو بقيت نسخة قديمة في الـ OPcache.
   */
  $featuredArticles = collect($featuredArticles ?? []);
  $latestArticles = collect($latestArticles ?? []);
  $articlesContent = collect($articlesContent ?? []);
  $stories = collect($stories ?? []);
  $reports = collect($reports ?? []);
  $opinions = collect($opinions ?? []);
  $categorySections = collect($categorySections ?? []);
  $homepageAds = collect($homepageAds ?? []);
  $editorPicks = collect($editorPicks ?? []);
  $featuredVideos = collect($featuredVideos ?? []);
  $trendingArticles = collect($trendingArticles ?? []);
  $sidebarAds = collect($sidebarAds ?? []);

  $localCategorySection = $localCategorySection
      ?? $categorySections->first(function ($category) {
          $identity = \Illuminate\Support\Str::lower(
              trim(($category->name ?? '').' '.($category->slug ?? ''))
          );

          return \Illuminate\Support\Str::contains($identity, [
              'محلي',
              'local',
          ]);
      });

  $remainingCategorySections = collect(
      $remainingCategorySections
          ?? $categorySections->reject(
              fn ($category) => $localCategorySection
                  && (string) $category->id === (string) $localCategorySection->id
          )->values()
  );

  $articlesAndStories = $articlesAndStories
      ?? $articlesContent
          ->concat($stories)
          ->sortByDesc(fn ($item) => $item->published_at?->timestamp ?? 0)
          ->unique('id')
          ->take(5)
          ->values();

  $opinionAndArticles = $opinionAndArticles
      ?? $opinions
          ->concat($articlesContent)
          ->sortByDesc(fn ($item) => $item->published_at?->timestamp ?? 0)
          ->unique('id')
          ->take(6)
          ->values();
@endphp

<style>
  .home-page { padding-block: 30px 48px; }
  .home-section { margin-bottom: 34px; }
  .home-layout { display: grid; grid-template-columns: minmax(0, 1fr) 310px; gap: 28px; align-items: start; }
  .home-sidebar { display: flex; flex-direction: column; gap: 18px; min-width: 0; }
  .home-sidebar-trending{order:1}.home-sidebar-opinions{order:2}.home-sidebar-currencies{order:3}.home-sidebar-weather{order:4}.home-sidebar-ads{order:5}.home-sidebar-newsletter{order:6}
  .weather-details{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-top:15px}
  .weather-detail{padding:10px 8px;border-radius:8px;background:rgba(255,255,255,.035);border:1px solid var(--border);font-size:10px;color:var(--text-muted)}
  .weather-detail strong{display:block;margin-top:4px;color:var(--white);font-size:12px;font-family:'Inter',sans-serif}


  .home-page,
  .home-page * { box-sizing: border-box; }
  .home-page { width: 100%; overflow-x: clip; }
  .home-page .container,
  .breaking-bar .container { width: min(calc(100% - 32px), 1240px); margin-inline: auto; }
  .home-page img,
  .home-page video { max-width: 100%; }
  .home-page a,
  .home-page p,
  .home-page h1,
  .home-page .article-title,
  .home-page .widget-article-title { overflow-wrap: anywhere; }
  .hero-grid { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(260px, .8fr); gap: 18px; align-items: stretch; }
  .hero-grid > * { min-width: 0; }
  .article-card-featured { width: 100%; min-width: 0; overflow: hidden; }
  .article-card-featured .card-img {
    position: relative;
    display: block;
    width: 100%;
    min-height: 290px;
    aspect-ratio: 16 / 9;
    overflow: hidden;
  }
  .article-card-featured .card-img > img {
    display: block;
    width: 100%;
    max-width: none;
    height: 100%;
    object-fit: cover;
    object-position: center;
  }
  .articles-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; }
  .editor-picks-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
  .article-card,
  .article-card-body,
  .widget-article-body,
  .home-layout > div { min-width: 0; }
  .article-card-img { overflow: hidden; aspect-ratio: 16 / 9; }
  .article-card-img > img { width: 100%; height: 100%; object-fit: cover; }
  .section-header { gap: 12px; }
  .section-title { min-width: 0; }

  .category-sections { display: grid; gap: 28px; margin-bottom: 32px; }
  .category-news { padding: 20px; border: 1px solid var(--border); border-radius: 16px; background: var(--surface); }
  .category-news-grid { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, .85fr); gap: 18px; }
  .category-lead { overflow: hidden; border-radius: 13px; background: var(--surface2); }
  .category-lead-image { display: block; width: 100%; aspect-ratio: 16 / 8.5; overflow: hidden; background: var(--surface2); }
  .category-lead-image img { width: 100%; height: 100%; object-fit: cover; transition: transform .35s ease; }
  .category-lead:hover .category-lead-image img { transform: scale(1.035); }
  .category-lead-body { padding: 16px; }
  .category-lead-title { margin: 0 0 9px; color: var(--white); font-size: 20px; line-height: 1.55; }
  .category-lead-summary { margin: 0 0 10px; color: rgba(255,255,255,.48); font-size: 13px; line-height: 1.75; }
  .category-news-list { display: grid; align-content: start; gap: 10px; }
  .category-news-item { display: grid; grid-template-columns: 104px minmax(0, 1fr); gap: 12px; min-height: 78px; padding: 8px; border: 1px solid var(--border); border-radius: 11px; background: var(--surface2); transition: border-color .2s ease, transform .2s ease; }
  .category-news-item:hover { border-color: rgba(200,154,43,.45); transform: translateY(-2px); }
  .category-news-thumb { width: 104px; height: 76px; overflow: hidden; border-radius: 8px; background: var(--surface); }
  .category-news-thumb img { width: 100%; height: 100%; object-fit: cover; }
  .category-news-title { margin: 2px 0 7px; color: var(--white); font-size: 13px; line-height: 1.55; }
  .category-news-meta { color: rgba(255,255,255,.38); font-size: 10.5px; }

  .home-news-block { margin-bottom: 42px; }
  .home-five-grid { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr); gap: 18px; }
  .home-five-lead { overflow: hidden; }
  .home-five-lead-image { display: block; width: 100%; aspect-ratio: 16 / 9; overflow: hidden; background: var(--surface2); }
  .home-five-lead-image img { width: 100%; height: 100%; object-fit: cover; transition: transform .35s ease; }
  .home-five-lead:hover .home-five-lead-image img { transform: scale(1.035); }
  .home-five-lead-title { margin: 5px 0 8px; font-size: 21px; line-height: 1.55; }
  .home-five-summary { margin: 0 0 12px; color: rgba(255,255,255,.48); font-size: 13px; line-height: 1.75; }
  .home-five-side { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
  .home-five-item { display: grid; grid-template-rows: auto 1fr; overflow: hidden; }
  .home-five-thumb { display: block; width: 100%; aspect-ratio: 16 / 8.5; overflow: hidden; background: var(--surface2); }
  .home-five-thumb img { width: 100%; height: 100%; object-fit: cover; transition: transform .3s ease; }
  .home-five-item:hover .home-five-thumb img { transform: scale(1.04); }
  .home-five-item-body { padding: 11px 12px; min-width: 0; }
  .home-five-item .article-title { margin: 3px 0 8px; font-size: 13px; line-height: 1.6; }
  .home-news-action { display: flex; justify-content: center; padding-top: 17px; }
  .home-more-button { min-width: 150px; justify-content: center; gap: 9px; }

  .home-ad-carousel { position: relative; width:min(92vw,820px); margin:32px auto 38px; }
  .home-ad-viewport { overflow: hidden; border-radius: 14px; }
  .home-ad-list {
    display: flex;
    gap: 14px;
    direction: ltr;
    transition: transform .65s cubic-bezier(.22,1,.36,1);
    will-change: transform;
  }
  .home-ad {
    flex: 0 0 100%;
    position: relative;
    overflow: hidden;
    min-width: 0;
    aspect-ratio: 16 / 5;
    border: 1px solid var(--border);
    border-radius: 14px;
    background: var(--surface2);
    box-shadow: 0 12px 32px rgba(0,0,0,.16);
  }
  .ad-animate {
    opacity: 0;
    transform: translateY(22px) scale(.985);
    transition:
      opacity .65s cubic-bezier(.22,1,.36,1),
      transform .65s cubic-bezier(.22,1,.36,1),
      border-color .3s ease,
      box-shadow .3s ease;
    transition-delay: var(--ad-delay, 0ms);
    will-change: opacity, transform;
  }
  .ad-animate.is-visible {
    opacity: 1;
    transform: translate3d(0, 0, 0) scale(1);
  }
  .ad-animate::after {
    content: '';
    position: absolute;
    inset: 0;
    z-index: 2;
    pointer-events: none;
    background: linear-gradient(105deg, transparent 35%, rgba(255,255,255,.16) 50%, transparent 65%);
    transform: translateX(-130%);
  }
  [dir="rtl"] .ad-animate::after { transform: translateX(130%); }
  .ad-animate.is-visible::after { animation: ad-shine 1.05s .35s ease-out both; }
  .ad-animate > a { position: relative; z-index: 1; }
  .ad-animate img,
  .ad-animate video { transition: transform .55s cubic-bezier(.22,1,.36,1), filter .4s ease; }
  .ad-animate:hover {
    animation-play-state: paused;
    border-color: rgba(200,154,43,.55);
    box-shadow: 0 18px 42px rgba(0,0,0,.24), 0 0 0 1px rgba(200,154,43,.12);
  }
  .ad-animate:hover img,
  .ad-animate:hover video { transform: scale(1.035); filter: saturate(1.08) contrast(1.03); }
  @keyframes ad-shine {
    from { transform: translateX(-130%); }
    to { transform: translateX(130%); }
  }
  [dir="rtl"] .ad-animate.is-visible::after { animation-name: ad-shine-rtl; }
  @keyframes ad-shine-rtl {
    from { transform: translateX(130%); }
    to { transform: translateX(-130%); }
  }
  @keyframes ad-horizontal-float {
    0%   { transform: translate3d(-14px, 0, 0) scale(1); }
    100% { transform: translate3d(14px, 0, 0) scale(1); }
  }
  .sidebar-ad.is-visible {
    animation-name: sidebar-ad-horizontal-float;
    animation-duration: 2.8s;
  }
  @keyframes sidebar-ad-horizontal-float {
    0%   { transform: translate3d(-8px, 0, 0) scale(1); }
    100% { transform: translate3d(8px, 0, 0) scale(1); }
  }
  .home-ad > a { display: block; width: 100%; height: 100%; color: inherit; }
  .home-ad-media-shell { position: relative; display: block; width: 100%; height: 100%; overflow: hidden; background: var(--surface2); }
  .home-ad-media-backdrop { position: absolute; z-index: 0; inset: -16px; width: calc(100% + 32px); height: calc(100% + 32px); object-fit: cover; filter: blur(18px) brightness(.48) saturate(1.15); transform: scale(1.08); opacity: .9; }
  .home-ad-media { position: relative; z-index: 1; display: block; width: 100%; height: 100%; object-fit: cover; object-position: center; background: transparent; padding: 0; }
  video.home-ad-media { object-fit: cover; padding: 0; }
  .home-ad-control {
    position: absolute;
    top: 50%;
    z-index: 5;
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 50%;
    color: #fff;
    background: rgba(0,0,0,.72);
    cursor: pointer;
    transform: translateY(-50%);
  }
  .home-ad-prev { left: -16px; }
  .home-ad-next { right: -16px; }
  .home-ad-dots { display: flex; justify-content: center; gap: 6px; margin-top: 12px; }
  .home-ad-dot { width: 7px; height: 7px; padding: 0; border: 0; border-radius: 50%; background: rgba(255,255,255,.25); cursor: pointer; }
  .home-ad-dot.is-active { width: 22px; border-radius: 8px; background: var(--gold); }
  .home-ad-fallback {
    min-height: 112px;
    padding: 24px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    background: linear-gradient(135deg, rgba(200,154,43,.15), rgba(200,154,43,.035));
  }
  .home-ad-label { margin-bottom: 5px; color: rgba(255,255,255,.38); font-size: 10px; letter-spacing: .8px; }
  .home-ad-title { color: rgba(255,255,255,.82); font-size: 14px; font-weight: 800; }

  .currency-market{margin:0;border:1px solid var(--border);border-radius:13px;background:linear-gradient(145deg,var(--surface),rgba(200,154,43,.045));box-shadow:0 12px 30px rgba(0,0,0,.15);overflow:hidden}
  .currency-market-head{padding:14px 15px 12px;border-bottom:1px solid var(--border);background:rgba(255,255,255,.018)}
  .currency-market-title{display:flex;align-items:center;gap:9px;color:var(--white);font-size:14px;font-weight:900}
  .currency-market-title i{width:31px;height:31px;display:grid;place-items:center;border-radius:8px;background:rgba(200,154,43,.14);color:var(--gold);font-size:13px}
  .currency-market-meta{display:flex;align-items:center;gap:7px;margin-top:8px;color:rgba(255,255,255,.4);font-size:9.5px}
  .currency-live-dot{width:7px;height:7px;border-radius:50%;background:#39d98a;box-shadow:0 0 0 5px rgba(57,217,138,.09);animation:currency-pulse 1.8s ease infinite}
  @keyframes currency-pulse{50%{box-shadow:0 0 0 9px rgba(57,217,138,0)}}
  .currency-list{display:flex;flex-direction:column}
  .currency-row{padding:12px 14px;border-bottom:1px solid var(--border);transition:background .25s ease}
  .currency-row:last-child{border-bottom:0}
  .currency-row:hover{background:rgba(200,154,43,.055)}
  .currency-row-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:9px}
  .currency-name{display:flex;align-items:center;gap:8px;min-width:0;font-weight:700;color:var(--white);font-size:10.5px}
  .currency-name-text{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:rgba(255,255,255,.68)}
  .currency-code{flex-shrink:0;min-width:55px;padding:4px 6px;border-radius:6px;background:rgba(200,154,43,.12);color:var(--gold);font-family:'Inter',sans-serif;font-size:9px;text-align:center}
  .currency-prices{display:grid;grid-template-columns:1fr 1fr;gap:8px}
  .currency-price{display:flex;align-items:center;justify-content:space-between;gap:6px;padding:7px 9px;border-radius:7px;background:rgba(255,255,255,.035);color:rgba(255,255,255,.38);font-size:9px}
  .currency-rate{color:rgba(255,255,255,.82);font-family:'Inter',sans-serif;font-variant-numeric:tabular-nums;font-size:11px;font-weight:700}
  .currency-trend{flex-shrink:0;font-size:9.5px}
  .currency-trend.is-up{color:#55d99b}.currency-trend.is-down{color:#ff7777}.currency-trend.is-flat{color:rgba(255,255,255,.35)}
  .currency-market-note{padding:10px 14px;border-top:1px solid var(--border);color:rgba(255,255,255,.3);font-size:8.5px;line-height:1.7}
  .currency-market-error{color:#ff8b8b}

  .sidebar-ad { position: relative; overflow: hidden; border-radius: 13px; border: 1px solid var(--border); background: var(--surface2); box-shadow: 0 10px 28px rgba(0,0,0,.14); }
  .sidebar-ad > a { display: block; color: inherit; }
  .sidebar-ad-media { display: block; width: 100%; aspect-ratio: 4 / 3; object-fit: cover; }
  .sidebar-ad .home-ad-fallback { min-height: 150px; }


  .world-map-bg {
    position: relative;
    background-color: #0c0c0d;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='640' height='320' viewBox='0 0 640 320'%3E%3Cg fill='%23C89A2B' fill-opacity='.5'%3E%3Ccircle cx='92' cy='72' r='1.8'/%3E%3Ccircle cx='98' cy='62' r='1.7'/%3E%3Ccircle cx='106' cy='142' r='1.4'/%3E%3Ccircle cx='78' cy='121' r='2.0'/%3E%3Ccircle cx='126' cy='97' r='2.0'/%3E%3Ccircle cx='87' cy='71' r='1.4'/%3E%3Ccircle cx='73' cy='116' r='1.7'/%3E%3Ccircle cx='99' cy='113' r='1.3'/%3E%3Ccircle cx='56' cy='77' r='1.8'/%3E%3Ccircle cx='106' cy='89' r='1.7'/%3E%3Ccircle cx='110' cy='87' r='1.9'/%3E%3Ccircle cx='143' cy='81' r='1.7'/%3E%3Ccircle cx='119' cy='147' r='1.8'/%3E%3Ccircle cx='64' cy='99' r='1.8'/%3E%3Ccircle cx='69' cy='107' r='1.3'/%3E%3Ccircle cx='139' cy='136' r='1.7'/%3E%3Ccircle cx='167' cy='89' r='1.8'/%3E%3Ccircle cx='129' cy='116' r='1.6'/%3E%3Ccircle cx='112' cy='125' r='1.3'/%3E%3Ccircle cx='143' cy='123' r='2.0'/%3E%3Ccircle cx='160' cy='86' r='1.6'/%3E%3Ccircle cx='111' cy='73' r='1.4'/%3E%3Ccircle cx='66' cy='82' r='1.6'/%3E%3Ccircle cx='109' cy='113' r='1.9'/%3E%3Ccircle cx='159' cy='146' r='1.5'/%3E%3Ccircle cx='104' cy='93' r='1.9'/%3E%3Ccircle cx='72' cy='80' r='1.5'/%3E%3Ccircle cx='114' cy='117' r='1.5'/%3E%3Ccircle cx='98' cy='115' r='2.0'/%3E%3Ccircle cx='142' cy='110' r='1.7'/%3E%3Ccircle cx='140' cy='62' r='1.9'/%3E%3Ccircle cx='154' cy='147' r='1.9'/%3E%3Ccircle cx='101' cy='97' r='1.4'/%3E%3Ccircle cx='134' cy='62' r='1.3'/%3E%3Ccircle cx='160' cy='177' r='1.5'/%3E%3Ccircle cx='156' cy='171' r='1.6'/%3E%3Ccircle cx='182' cy='175' r='1.5'/%3E%3Ccircle cx='167' cy='198' r='1.4'/%3E%3Ccircle cx='174' cy='210' r='1.4'/%3E%3Ccircle cx='154' cy='196' r='1.5'/%3E%3Ccircle cx='194' cy='177' r='1.3'/%3E%3Ccircle cx='201' cy='215' r='1.4'/%3E%3Ccircle cx='178' cy='163' r='1.7'/%3E%3Ccircle cx='187' cy='187' r='1.6'/%3E%3Ccircle cx='157' cy='240' r='1.7'/%3E%3Ccircle cx='192' cy='194' r='1.5'/%3E%3Ccircle cx='196' cy='244' r='1.9'/%3E%3Ccircle cx='189' cy='184' r='1.7'/%3E%3Ccircle cx='150' cy='189' r='1.5'/%3E%3Ccircle cx='173' cy='257' r='2.0'/%3E%3Ccircle cx='201' cy='198' r='1.5'/%3E%3Ccircle cx='161' cy='180' r='1.4'/%3E%3Ccircle cx='183' cy='254' r='1.9'/%3E%3Ccircle cx='175' cy='228' r='1.9'/%3E%3Ccircle cx='190' cy='210' r='1.4'/%3E%3Ccircle cx='192' cy='195' r='1.9'/%3E%3Ccircle cx='202' cy='201' r='1.6'/%3E%3Ccircle cx='158' cy='173' r='1.4'/%3E%3Ccircle cx='168' cy='217' r='1.4'/%3E%3Ccircle cx='184' cy='215' r='2.0'/%3E%3Ccircle cx='334' cy='97' r='1.9'/%3E%3Ccircle cx='321' cy='62' r='1.5'/%3E%3Ccircle cx='322' cy='81' r='1.5'/%3E%3Ccircle cx='333' cy='55' r='1.9'/%3E%3Ccircle cx='329' cy='74' r='1.7'/%3E%3Ccircle cx='362' cy='72' r='1.9'/%3E%3Ccircle cx='338' cy='78' r='1.7'/%3E%3Ccircle cx='356' cy='58' r='1.6'/%3E%3Ccircle cx='352' cy='79' r='1.5'/%3E%3Ccircle cx='339' cy='79' r='1.8'/%3E%3Ccircle cx='314' cy='79' r='1.5'/%3E%3Ccircle cx='325' cy='91' r='1.7'/%3E%3Ccircle cx='342' cy='91' r='1.9'/%3E%3Ccircle cx='335' cy='82' r='1.7'/%3E%3Ccircle cx='339' cy='87' r='1.6'/%3E%3Ccircle cx='340' cy='75' r='2.0'/%3E%3Ccircle cx='350' cy='97' r='2.0'/%3E%3Ccircle cx='324' cy='79' r='2.0'/%3E%3Ccircle cx='368' cy='123' r='1.4'/%3E%3Ccircle cx='340' cy='114' r='1.5'/%3E%3Ccircle cx='356' cy='123' r='1.9'/%3E%3Ccircle cx='378' cy='134' r='2.0'/%3E%3Ccircle cx='337' cy='170' r='2.0'/%3E%3Ccircle cx='368' cy='126' r='1.6'/%3E%3Ccircle cx='345' cy='150' r='1.4'/%3E%3Ccircle cx='331' cy='202' r='1.3'/%3E%3Ccircle cx='348' cy='164' r='1.3'/%3E%3Ccircle cx='332' cy='189' r='1.7'/%3E%3Ccircle cx='316' cy='140' r='1.3'/%3E%3Ccircle cx='364' cy='141' r='1.4'/%3E%3Ccircle cx='338' cy='228' r='1.9'/%3E%3Ccircle cx='327' cy='124' r='1.9'/%3E%3Ccircle cx='349' cy='199' r='1.4'/%3E%3Ccircle cx='339' cy='114' r='2.0'/%3E%3Ccircle cx='354' cy='213' r='1.4'/%3E%3Ccircle cx='370' cy='166' r='1.5'/%3E%3Ccircle cx='348' cy='230' r='1.5'/%3E%3Ccircle cx='317' cy='176' r='1.5'/%3E%3Ccircle cx='316' cy='126' r='1.3'/%3E%3Ccircle cx='323' cy='146' r='1.5'/%3E%3Ccircle cx='363' cy='143' r='1.7'/%3E%3Ccircle cx='321' cy='151' r='1.3'/%3E%3Ccircle cx='361' cy='179' r='1.4'/%3E%3Ccircle cx='342' cy='231' r='1.4'/%3E%3Ccircle cx='367' cy='163' r='1.6'/%3E%3Ccircle cx='368' cy='157' r='1.7'/%3E%3Ccircle cx='333' cy='217' r='1.8'/%3E%3Ccircle cx='354' cy='159' r='1.5'/%3E%3Ccircle cx='312' cy='122' r='1.3'/%3E%3Ccircle cx='361' cy='139' r='1.4'/%3E%3Ccircle cx='371' cy='195' r='1.5'/%3E%3Ccircle cx='325' cy='144' r='1.6'/%3E%3Ccircle cx='397' cy='97' r='1.5'/%3E%3Ccircle cx='469' cy='71' r='2.0'/%3E%3Ccircle cx='425' cy='86' r='1.3'/%3E%3Ccircle cx='438' cy='101' r='1.7'/%3E%3Ccircle cx='405' cy='105' r='1.3'/%3E%3Ccircle cx='417' cy='51' r='1.6'/%3E%3Ccircle cx='424' cy='70' r='1.7'/%3E%3Ccircle cx='465' cy='136' r='1.8'/%3E%3Ccircle cx='440' cy='82' r='2.0'/%3E%3Ccircle cx='396' cy='133' r='1.8'/%3E%3Ccircle cx='532' cy='120' r='1.8'/%3E%3Ccircle cx='517' cy='58' r='1.7'/%3E%3Ccircle cx='461' cy='147' r='1.9'/%3E%3Ccircle cx='520' cy='115' r='1.9'/%3E%3Ccircle cx='494' cy='129' r='1.5'/%3E%3Ccircle cx='434' cy='53' r='1.9'/%3E%3Ccircle cx='471' cy='120' r='1.7'/%3E%3Ccircle cx='493' cy='103' r='1.3'/%3E%3Ccircle cx='515' cy='136' r='1.7'/%3E%3Ccircle cx='466' cy='124' r='1.3'/%3E%3Ccircle cx='504' cy='72' r='1.4'/%3E%3Ccircle cx='417' cy='133' r='1.4'/%3E%3Ccircle cx='459' cy='89' r='1.6'/%3E%3Ccircle cx='494' cy='138' r='1.7'/%3E%3Ccircle cx='486' cy='50' r='1.4'/%3E%3Ccircle cx='415' cy='135' r='1.5'/%3E%3Ccircle cx='472' cy='42' r='1.3'/%3E%3Ccircle cx='417' cy='126' r='1.8'/%3E%3Ccircle cx='492' cy='77' r='1.7'/%3E%3Ccircle cx='453' cy='100' r='1.4'/%3E%3Ccircle cx='532' cy='66' r='2.0'/%3E%3Ccircle cx='452' cy='145' r='2.0'/%3E%3Ccircle cx='451' cy='74' r='1.4'/%3E%3Ccircle cx='475' cy='58' r='1.7'/%3E%3Ccircle cx='519' cy='105' r='1.9'/%3E%3Ccircle cx='497' cy='70' r='1.9'/%3E%3Ccircle cx='457' cy='43' r='1.3'/%3E%3Ccircle cx='458' cy='98' r='1.5'/%3E%3Ccircle cx='394' cy='84' r='1.5'/%3E%3Ccircle cx='421' cy='88' r='1.6'/%3E%3Ccircle cx='434' cy='95' r='1.5'/%3E%3Ccircle cx='377' cy='53' r='1.9'/%3E%3Ccircle cx='414' cy='74' r='1.7'/%3E%3Ccircle cx='403' cy='88' r='2.0'/%3E%3Ccircle cx='500' cy='46' r='1.8'/%3E%3Ccircle cx='451' cy='136' r='1.8'/%3E%3Ccircle cx='421' cy='46' r='1.9'/%3E%3Ccircle cx='391' cy='100' r='1.5'/%3E%3Ccircle cx='423' cy='135' r='2.0'/%3E%3Ccircle cx='416' cy='124' r='1.5'/%3E%3Ccircle cx='471' cy='90' r='1.4'/%3E%3Ccircle cx='398' cy='67' r='1.9'/%3E%3Ccircle cx='459' cy='68' r='1.9'/%3E%3Ccircle cx='394' cy='65' r='1.4'/%3E%3Ccircle cx='431' cy='52' r='1.5'/%3E%3Ccircle cx='416' cy='113' r='1.9'/%3E%3Ccircle cx='551' cy='228' r='1.6'/%3E%3Ccircle cx='536' cy='226' r='1.5'/%3E%3Ccircle cx='504' cy='221' r='2.0'/%3E%3Ccircle cx='509' cy='232' r='1.7'/%3E%3Ccircle cx='559' cy='218' r='1.5'/%3E%3Ccircle cx='517' cy='227' r='1.6'/%3E%3Ccircle cx='561' cy='231' r='1.7'/%3E%3Ccircle cx='510' cy='233' r='1.8'/%3E%3Ccircle cx='544' cy='245' r='1.6'/%3E%3Ccircle cx='553' cy='219' r='1.9'/%3E%3Ccircle cx='544' cy='223' r='1.4'/%3E%3Ccircle cx='517' cy='239' r='1.8'/%3E%3Ccircle cx='536' cy='236' r='1.6'/%3E%3Ccircle cx='515' cy='237' r='1.3'/%3E%3Ccircle cx='521' cy='230' r='2.0'/%3E%3Ccircle cx='544' cy='250' r='1.6'/%3E%3C/g%3E%3C/svg%3E");
    background-repeat: repeat-x;
    background-position: 0 50%;
    background-size: auto 170%;
    overflow: hidden;
    animation: worldMapPan 45s linear infinite;
  }
  .world-map-bg::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 30% 35%, rgba(200,154,43,.14), transparent 62%);
    pointer-events: none;
  }
  @keyframes worldMapPan {
    from { background-position-x: 0; }
    to   { background-position-x: -640px; }
  }


  @media (max-width: 1180px) {
    .home-layout { grid-template-columns: minmax(0, 1fr) 285px; gap: 22px; }
    .articles-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  }


  @media (max-width: 900px) {
    .home-page { padding-block: 24px 40px; }
    .home-layout { grid-template-columns: 1fr; }
    .hero-grid { grid-template-columns: 1fr; }
    .hero-grid > div:last-child { display: grid !important; grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .hero-grid > div:last-child .article-card { flex-direction: column !important; }
    .hero-grid > div:last-child .article-card > div:first-child { width: 100% !important; height: 130px; border-radius: 10px 10px 0 0 !important; }
    .home-sidebar { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); align-items: start; }
    .home-sidebar .sidebar-widget,
    .home-sidebar .sidebar-ad { width: 100%; margin: 0; }
    .sidebar-ad-media { aspect-ratio: 16 / 9; }
    .home-ad { flex-basis: 100%; }
    .category-news-grid { grid-template-columns: 1fr; }
    .category-news-list { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .home-five-grid { grid-template-columns: 1fr; }
  }


  @media (max-width: 680px) {
    .home-page .container,
    .breaking-bar .container { width: min(calc(100% - 24px), 1240px); }
    .home-page { padding-block: 20px 34px; }
    .home-section { margin-bottom: 28px; }
    .article-card-featured .card-img { min-height: 220px; }
    .article-card-featured .card-body { padding: 16px; }
    .article-card-featured .card-title { font-size: clamp(20px, 5.8vw, 28px); line-height: 1.45; }
    .articles-grid,
    .editor-picks-grid,
    .home-sidebar { grid-template-columns: 1fr; }
    .hero-grid > div:last-child { grid-template-columns: 1fr !important; }
    .hero-grid > div:last-child .article-card { flex-direction: row !important; }
    .hero-grid > div:last-child .article-card > div:first-child { width: 110px !important; height: auto; min-height: 105px; border-radius: 10px 0 0 10px !important; }
    [dir="rtl"] .hero-grid > div:last-child .article-card > div:first-child { border-radius: 0 10px 10px 0 !important; }
    .article-card-img { aspect-ratio: 16 / 9; }
    .section-header { align-items: center; }
    .section-title { font-size: clamp(16px, 4.5vw, 20px); }
    .section-more { flex-shrink: 0; white-space: nowrap; }
    .article-meta { flex-wrap: wrap; row-gap: 5px; }
    .breaking-bar .container { min-width: 0; }
    .breaking-label { flex-shrink: 0; white-space: nowrap; }
    .breaking-ticker { min-width: 0; overflow: hidden; }
    .home-ad { flex-basis: 100%; min-height: 0; border-radius: 11px; }
    .home-ad-media { height: 100%; }
    .home-ad-fallback { min-height: 96px; padding: 18px; }
    .sidebar-ad-media { max-height: 280px; }
    .home-ad-prev { left: 6px; }
    .home-ad-next { right: 6px; }
    .category-news { padding: 14px; }
    .category-news-list { grid-template-columns: 1fr; }
    .category-lead-title { font-size: 17px; }
    .category-news-item { grid-template-columns: 96px minmax(0, 1fr); }
    .category-news-thumb { width: 96px; height: 72px; }
    .home-five-side { grid-template-columns: 1fr; }
    .home-five-item { grid-template-columns: 112px minmax(0, 1fr); grid-template-rows: none; }
    .home-five-thumb { height: 100%; min-height: 96px; aspect-ratio: auto; }
    .home-five-lead-title { font-size: 18px; }
  }


  @media (max-width: 420px) {
    .home-page .container,
    .breaking-bar .container { width: min(calc(100% - 20px), 1240px); }
    .home-page { padding-block: 16px 28px; }
    .home-section { margin-bottom: 23px; }
    .article-card-featured .card-img { min-height: 185px; }
    .article-card-featured .card-body { padding: 14px; }
    .hero-grid { gap: 12px; }
    .hero-grid > div:last-child { gap: 10px !important; }
    .hero-grid > div:last-child .article-card > div:first-child { width: 92px !important; min-height: 94px; }
    .article-card-body { padding: 13px; }
    .article-title { line-height: 1.55; }
    .section-header { margin-bottom: 14px; }
    .section-title { font-size: 16px; }
    .section-more { font-size: 11px; }
    .badge-breaking,
    .badge-featured { font-size: 9px; padding: 4px 7px; }
    .article-meta { font-size: 10.5px; }
    .home-ad-media { height: 100%; }
    .home-ad-title { font-size: 13px; }
    .sidebar-widget { border-radius: 11px; }
    .currency-market-title{font-size:13.5px}
  }
  @media (prefers-reduced-motion: reduce) {
    .ad-animate,
    .ad-animate img,
    .ad-animate video {
      opacity: 1;
      transform: none !important;
      transition: none !important;
      animation: none !important;
    }
    .ad-animate::after { display: none; }
    .world-map-bg { animation: none; }
  }


</style>


<div class="main-content home-page">
  <div class="container">

    @include('partials.home-news-section', [
      'items' => $featuredArticles,
      'title' => 'الأخبار المميزة',
      'icon' => 'fa-star',
      'moreUrl' => route('search', ['featured' => 1]),
    ])

  <div class="home-layout">
    <div>

      @include('partials.home-news-section', [
        'items' => $latestArticles,
        'title' => 'آخر الأخبار',
        'icon' => 'fa-clock-rotate-left',
        'moreUrl' => route('search', ['sort' => 'newest']),
      ])

      @if($localCategorySection)
        @include('partials.home-news-section', [
          'items' => $localCategorySection->articles,
          'title' => $localCategorySection->name,
          'icon' => 'fa-layer-group',
          'moreUrl' => route('categories.show', $localCategorySection->slug),
        ])
      @endif

      @include('partials.home-news-section', [
        'items' => $articlesAndStories,
        'title' => 'المقالات والقصص',
        'icon' => 'fa-book-open-reader',
        'moreUrl' => route('content.index', 'articles-stories'),
      ])
      @include('partials.home-content-section', ['items' => $reports, 'type' => 'report', 'title' => 'التقارير', 'icon' => 'fa-chart-column'])

      @foreach($remainingCategorySections as $sectionCategory)
        @include('partials.home-news-section', [
          'items' => $sectionCategory->articles,
          'title' => $sectionCategory->name,
          'icon' => 'fa-layer-group',
          'moreUrl' => route('categories.show', $sectionCategory->slug),
        ])
      @endforeach

      @if($homepageAds->isNotEmpty())
      <section class="home-ad-carousel" id="home-ad-carousel" aria-label="الإعلانات">
        <div class="home-ad-viewport">
          <div class="home-ad-list" id="home-ad-list">
            @foreach($homepageAds as $ad)
              <article class="home-ad ad-animate" data-ad-reveal>
                @if($ad->link)
                  <a href="{{ $ad->link }}" target="_blank" rel="noopener noreferrer sponsored">
                @endif
                @if($ad->image_url && $ad->type === 'video')
                  <video class="home-ad-media" data-home-ad-video muted playsinline preload="auto" aria-label="{{ $ad->title }}" onerror="this.hidden=true;this.nextElementSibling.hidden=false">
                    <source src="{{ $ad->image_url }}">
                  </video>
                  <div class="home-ad-fallback" hidden><span class="home-ad-label">إعلان فيديو</span><span class="home-ad-title">تعذر تشغيل صيغة الفيديو</span></div>
                @elseif($ad->image_url)
                  <span class="home-ad-media-shell">
                    <img class="home-ad-media-backdrop" src="{{ $ad->image_url }}" alt="" aria-hidden="true">
                    <img class="home-ad-media" src="{{ $ad->image_url }}" alt="{{ $ad->title }}">
                  </span>
                @else
                  <div class="home-ad-fallback"><span class="home-ad-label">إعلان</span><span class="home-ad-title">{{ $ad->title }}</span></div>
                @endif
                @if($ad->link)
                  </a>
                @endif
              </article>
            @endforeach
          </div>
        </div>
        <div class="home-ad-dots" id="home-ad-dots"></div>
      </section>
      @endif

      @if($editorPicks->count())
      <div style="margin-bottom:32px">
        <div class="section-header">
          <div class="section-title"><i class="fa-solid fa-pen-nib"></i> {{ __('messages.section_editor_picks') }}</div>
        </div>
        <div class="editor-picks-grid">
          @foreach($editorPicks as $ep)
          <div class="article-card" style="flex-direction:row;align-items:stretch">
            <div style="width:80px;flex-shrink:0;background:rgba(200,154,43,.1);border-radius:10px 0 0 10px;overflow:hidden;border-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}:2px solid var(--gold)">
              @include('partials.article-image', ['article' => $ep, 'alt' => '', 'style' => 'width:100%;height:100%;object-fit:cover;padding:8px'])
            </div>
            <div class="article-card-body" style="padding:12px">
              <div style="font-size:10px;font-weight:700;color:var(--gold);margin-bottom:4px;letter-spacing:.3px">✏ {{ __('messages.badge_editor_pick') }}</div>
              <div class="article-title" style="font-size:12.5px"><a href="{{ route('articles.show',$ep->slug) }}">{{ Str::limit($ep->title,65) }}</a></div>
              <div class="article-meta"><span>{{ $ep->published_at?->diffForHumans() }}</span></div>
            </div>
          </div>
          @endforeach
        </div>
      </div>
      @endif


      @if($featuredVideos->count())
      <div>
        <div class="section-header">
          <div class="section-title"><i class="fa-solid fa-video"></i> {{ __('messages.section_videos') }}</div>
          <a href="{{ route('videos.index') }}" class="section-more">{{ __('messages.section_more') }} <i class="fa-solid fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}"></i></a>
        </div>
        <div class="articles-grid">
          @foreach($featuredVideos as $vid)
          <a href="{{ route('videos.show',$vid->slug) }}" class="article-card">
            <div class="article-card-img world-map-bg">
              @if($vid->thumbnail)
              <img src="{{ $vid->thumbnail }}" alt="{{ $vid->title }}" loading="lazy" onerror="this.style.display='none'" style="position:relative;z-index:1">
              @else
              <div style="height:100%;display:flex;align-items:center;justify-content:center;position:relative;z-index:1"><i class="fa-solid fa-play" style="font-size:36px;color:rgba(255,255,255,.3)"></i></div>
              @endif
              <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;z-index:2">
                <div style="width:48px;height:48px;background:rgba(200,154,43,.9);border-radius:50%;display:flex;align-items:center;justify-content:center">
                  <i class="fa-solid fa-play" style="color:var(--black);font-size:14px;margin-right:-2px"></i>
                </div>
              </div>
            </div>
            <div class="article-card-body">
              <div class="article-cat"><i class="fa-solid fa-video"></i> {{ $vid->category?->name ?? __('messages.section_videos') }}</div>
              <div class="article-title">{{ Str::limit($vid->title,68) }}</div>

            </div>
          </a>
          @endforeach
        </div>
      </div>
      @endif
    </div>


    <aside class="home-sidebar">

      <div class="sidebar-widget home-sidebar-trending">
        <div class="widget-header"><span class="widget-title"><i class="fa-solid fa-fire"></i> الأكثر قراءة</span></div>
        <div class="widget-body">
          @forelse($trendingArticles->take(6) as $i => $t)
          <a href="{{ route('articles.show',$t->slug) }}" class="widget-article">
            <div style="font-size:20px;font-weight:900;color:{{ $i<3?'var(--gold)':'rgba(255,255,255,.2)' }};width:28px;flex-shrink:0;text-align:center;font-family:'Inter',sans-serif">{{ $i+1 }}</div>
            <div class="widget-article-body">
              <div class="widget-article-title">{{ Str::limit($t->title,68) }}</div>

            </div>
          </a>
          @empty
          <div style="padding:20px;text-align:center;color:rgba(255,255,255,.25);font-size:13px">{{ __('messages.no_articles') }}</div>
          @endforelse
        </div>
      </div>


      <div class="sidebar-widget home-sidebar-opinions">
        <div class="widget-header">
          <span class="widget-title"><i class="fa-solid fa-pen-nib"></i> الآراء والمقالات</span>
        </div>
        <div class="widget-body">
          @forelse($opinionAndArticles->take(5) as $opinionOrArticle)
            @php
              $journalist = $opinionOrArticle->journalist;
              $journalistPhoto = $journalist?->photo_url;
              $hasJournalistPhoto = is_string($journalistPhoto) && trim($journalistPhoto) !== '';
            @endphp

            <a href="{{ route('articles.show', $opinionOrArticle->slug) }}" class="widget-article" style="gap:11px">
              <span style="width:46px;height:46px;flex:0 0 46px;position:relative;display:block">
                @if($hasJournalistPhoto)
                  <img
                    src="{{ $journalistPhoto }}"
                    alt="{{ $journalist?->name ?? '' }}"
                    loading="lazy"
                    style="position:absolute;inset:0;width:46px;height:46px;display:block;border-radius:50%;object-fit:cover;border:2px solid var(--gold)"
                    onerror="this.style.display='none';this.nextElementSibling.style.display='grid';"
                  >
                  <span style="position:absolute;inset:0;width:46px;height:46px;display:none;place-items:center;border-radius:50%;background:rgba(200,154,43,.12);color:var(--gold);border:1px solid rgba(200,154,43,.25)">
                    <i class="fa-solid fa-user-pen"></i>
                  </span>
                @else
                  <span style="position:absolute;inset:0;width:46px;height:46px;display:grid;place-items:center;border-radius:50%;background:rgba(200,154,43,.12);color:var(--gold);border:1px solid rgba(200,154,43,.25)">
                    <i class="fa-solid fa-user-pen"></i>
                  </span>
                @endif
              </span>

              <span class="widget-article-body">
                <strong class="widget-article-title">{{ Str::limit($opinionOrArticle->title, 62) }}</strong>
                <small style="color:var(--text-muted)">
                  {{ $journalist?->name ?? 'وكالة الأصيل' }} · {{ $opinionOrArticle->content_type_label }}
                </small>
              </span>
            </a>
          @empty
            <div style="padding:18px;color:var(--text-muted);font-size:13px">ستظهر هنا أحدث الآراء والمقالات.</div>
          @endforelse

          <a href="{{ route('content.index', 'opinions-articles') }}" class="btn btn-outline" style="margin:10px;width:calc(100% - 20px);justify-content:center">
            استكشف الآراء والمقالات
          </a>
        </div>
      </div>

      <div class="sidebar-widget home-sidebar-weather" id="weather-widget">
        <div class="widget-header"><span class="widget-title"><i class="fa-solid fa-cloud-sun"></i> الطقس</span></div>
        <div style="padding:20px;text-align:center">
          <div style="font-size:13px;color:var(--text-muted)">غزة، فلسطين</div>
          <div id="weather-temp" style="font-size:38px;font-weight:900;color:var(--gold);margin:8px 0">--°</div>
          <div id="weather-desc" style="font-size:13px">جارٍ تحديث حالة الطقس...</div>
          <div class="weather-details">
            <div class="weather-detail"><i class="fa-solid fa-droplet"></i> الرطوبة<strong id="weather-humidity">--%</strong></div>
            <div class="weather-detail"><i class="fa-solid fa-wind"></i> سرعة الرياح<strong id="weather-wind">-- كم/س</strong></div>
          </div>
        </div>
      </div>

      <section class="currency-market home-sidebar-currencies" id="currency-market" aria-labelledby="currency-market-heading">
        <div class="currency-market-head">
          <div class="currency-market-title" id="currency-market-heading"><i class="fa-solid fa-money-bill-trend-up"></i><span>أسعار العملات اليوم</span></div>
          <div class="currency-market-meta"><span class="currency-live-dot"></span><span id="currency-market-status">جاري تحديث السوق…</span></div>
        </div>
        <div class="currency-list" id="currency-market-body">
          @foreach([
            ['code'=>'USD/ILS','name'=>'دولار / شيكل'],
            ['code'=>'JOD/ILS','name'=>'دينار أردني / شيكل'],
            ['code'=>'EUR/ILS','name'=>'يورو / شيكل'],
            ['code'=>'GBP/ILS','name'=>'إسترليني / شيكل'],
            ['code'=>'CHF/ILS','name'=>'فرنك / شيكل'],
            ['code'=>'EGP/ILS','name'=>'جنيه مصري / شيكل'],
            ['code'=>'USD/EGP','name'=>'دولار / جنيه مصري'],
          ] as $currency)
          <div class="currency-row" data-currency-pair="{{ $currency['code'] }}">
            <div class="currency-row-head"><div class="currency-name"><span class="currency-code">{{ $currency['code'] }}</span><span class="currency-name-text">{{ $currency['name'] }}</span></div><span class="currency-trend is-flat" data-trend>—</span></div>
            <div class="currency-prices"><div class="currency-price"><span>شراء</span><strong class="currency-rate" data-buy>—</strong></div><div class="currency-price"><span>بيع</span><strong class="currency-rate" data-sell>—</strong></div></div>
          </div>
          @endforeach
        </div>
        <div class="currency-market-note">أسعار استرشادية · تحديث تلقائي كل 30 دقيقة</div>
      </section>


      @if($sidebarAds->isNotEmpty())
      @foreach($sidebarAds as $ad)
      <div class="sidebar-ad ad-animate home-sidebar-ads" data-ad-reveal style="--ad-delay: {{ min($loop->index * 80, 320) }}ms">
        @if($ad->link)<a href="{{ $ad->link }}" target="_blank" rel="noopener noreferrer sponsored">@endif
          @if($ad->image_url && $ad->type === 'video')
          <video class="sidebar-ad-media world-map-bg" autoplay muted loop playsinline preload="metadata"
                 aria-label="{{ $ad->title }}"
                 onerror="this.hidden=true;this.nextElementSibling.hidden=false">
            <source src="{{ $ad->image_url }}">
          </video>
          <div class="home-ad-fallback" hidden>
            <span class="home-ad-label">{{ __('messages.advertisement') }}</span>
            <span class="home-ad-title">{{ $ad->title }}</span>
          </div>
          @elseif($ad->image_url)
          <img class="sidebar-ad-media world-map-bg" src="{{ $ad->image_url }}" alt="{{ $ad->title }}" loading="lazy"
               onerror="this.hidden=true;this.nextElementSibling.hidden=false">
          <div class="home-ad-fallback" hidden>
            <span class="home-ad-label">{{ __('messages.advertisement') }}</span>
            <span class="home-ad-title">{{ $ad->title }}</span>
          </div>
          @else
          <div class="home-ad-fallback world-map-bg">
            <span class="home-ad-label">{{ __('messages.advertisement') }}</span>
            <span class="home-ad-title">{{ $ad->title }}</span>
          </div>
          @endif
        @if($ad->link)</a>@endif
      </div>
      @endforeach
      @endif


      <div class="sidebar-widget home-sidebar-newsletter" style="background:var(--surface2)">
        <div style="padding:22px;text-align:center">
          <i class="fa-solid fa-envelope-open-text" style="font-size:26px;color:var(--gold);margin-bottom:10px;display:block"></i>
          <div style="font-size:14px;font-weight:700;margin-bottom:5px;color:var(--white)">{{ __('messages.newsletter_widget_title') }}</div>
          <div style="font-size:12px;color:rgba(255,255,255,.4);margin-bottom:14px">{{ __('messages.newsletter_widget_sub') }}</div>
          <form action="{{ route('newsletter.subscribe') }}" method="POST">
            @csrf
            <input type="email" name="email" placeholder="{{ __('messages.newsletter_placeholder') }}" required
                   style="width:100%;padding:9px 12px;border-radius:7px;border:1px solid var(--border);font-family:'Cairo',sans-serif;font-size:13px;margin-bottom:8px;direction:{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }};background:var(--surface);color:var(--white)">
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">{{ __('messages.newsletter_btn') }}</button>
          </form>
        </div>
      </div>
    </aside>
  </div>

</div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const currencyRows=[...document.querySelectorAll('[data-currency-pair]')];
    const currencyStatus=document.getElementById('currency-market-status');
    const currencyCacheKey='alaseel_currency_market_v1';
    const renderCurrencies=(payload,isCached=false)=>{
      if(!payload?.rates)return;
      const previous=JSON.parse(localStorage.getItem(currencyCacheKey)||'null');
      currencyRows.forEach((row)=>{
        const pair=row.dataset.currencyPair;
        const mid=Number(payload.rates[pair]);
        if(!Number.isFinite(mid))return;
        const buy=mid*(1-.0035);
        const sell=mid*(1+.0035);
        row.querySelector('[data-buy]').textContent=buy.toFixed(4);
        row.querySelector('[data-sell]').textContent=sell.toFixed(4);
        const oldMid=Number(previous?.rates?.[pair]);
        const trend=row.querySelector('[data-trend]');
        trend.className='currency-trend is-flat';
        trend.textContent='—';
        if(Number.isFinite(oldMid)&&oldMid!==mid){
          const change=((mid-oldMid)/oldMid)*100;
          trend.className=`currency-trend ${change>0?'is-up':'is-down'}`;
          trend.innerHTML=`<i class="fa-solid fa-arrow-${change>0?'up':'down'}"></i> ${Math.abs(change).toFixed(2)}%`;
        }
      });
      const updatedAt=new Date(payload.updatedAt||Date.now());
      currencyStatus.textContent=`${isCached?'آخر بيانات محفوظة':'آخر تحديث'}: ${updatedAt.toLocaleTimeString('ar-PS',{hour:'2-digit',minute:'2-digit'})}`;
      if(!isCached)localStorage.setItem(currencyCacheKey,JSON.stringify(payload));
    };
    const fetchCurrencies=()=>fetch('https://api.frankfurter.dev/v2/rates?base=USD&quotes=ILS,JOD,EUR,GBP,CHF,EGP',{headers:{Accept:'application/json'}})
      .then((response)=>response.ok?response.json():Promise.reject(new Error('currency-api')))
      .then((data)=>{
        const list=Array.isArray(data)?data:[];
        const usd=Object.fromEntries(list.map((item)=>[item.quote,Number(item.rate)]));
        if(!usd.ILS||!usd.JOD||!usd.EUR||!usd.GBP||!usd.CHF||!usd.EGP)throw new Error('currency-data');
        renderCurrencies({updatedAt:Date.now(),rates:{
          'USD/ILS':usd.ILS,
          'JOD/ILS':usd.ILS/usd.JOD,
          'EUR/ILS':usd.ILS/usd.EUR,
          'GBP/ILS':usd.ILS/usd.GBP,
          'CHF/ILS':usd.ILS/usd.CHF,
          'EGP/ILS':usd.ILS/usd.EGP,
          'USD/EGP':usd.EGP,
        }});
      })
      .catch(()=>{
        const cached=JSON.parse(localStorage.getItem(currencyCacheKey)||'null');
        if(cached){renderCurrencies(cached,true);return;}
        if(currencyStatus){currencyStatus.textContent='تعذر تحديث الأسعار الآن';currencyStatus.classList.add('currency-market-error');}
      });
    if(currencyRows.length){fetchCurrencies();setInterval(fetchCurrencies,1800000);}

    const ads = document.querySelectorAll('[data-ad-reveal]');
    ads.forEach((ad) => ad.classList.add('is-visible'));

    const list = document.getElementById('home-ad-list');
    const carousel = document.getElementById('home-ad-carousel');
    const dots = document.getElementById('home-ad-dots');
    if (list && carousel) {
      const cards = [...list.children];
      let index = 0;
      let timer;
      const perView = () => 1;
      const pages = () => Math.max(1, cards.length - perView() + 1);
      const stopVideos = (except = null) => cards.forEach((card) => {
        const video = card.querySelector('[data-home-ad-video]');
        if (!video || video === except) return;
        video.pause();
        try { video.currentTime = 0; } catch (error) {}
      });
      const schedule = () => {
        clearTimeout(timer);
        const activeVideo = cards[index]?.querySelector('[data-home-ad-video]');
        stopVideos(activeVideo);
        if (activeVideo) {
          activeVideo.muted = true;
          try { activeVideo.currentTime = 0; } catch (error) {}
          activeVideo.onended = () => { index = (index + 1) % pages(); move(); };
          activeVideo.play().catch(() => {
            timer = setTimeout(() => { index = (index + 1) % pages(); move(); }, 6000);
          });
          return;
        }
        timer = setTimeout(() => { index = (index + 1) % pages(); move(); }, 6000);
      };
      const drawDots = () => { dots.innerHTML = ''; for(let i=0;i<pages();i++){const button=document.createElement('button');button.className='home-ad-dot'+(i===index?' is-active':'');button.setAttribute('aria-label','الإعلان '+(i+1));button.onclick=()=>{index=i;move();};dots.appendChild(button);} };
      const move = () => { index %= pages(); const card = cards[0]; if(!card)return; list.style.transform=`translateX(-${index*(card.getBoundingClientRect().width+14)}px)`; drawDots(); schedule(); };
      addEventListener('resize',()=>{index=0;move();});
      move();
    }

    fetch('https://api.open-meteo.com/v1/forecast?latitude=31.50&longitude=34.47&current=temperature_2m,relative_humidity_2m,wind_speed_10m,weather_code&timezone=Asia%2FHebron')
      .then(response=>response.ok?response.json():Promise.reject())
      .then(data=>{
        const current=data.current||{};
        const weatherNames={0:'سماء صافية',1:'غائم جزئيًا',2:'غائم جزئيًا',3:'غائم',45:'ضباب',48:'ضباب',51:'رذاذ خفيف',53:'رذاذ',55:'رذاذ كثيف',61:'أمطار خفيفة',63:'أمطار',65:'أمطار غزيرة',80:'زخات مطر',81:'زخات مطر',82:'زخات غزيرة',95:'عواصف رعدية'};
        const temp=document.getElementById('weather-temp');
        const desc=document.getElementById('weather-desc');
        const humidity=document.getElementById('weather-humidity');
        const wind=document.getElementById('weather-wind');
        if(temp)temp.textContent=Math.round(current.temperature_2m)+'°';
        if(desc)desc.textContent=weatherNames[current.weather_code]||'حالة الطقس الحالية في غزة';
        if(humidity)humidity.textContent=Math.round(current.relative_humidity_2m)+'%';
        if(wind)wind.textContent=Math.round(current.wind_speed_10m)+' كم/س';
      })
      .catch(()=>{const desc=document.getElementById('weather-desc');if(desc)desc.textContent='تعذر تحديث الطقس الآن';});

  });
</script>
@endsection