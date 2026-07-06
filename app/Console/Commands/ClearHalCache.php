<?php

namespace App\Console\Commands;

use App\Models\ShopOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearHalCache extends Command
{
    protected $signature = 'ClearHalCache';

    public function handle(): void
    {
        Cache::tags('HAL')->flush();
    }
}
