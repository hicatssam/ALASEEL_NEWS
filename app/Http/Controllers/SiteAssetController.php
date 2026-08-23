<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class SiteAssetController extends Controller
{
    public function media(Request $request): Response
    {
        $path = (string) $request->query('path', '');
        $path = str_replace('\\', '/', trim($path));
        $path = preg_replace('#^/?storage/app/public/#', '', $path);
        $path = preg_replace('#^/?public/#', '', $path);
        $path = preg_replace('#^/?storage/#', '', $path);
        $path = ltrim((string) $path, '/');

        abort_if($path === '' || str_contains($path, '..'), 404);
        abort_unless(Storage::disk('public')->exists($path), 404);

        $response = response()->file(
            Storage::disk('public')->path($path),
            [
                'Content-Type' => Storage::disk('public')->mimeType($path) ?: 'application/octet-stream',
                'Cache-Control' => 'public, max-age=86400',
                'Content-Disposition' => 'inline; filename="'.basename($path).'"',
            ]
        );

        $response->headers->set('Accept-Ranges', 'bytes');

        return $response;
    }

    public function logo(): Response
    {
        $savedLogo = (string) Setting::get('site_logo', '');

        if (filter_var($savedLogo, FILTER_VALIDATE_URL)) {
            return redirect()->away($savedLogo);
        }

        $path = str_replace('\\', '/', trim($savedLogo));
        $path = preg_replace('#^/?storage/app/public/#', '', $path);
        $path = preg_replace('#^/?public/#', '', $path);
        $path = preg_replace('#^/?storage/#', '', $path);
        $path = ltrim((string) $path, '/');

        if ($path !== '' && Storage::disk('public')->exists($path)) {
            return response()->file(
                Storage::disk('public')->path($path),
                [
                    'Content-Type' => Storage::disk('public')->mimeType($path) ?: 'image/png',
                    'Cache-Control' => 'public, max-age=3600',
                ]
            );
        }

        $fallback = public_path('images/logo.png');
        abort_unless(is_file($fallback), 404);

        return response()->file($fallback, [
            'Content-Type' => mime_content_type($fallback) ?: 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * إنشاء أيقونة مربعة من شعار الإعدادات مع الحفاظ على نسبته.
     */
    public function icon(int $size): Response
    {
        abort_unless(in_array($size, [32, 180, 192, 512], true), 404);

        $iconPath = "settings/icons/site-icon-{$size}.png";

        if (! Storage::disk('public')->exists($iconPath)) {
            $this->generateIcon($iconPath, $size);
        }

        if (Storage::disk('public')->exists($iconPath)) {
            return response()->file(Storage::disk('public')->path($iconPath), [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ]);
        }

        return $this->logo();
    }

    /**
     * Manifest ديناميكي يستخدم الأيقونات الناتجة من شعار الإعدادات.
     */
    public function manifest(): JsonResponse
    {
        $siteName = (string) Setting::get('site_name', 'وكالة الأصيل الإخبارية');

        return response()->json([
            'name' => $siteName,
            'short_name' => $siteName,
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#0B0B0B',
            'theme_color' => '#0B0B0B',
            'icons' => [
                [
                    'src' => route('site.icon', ['size' => 192]),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                ],
                [
                    'src' => route('site.icon', ['size' => 512]),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                ],
            ],
        ], 200, [
            'Content-Type' => 'application/manifest+json; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function generateIcon(string $destination, int $size): void
    {
        if (! function_exists('imagecreatefromstring')) {
            return;
        }

        $logoPath = $this->localLogoPath();

        if ($logoPath === null || ! is_file($logoPath)) {
            return;
        }

        $contents = @file_get_contents($logoPath);
        $source = $contents !== false ? @imagecreatefromstring($contents) : false;

        if ($source === false) {
            return;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        if ($sourceWidth < 1 || $sourceHeight < 1) {
            imagedestroy($source);
            return;
        }

        $canvas = imagecreatetruecolor($size, $size);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $size, $size, $transparent);

        $innerSize = (int) round($size * .86);
        $scale = min($innerSize / $sourceWidth, $innerSize / $sourceHeight);
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $targetX = (int) floor(($size - $targetWidth) / 2);
        $targetY = (int) floor(($size - $targetHeight) / 2);

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

        Storage::disk('public')->makeDirectory('settings/icons');
        imagepng($canvas, Storage::disk('public')->path($destination), 9);

        imagedestroy($source);
        imagedestroy($canvas);
    }

    private function localLogoPath(): ?string
    {
        $savedLogo = (string) Setting::get('site_logo', '');

        if ($savedLogo !== '' && ! filter_var($savedLogo, FILTER_VALIDATE_URL)) {
            $path = str_replace('\\', '/', trim($savedLogo));
            $path = preg_replace('#^/?(?:storage/app/public/|public/|storage/)+#', '', $path);
            $path = ltrim((string) $path, '/');

            if ($path !== '' && Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->path($path);
            }
        }

        $fallback = public_path('images/logo.png');

        return is_file($fallback) ? $fallback : null;
    }
}
