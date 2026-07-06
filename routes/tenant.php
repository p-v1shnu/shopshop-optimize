<?php

declare(strict_types=1);

use App\Http\Middleware\CheckProfile;
use App\Http\Middleware\CheckTenantStatus;
use App\Livewire\Frontend\ProfileEditPage;
use App\Livewire\Frontend\ProfilePage;
use App\Livewire\Frontend\ShopCartPage;
use App\Livewire\Frontend\ShopCheckoutPage;
use App\Livewire\Frontend\ShopOrderDetailPage;
use App\Livewire\Frontend\ShopOrdersPage;
use App\Livewire\Frontend\ShopProductDetailPage;
use App\Livewire\Frontend\ShopProductsPage;
use App\Livewire\Frontend\ShopSearchPage;
use App\Livewire\Frontend\ShopShippingPage;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\CheckTenantForMaintenanceMode;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    CheckTenantStatus::class,
    CheckTenantForMaintenanceMode::class,
])->group(function () {

    Auth::routes([
        'verify'   => false,
        'reset'    => false,
        'register' => false,
    ]);

    Route::redirect('/', '/shop')->name('home');

    Route::livewire('/shop/search', ShopSearchPage::class)->name('shop.search');

    Route::middleware([CheckProfile::class])->group(function () {

        Route::livewire('/shop', ShopProductsPage::class)->name('shop.home');
        Route::livewire('/shop/products/{productId}', ShopProductDetailPage::class)->name('shop.product');

        Route::middleware(['auth', 'can:role-user'])->group(function () {

            Route::livewire('/profile', ProfilePage::class)->name('profile');
            Route::livewire('/edit-profile', ProfileEditPage::class)->name('profile.edit');

            Route::livewire('/shop/cart', ShopCartPage::class)->name('shop.cart');
            Route::livewire('/shop/shipping', ShopShippingPage::class)->name('shop.shipping');
            Route::livewire('/shop/orders/{orderId}/checkout', ShopCheckoutPage::class)->name('shop.checkout');
            Route::livewire('/shop/orders', ShopOrdersPage::class)->name('shop.orders');
            Route::livewire('/shop/orders/{orderId}', ShopOrderDetailPage::class)->name('shop.orderDetail');

        });

    });

    // Error routes for middleware-enabled error pages
    Route::get('/error/{code}', function ($code) {
        $viewName = "errors.{$code}";

        // Check if specific error view exists, fallback to generic error view
        if (view()->exists($viewName)) {
            return response()->view($viewName, [], $code);
        }

        return response()->view('errors.500', [], $code);
    })->where('code', '[0-9]+');

});
