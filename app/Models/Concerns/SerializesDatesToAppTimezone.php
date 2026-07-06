<?php
namespace App\Models\Concerns;

use DateTimeInterface;
use Carbon\CarbonImmutable;

trait SerializesDatesToAppTimezone
{
    protected function serializeDate(DateTimeInterface $date): string
    {
        return CarbonImmutable::instance($date)
            ->setTimezone(config('app.timezone'))
            ->format('Y-m-d\TH:i:sP');
    }
}
