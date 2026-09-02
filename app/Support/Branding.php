<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class Branding
{
    private const LOGO_KEY = 'branding.logo_path';

    private const FAVICON_KEY = 'branding.favicon_path';

    public static function logoPath(): ?string
    {
        $path = Setting::getValue(self::LOGO_KEY);

        return is_string($path) && $path !== '' ? $path : null;
    }

    public static function faviconPath(): ?string
    {
        $path = Setting::getValue(self::FAVICON_KEY);

        return is_string($path) && $path !== '' ? $path : null;
    }

    public static function logoUrl(): ?string
    {
        $path = self::logoPath();

        return $path && Storage::disk('public')->exists($path)
            ? Storage::disk('public')->url($path)
            : null;
    }

    public static function faviconUrl(): ?string
    {
        $path = self::faviconPath();

        return $path && Storage::disk('public')->exists($path)
            ? Storage::disk('public')->url($path)
            : null;
    }

    public static function setLogoPath(string $path): void
    {
        self::deleteStoredFile(self::logoPath());
        Setting::setValue(self::LOGO_KEY, $path);
    }

    public static function setFaviconPath(string $path): void
    {
        self::deleteStoredFile(self::faviconPath());
        Setting::setValue(self::FAVICON_KEY, $path);
    }

    public static function removeLogo(): void
    {
        self::deleteStoredFile(self::logoPath());
        Setting::setValue(self::LOGO_KEY, null);
    }

    public static function removeFavicon(): void
    {
        self::deleteStoredFile(self::faviconPath());
        Setting::setValue(self::FAVICON_KEY, null);
    }

    private static function deleteStoredFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
