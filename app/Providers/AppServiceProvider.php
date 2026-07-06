<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Setting::class, fn () => Setting::query()->firstOrNew());
    }

    public function boot(): void
    {
        Schema::defaultStringLength(255);

        DB::prohibitDestructiveCommands(config('custom.db_enable_migration') !== true);

        resolve(UrlGenerator::class)->forceScheme('https');

        // Model::shouldBeStrict();
    }
}
