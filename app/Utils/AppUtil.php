<?php

namespace App\Utils;

use Illuminate\Foundation\ViteException;
use Illuminate\Support\Facades\Vite;

class AppUtil
{
    public static function asset(string|null $path, string|null $fallbackPath = null): string
    {
        if ($path === null) {
            return '';
        }

        try {
            return Vite::asset($path);
        } catch (ViteException $e) {

            if ($fallbackPath !== null) {
                return self::asset($fallbackPath);
            }

            return '';
        }
    }
}
