<?php

namespace App\Models;

use App\Models\Concerns\SerializesDatesToAppTimezone;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use SerializesDatesToAppTimezone, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'tenant_id',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isSuper(): bool
    {
        return $this->role === 'super';
    }

    public function isShop(): bool
    {
        return $this->role === 'shop';
    }
}
