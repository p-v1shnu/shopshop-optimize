<?php

namespace App\Models;

use App\Models\Concerns\SerializesDatesToAppTimezone;
use Illuminate\Database\Eloquent\Model;

class OtpLog extends Model
{
    use SerializesDatesToAppTimezone;

    protected $fillable = [
        'provider',
        'provider_reference',
        'msisdn',
        'otp',
        'data',
        'expired_at',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'data'       => 'array',
            'expired_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
