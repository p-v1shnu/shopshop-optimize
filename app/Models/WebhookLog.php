<?php

namespace App\Models;

use App\Models\Concerns\SerializesDatesToAppTimezone;
use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    use SerializesDatesToAppTimezone;

    protected $fillable = [
        'type',
        'message',
        'detail',
        'response_time',
        'remark',
        'model',
        'model_id',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'detail'     => 'json',
            'created_at' => 'datetime',
            'updated_at'  => 'datetime',
        ];
    }
}
