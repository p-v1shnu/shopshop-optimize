<?php

namespace App\Models;

use App\Models\Concerns\SerializesDatesToAppTimezone;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class User extends Authenticatable
{
    use SerializesDatesToAppTimezone, HasFactory, Notifiable, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'type',
        'email',
        'email_verified_at',
        'password',
        'role',
        'phone',
        'name',
        'gender',
        'dob',
        'province',
        'district',
        'village',
        'banned_at',
        'status',
        'remark',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'dob'               => 'date',
            'banned_at'         => 'datetime',
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
        ];
    }

    public function shopOrders(): HasMany
    {
        return $this->hasMany(ShopOrder::class);
    }

    public function hadCompleteProfile(): Attribute
    {
        return new Attribute(
            get: fn (): bool => (!empty($this->phone))
                && (!empty($this->name))
                && (!empty($this->dob))
                && (!empty($this->gender))
                && (!empty($this->province))
                && (!empty($this->district))
                && (!empty($this->village))
        );
    }
}
