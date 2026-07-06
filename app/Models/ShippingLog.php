<?php

namespace App\Models;

use App\Models\Concerns\SerializesDatesToAppTimezone;
use Illuminate\Database\Eloquent\Model;

class ShippingLog extends Model
{
    use SerializesDatesToAppTimezone;

    protected $fillable = [
        'provider',
        'provider_reference',
        'type',
        'data',
        'data->request',
        'data->response',
        'response_time',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'data'       => 'json',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
