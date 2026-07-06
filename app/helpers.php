<?php

use App\Models\Setting;

if (!function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        $value = app(Setting::class)->{$key} ?? null;
        return $value ?? $default;
    }
}
