<?php

use App\Models\ShopOrder;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('orders.{orderId}', function (User $user, string $id) {
    $order = ShopOrder::find($id);
    return $user->id === optional($order)->user_id;
});
