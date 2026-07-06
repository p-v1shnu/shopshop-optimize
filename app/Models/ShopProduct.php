<?php

namespace App\Models;

use App\Models\Concerns\SerializesDatesToAppTimezone;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ShopProduct extends Model
{
    use SerializesDatesToAppTimezone, BelongsToTenant;

    public $appends = ['cover_image'];

    protected $fillable = [
        'tenant_id',
        'name',
        'images',
        'normal_price',
        'price',
        'short_description',
        'long_description',
        'sku',
        'total_unit',
        'unit_type',
        'storage',
        'sort_no',
        'total_search',
        'available_quantity',
        'status',
        'remark',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'images'     => 'json',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected function coverImage(): Attribute
    {
        $image = collect($this->images)->firstWhere('is_cover', '=', true);

        return Attribute::make(
            get: fn (): string | null => $image ? $image['filename'] : null,
        );
    }

    /**
     * Update product available quantity using stored procedure
     *
     * @param int $quantity The quantity to update/set
     * @param string $type 'UPDATE' to add/subtract, 'SET' to set absolute value
     * @param string $remark Remark for the stock movement
     * @return array Returns ['success' => bool, 'message' => string]
     */
    public function updateProductAvailableQuantity(int $quantity, string $type, string $remark): array
    {
        // Call the stored procedure
        DB::select(
            'CALL update_product_available_quantity(?, ?, ?, ?, @success, @message)',
            [$this->id, $quantity, $type, $remark]
        );

        // Retrieve the output parameters
        $output = DB::select('SELECT @success as success, @message as message')[0];

        // Return the result
        return [
            'success' => (bool) $output->success,
            'message' => $output->message,
        ];
    }
}
