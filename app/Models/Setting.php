<?php

namespace App\Models;

use App\Models\Concerns\SerializesDatesToAppTimezone;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use SerializesDatesToAppTimezone;

    protected $fillable = [
        'title',
        'facebook_cover_url',
        'landing_page_url',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
