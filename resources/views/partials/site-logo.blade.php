@php
    $logoSetting = \App\Models\Setting::query()->where('key', 'site_logo')->first();
    $savedLogo = $logoSetting?->value;

    $siteLogoUrl = filled($savedLogo) && filter_var($savedLogo, FILTER_VALIDATE_URL)
        ? $savedLogo
        : route('site.logo', ['v' => $logoSetting?->updated_at?->timestamp ?? 1]);

    $siteName = \App\Models\Setting::get(
        'site_name',
        config('app.name')
    );
@endphp

<img
    src="{{ $siteLogoUrl }}"
    alt="{{ $siteName }}"
    class="{{ $class ?? 'site-logo' }}"
    style="{{ $style ?? '' }}"
    onerror="this.onerror=null;this.src='{{ asset('images/logo.png') }}';"
>
