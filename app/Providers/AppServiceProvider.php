<?php

namespace App\Providers;

use App\Models\Advertisement;
use App\Models\Article;
use App\Models\BreakingAlert;
use App\Models\Category;
use App\Models\LiveStream;
use App\Models\Notification;
use App\Models\Setting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Paginator::useBootstrapFive();

        /*
        |--------------------------------------------------------------------------
        | إعدادات الموقع
        |--------------------------------------------------------------------------
        */

        View::composer('*', function ($view) {
            $locale = app()->getLocale();

            $siteSettings = cache()->remember(
                'site_settings_'.$locale,
                300,
                function () {
                    $settings = Setting::query()->get();

                    $values = $settings
                        ->mapWithKeys(fn (Setting $setting) => [
                            $setting->key => $setting->value,
                        ])
                        ->toArray();

                    $values['_site_logo_version'] = $settings
                        ->firstWhere('key', 'site_logo')
                        ?->updated_at
                        ?->timestamp ?? 1;

                    return $values;
                }
            );

            $view->with('siteSettings', $siteSettings);
        });

        /*
        |--------------------------------------------------------------------------
        | بيانات الواجهة العامة
        |--------------------------------------------------------------------------
        */

        View::composer('layouts.app', function ($view) {

            /*
            |--------------------------------------------------------------------------
            | أقسام الهيدر
            |--------------------------------------------------------------------------
            */

            $navCategories = Category::query()
                ->active()
                ->root()
                ->where('show_in_header', true)
                ->with([
                    'children' => function ($query) {
                        $query
                            ->active()
                            ->orderBy('sort_order')
                            ->orderBy('name');
                    },
                ])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            /*
            |--------------------------------------------------------------------------
            | أقسام الفوتر
            |--------------------------------------------------------------------------
            */

            $footerCategories = Category::query()
                ->forFooter()
                ->limit(10)
                ->get();

            /*
            |--------------------------------------------------------------------------
            | البث المباشر
            |--------------------------------------------------------------------------
            */

            $activeStream = LiveStream::active();

            /*
            |--------------------------------------------------------------------------
            | آخر المستجدات
            |--------------------------------------------------------------------------
            |
            | آخر الأخبار المنشورة في الموقع.
            | لا تعتمد على is_breaking.
            |
            */

            $globalLatestUpdates = Article::query()
                ->published()
                ->with([
                    'category',
                    'journalist',
                    'mainImageMedia',
                    'translations',
                ])
                ->latest('published_at')
                ->limit(12)
                ->get();

            /*
            |--------------------------------------------------------------------------
            | الأخبار العاجلة
            |--------------------------------------------------------------------------
            |
            | visible() يتحقق من:
            | is_active
            | starts_at
            | expires_at
            |
            */

            $globalBreakingAlerts = BreakingAlert::query()
                ->visible()
                ->latest('starts_at')
                ->limit(10)
                ->get();

            /*
            |--------------------------------------------------------------------------
            | الإعلانات المشتركة
            |--------------------------------------------------------------------------
            */

            $layoutAds = Advertisement::query()
                ->active()
                ->whereIn('position', [
                    'header',
                    'footer',
                    'popup',
                ])
                ->latest()
                ->get()
                ->groupBy('position');

            $headerAds = $layoutAds->get(
                'header',
                collect()
            );

            $footerAds = $layoutAds->get(
                'footer',
                collect()
            );

            $popupAds = $layoutAds->get(
                'popup',
                collect()
            );

            /*
            |--------------------------------------------------------------------------
            | إرسال البيانات للـ Layout
            |--------------------------------------------------------------------------
            */

            $view->with(compact(
                'navCategories',
                'footerCategories',
                'activeStream',
                'globalLatestUpdates',
                'globalBreakingAlerts',
                'headerAds',
                'footerAds',
                'popupAds'
            ));
        });

        /*
        |--------------------------------------------------------------------------
        | بيانات لوحة الإدارة
        |--------------------------------------------------------------------------
        */

        View::composer('layouts.admin', function ($view) {

            $adminUnreadCount = Notification::query()
                ->whereNull('read_at')
                ->count();

            $adminRecentNotifications = Notification::query()
                ->latest()
                ->limit(8)
                ->get();

            $view->with(compact(
                'adminUnreadCount',
                'adminRecentNotifications'
            ));
        });
    }
}