<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class SiteAssetController extends Controller
{
    /**
     * صورة المقال الموحدة 1200×630.
     *
     * يتم دمج شعار الموقع داخل النسخة المعروضة نفسها، حتى يبقى
     * موجودًا عند حفظ الصورة مباشرة من المتصفح أو مشاركتها.
     */
    public function articleImage(Article $article): Response
    {
        $articleVersion = $article->updated_at?->timestamp ?? 1;
        $logoSetting = Setting::query()->where('key', 'site_logo')->first();
        $logoVersion = $logoSetting?->updated_at?->timestamp ?? 1;

        /*
         * اسم الكاش يتغير عند تعديل المقال أو شعار الموقع.
         */
        $destination = "generated/article-images/v4-{$article->id}-{$articleVersion}-{$logoVersion}.jpg";

        if (! Storage::disk('public')->exists($destination)) {
            $this->generateArticleImage($article, $destination, true);
        }

        /*
         * إذا تعذر إنشاء الصورة بواسطة GD،
         * نعرض الصورة الأصلية مباشرة.
         */
        if (! Storage::disk('public')->exists($destination)) {
            $original = $this->localArticleImagePath($article);

            if ($original !== null && is_file($original)) {
                return response()->file($original, [
                    'Content-Type' => mime_content_type($original) ?: 'image/jpeg',

                    'Content-Disposition' =>
                        'inline; filename="article-'.$article->id.'-original.'
                        .pathinfo($original, PATHINFO_EXTENSION).'"',

                    'Cache-Control' => 'public, max-age=86400',
                ]);
            }

            /*
             * دعم الصور الخارجية.
             */
            $external = $article->getRawOriginal('main_image');

            if (
                is_string($external) &&
                filter_var($external, FILTER_VALIDATE_URL)
            ) {
                return redirect()->away($external);
            }

            /*
             * إذا لم توجد صورة للمقال نهائيًا
             * نستخدم شعار الموقع كصورة احتياطية فقط.
             */
            return $this->logo();
        }

        return response()->file(
            Storage::disk('public')->path($destination),
            [
                'Content-Type' => 'image/jpeg',

                'Content-Disposition' =>
                    'inline; filename="article-'.$article->id.'.jpg"',

                'Cache-Control' =>
                    'public, max-age=31536000, immutable',
            ]
        );
    }

    /**
     * عرض ملفات media الموجودة داخل storage/public.
     */
    public function media(Request $request): Response
    {
        $path = (string) $request->query('path', '');

        $path = str_replace('\\', '/', trim($path));

        $path = preg_replace('#^.*?/storage/app/public/#', '', $path);
        $path = preg_replace('#^.*?/public/storage/#', '', $path);
        $path = preg_replace('#^/?public/#', '', $path);

        $path = preg_replace(
            '#^/?storage/#',
            '',
            $path
        );

        $path = ltrim((string) $path, '/');

        abort_if(
            $path === '' || str_contains($path, '..'),
            404
        );

        abort_unless(
            Storage::disk('public')->exists($path),
            404
        );

        $response = response()->file(
            Storage::disk('public')->path($path),
            [
                'Content-Type' =>
                    Storage::disk('public')->mimeType($path)
                    ?: 'application/octet-stream',

                'Cache-Control' =>
                    'public, max-age=86400',

                'Content-Disposition' =>
                    'inline; filename="'.basename($path).'"',
            ]
        );

        $response->headers->set(
            'Accept-Ranges',
            'bytes'
        );

        return $response;
    }

    /**
     * عرض شعار الموقع.
     */
    public function logo(): Response
    {
        $savedLogo = (string) Setting::get(
            'site_logo',
            ''
        );

        /*
         * إذا كان الشعار عبارة عن رابط خارجي.
         */
        if (
            filter_var(
                $savedLogo,
                FILTER_VALIDATE_URL
            )
        ) {
            return redirect()->away($savedLogo);
        }

        $path = str_replace(
            '\\',
            '/',
            trim($savedLogo)
        );

        $path = preg_replace(
            '#^/?storage/app/public/#',
            '',
            $path
        );

        $path = preg_replace(
            '#^/?public/#',
            '',
            $path
        );

        $path = preg_replace(
            '#^/?storage/#',
            '',
            $path
        );

        $path = ltrim(
            (string) $path,
            '/'
        );

        /*
         * شعار الموقع المحفوظ في الإعدادات.
         */
        if (
            $path !== '' &&
            Storage::disk('public')->exists($path)
        ) {
            return response()->file(
                Storage::disk('public')->path($path),
                [
                    'Content-Type' =>
                        Storage::disk('public')->mimeType($path)
                        ?: 'image/png',

                    'Cache-Control' =>
                        'public, max-age=3600',
                ]
            );
        }

        /*
         * الشعار الاحتياطي.
         */
        $fallback = public_path(
            'images/logo.png'
        );

        abort_unless(
            is_file($fallback),
            404
        );

        return response()->file(
            $fallback,
            [
                'Content-Type' =>
                    mime_content_type($fallback)
                    ?: 'image/png',

                'Cache-Control' =>
                    'public, max-age=3600',
            ]
        );
    }

    /**
     * إنشاء وعرض أيقونة الموقع.
     */
    public function icon(int $size): Response
    {
        abort_unless(
            in_array(
                $size,
                [32, 180, 192, 512],
                true
            ),
            404
        );

        $iconPath =
            "settings/icons/site-icon-{$size}.png";

        if (
            ! Storage::disk('public')
                ->exists($iconPath)
        ) {
            $this->generateIcon(
                $iconPath,
                $size
            );
        }

        if (
            Storage::disk('public')
                ->exists($iconPath)
        ) {
            return response()->file(
                Storage::disk('public')
                    ->path($iconPath),
                [
                    'Content-Type' =>
                        'image/png',

                    'Cache-Control' =>
                        'public, max-age=31536000, immutable',
                ]
            );
        }

        return $this->logo();
    }

    /**
     * Manifest الخاص بالموقع.
     */
    public function manifest(): JsonResponse
    {
        $siteName = (string) Setting::get(
            'site_name',
            'وكالة الأصيل الإخبارية'
        );

        return response()->json(
            [
                'name' => $siteName,

                'short_name' => $siteName,

                'start_url' => '/',

                'display' => 'standalone',

                'background_color' => '#0B0B0B',

                'theme_color' => '#0B0B0B',

                'icons' => [
                    [
                        'src' => route(
                            'site.icon',
                            ['size' => 192]
                        ),

                        'sizes' => '192x192',

                        'type' => 'image/png',
                    ],

                    [
                        'src' => route(
                            'site.icon',
                            ['size' => 512]
                        ),

                        'sizes' => '512x512',

                        'type' => 'image/png',
                    ],
                ],
            ],
            200,
            [
                'Content-Type' =>
                    'application/manifest+json; charset=UTF-8',

                'Cache-Control' =>
                    'public, max-age=3600',
            ],
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * إنشاء أيقونة مربعة من شعار الموقع.
     */
    private function generateIcon(
        string $destination,
        int $size
    ): void {
        if (
            ! function_exists(
                'imagecreatefromstring'
            )
        ) {
            return;
        }

        $logoPath = $this->localLogoPath();

        if (
            $logoPath === null ||
            ! is_file($logoPath)
        ) {
            return;
        }

        $contents =
            @file_get_contents($logoPath);

        $source =
            $contents !== false
                ? @imagecreatefromstring($contents)
                : false;

        if ($source === false) {
            return;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        if (
            $sourceWidth < 1 ||
            $sourceHeight < 1
        ) {
            imagedestroy($source);

            return;
        }

        $canvas =
            imagecreatetruecolor(
                $size,
                $size
            );

        imagealphablending(
            $canvas,
            false
        );

        imagesavealpha(
            $canvas,
            true
        );

        $transparent =
            imagecolorallocatealpha(
                $canvas,
                0,
                0,
                0,
                127
            );

        imagefilledrectangle(
            $canvas,
            0,
            0,
            $size,
            $size,
            $transparent
        );

        $innerSize =
            (int) round(
                $size * .86
            );

        $scale = min(
            $innerSize / $sourceWidth,
            $innerSize / $sourceHeight
        );

        $targetWidth = max(
            1,
            (int) round(
                $sourceWidth * $scale
            )
        );

        $targetHeight = max(
            1,
            (int) round(
                $sourceHeight * $scale
            )
        );

        $targetX =
            (int) floor(
                ($size - $targetWidth) / 2
            );

        $targetY =
            (int) floor(
                ($size - $targetHeight) / 2
            );

        imagecopyresampled(
            $canvas,
            $source,
            $targetX,
            $targetY,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight
        );

        Storage::disk('public')
            ->makeDirectory(
                'settings/icons'
            );

        imagepng(
            $canvas,
            Storage::disk('public')
                ->path($destination),
            9
        );

        imagedestroy($source);
        imagedestroy($canvas);
    }

    /**
     * إنشاء نسخة 1200×630 من صورة المقال.
     *
     * النسخة العادية بلا شعار، ونسخة التنزيل يمكن توليدها بالشعار.
     */
    private function generateArticleImage(
        Article $article,
        string $destination,
        bool $withLogo = false
    ): void {
        if (
            ! function_exists(
                'imagecreatefromstring'
            )
        ) {
            return;
        }

        /*
         * الحصول على الصورة الأصلية للمقال.
         */
        $articlePath =
            $this->localArticleImagePath(
                $article
            );

        if (
            $articlePath === null ||
            ! is_file($articlePath)
        ) {
            return;
        }

        $contents =
            @file_get_contents(
                $articlePath
            );

        if ($contents === false) {
            return;
        }

        $source =
            @imagecreatefromstring(
                $contents
            );

        /*
         * إذا لم يستطع GD قراءة الصورة،
         * نترك articleImage() يعرض الأصل.
         */
        if ($source === false) {
            return;
        }

        $sourceWidth =
            imagesx($source);

        $sourceHeight =
            imagesy($source);

        if (
            $sourceWidth < 1 ||
            $sourceHeight < 1
        ) {
            imagedestroy($source);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | المقاس النهائي
        |--------------------------------------------------------------------------
        */

        $width = 1200;
        $height = 630;

        $canvas =
            imagecreatetruecolor(
                $width,
                $height
            );

        $background =
            imagecolorallocate(
                $canvas,
                12,
                12,
                12
            );

        imagefilledrectangle(
            $canvas,
            0,
            0,
            $width,
            $height,
            $background
        );

        /*
        |--------------------------------------------------------------------------
        | Cover
        |--------------------------------------------------------------------------
        |
        | ملء مساحة 1200×630 مع الحفاظ
        | على نسبة أبعاد الصورة.
        |
        */

        $scale = max(
            $width / $sourceWidth,
            $height / $sourceHeight
        );

        $targetWidth = max(
            1,
            (int) round(
                $sourceWidth * $scale
            )
        );

        $targetHeight = max(
            1,
            (int) round(
                $sourceHeight * $scale
            )
        );

        $targetX =
            (int) floor(
                ($width - $targetWidth) / 2
            );

        $targetY =
            (int) floor(
                ($height - $targetHeight) / 2
            );

        imagecopyresampled(
            $canvas,
            $source,
            $targetX,
            $targetY,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight
        );

        imagedestroy($source);

        if ($withLogo) {
            $this->placeLogoOnArticleImage($canvas, $width, $height);
        }

        Storage::disk('public')
            ->makeDirectory(
                'generated/article-images'
            );

        imagejpeg(
            $canvas,
            Storage::disk('public')
                ->path($destination),
            90
        );

        imagedestroy($canvas);
    }

    /**
     * دمج الشعار أسفل يمين الصورة مع الحفاظ على الشفافية ونسبة الأبعاد.
     */
    private function placeLogoOnArticleImage(\GdImage $canvas, int $canvasWidth, int $canvasHeight): void
    {
        $logoPath = $this->localLogoPath();

        if ($logoPath === null || ! is_file($logoPath)) {
            return;
        }

        $logoContents = @file_get_contents($logoPath);
        $logo = $logoContents !== false ? @imagecreatefromstring($logoContents) : false;

        if ($logo === false) {
            return;
        }

        $logoWidth = imagesx($logo);
        $logoHeight = imagesy($logo);

        if ($logoWidth < 1 || $logoHeight < 1) {
            imagedestroy($logo);
            return;
        }

        $maxWidth = 240;
        $maxHeight = 115;
        $scale = min($maxWidth / $logoWidth, $maxHeight / $logoHeight);
        $targetWidth = max(1, (int) round($logoWidth * $scale));
        $targetHeight = max(1, (int) round($logoHeight * $scale));
        $padding = 28;
        $targetX = max(0, $canvasWidth - $targetWidth - $padding);
        $targetY = max(0, $canvasHeight - $targetHeight - $padding);

        imagealphablending($canvas, true);
        imagecopyresampled(
            $canvas,
            $logo,
            $targetX,
            $targetY,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $logoWidth,
            $logoHeight
        );

        imagedestroy($logo);
    }

    /**
     * الحصول على المسار المحلي لصورة المقال.
     */
    private function localArticleImagePath(
        Article $article
    ): ?string {
        $path =
            $article->mainImageMedia?->file_path
            ?: $article->getRawOriginal(
                'main_image'
            );

        if (
            blank($path) ||
            filter_var(
                $path,
                FILTER_VALIDATE_URL
            )
        ) {
            return null;
        }

        $path = str_replace(
            '\\',
            '/',
            trim((string) $path)
        );

        $path = preg_replace(
            '#^/?(?:storage/app/public/|public/|storage/)+#',
            '',
            $path
        );

        $path = ltrim(
            (string) $path,
            '/'
        );

        return
            $path !== '' &&
            Storage::disk('public')
                ->exists($path)

                ? Storage::disk('public')
                    ->path($path)

                : null;
    }

    /**
     * الحصول على المسار المحلي لشعار الموقع.
     *
     * تستخدم هذه الدالة للأيقونات ولنسخة صورة المقال القابلة للتنزيل.
     */
    private function localLogoPath(): ?string
    {
        $savedLogo =
            (string) Setting::get(
                'site_logo',
                ''
            );

        if (
            $savedLogo !== '' &&
            ! filter_var(
                $savedLogo,
                FILTER_VALIDATE_URL
            )
        ) {
            $path = str_replace(
                '\\',
                '/',
                trim($savedLogo)
            );

            $path = preg_replace(
                '#^/?(?:storage/app/public/|public/|storage/)+#',
                '',
                $path
            );

            $path = ltrim(
                (string) $path,
                '/'
            );

            if (
                $path !== '' &&
                Storage::disk('public')
                    ->exists($path)
            ) {
                return Storage::disk('public')
                    ->path($path);
            }
        }

        $fallback =
            public_path(
                'images/logo.png'
            );

        return is_file($fallback)
            ? $fallback
            : null;
    }
}
