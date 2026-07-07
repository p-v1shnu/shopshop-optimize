<?php

use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\SetAdminCurrentShop;
use App\Livewire\Admin\AdminAccountsPage;
use App\Livewire\Admin\BannersPage;
use App\Livewire\Admin\BrandsPage;
use App\Livewire\Admin\CouponsPage;
use App\Livewire\Admin\CustomersPage;
use App\Livewire\Admin\DashboardPage;
use App\Livewire\Admin\LoginPage;
use App\Livewire\Admin\MyAccountPage;
use App\Livewire\Admin\OrdersPage;
use App\Livewire\Admin\ProductsPage;
use App\Livewire\Admin\SettingsPage;
use App\Livewire\Admin\ShippingRulesPage;
use App\Models\Tenant;
use App\Support\AdminTenantScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

$adminDomain = 'admin.'.parse_url(config('app.url'), PHP_URL_HOST);

Route::domain($adminDomain)->middleware('web')->group(function () {
    Route::redirect('/', '/admin');

    Route::middleware('guest:admin')->group(function () {
        // Login is handled by the LoginPage Livewire component (LoginPage::login()).
        Route::livewire('/admin/login', LoginPage::class)->name('admin.login');
    });

    Route::middleware([
        AuthenticateAdmin::class.':admin',
        SetAdminCurrentShop::class,
    ])->group(function () {
        Route::livewire('/admin', DashboardPage::class)->name('admin.dashboard');
        Route::livewire('/admin/products', ProductsPage::class)->name('admin.products');
        Route::livewire('/admin/orders', OrdersPage::class)->name('admin.orders');
        Route::livewire('/admin/coupons', CouponsPage::class)->name('admin.coupons');
        Route::livewire('/admin/shipping-rules', ShippingRulesPage::class)->name('admin.shipping-rules');
        Route::livewire('/admin/customers', CustomersPage::class)->name('admin.customers');
        Route::livewire('/admin/settings', SettingsPage::class)->name('admin.settings');
        Route::livewire('/admin/banners', BannersPage::class)->name('admin.banners');
        Route::livewire('/admin/my-account', MyAccountPage::class)->name('admin.my-account');

        Route::middleware(EnsureSuperAdmin::class)->group(function () {
            Route::livewire('/admin/admin-accounts', AdminAccountsPage::class)->name('admin.admin-accounts');
            Route::livewire('/admin/brands', BrandsPage::class)->name('admin.brands');
        });

        Route::post('/admin/current-shop', function (Request $request) {
            $admin = Auth::guard('admin')->user();

            if (! $admin?->isSuper()) {
                return response('', 403);
            }

            $validated = $request->validate([
                'tenant_id' => ['required', 'string', 'exists:tenants,id'],
            ]);

            $request->session()->put(AdminTenantScope::SESSION_KEY, $validated['tenant_id']);

            return redirect()->route('admin.dashboard');
        })->name('admin.current-shop.update');

        Route::post('/admin/logout', function (Request $request) {
            Auth::guard('admin')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login');
        })->name('admin.logout');
    });
});

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {

        Route::view('/', 'frontend.central-domain');

        // ================================================================================

        // Invoice webhook redirect to tenant domain
        // https://shopshop.la/[tenant_id]/shop/orders/[order_id] => https://[tenant_id].shopshop.la/shop/orders/[order_id]
        Route::get('/{tenant_id}/shop/orders/{order_id}', function ($tenant_id, $order_id) {
            $tenant = Tenant::query()
                ->with('domains:tenant_id,domain') // Eager load domains with specific columns
                ->where('id', $tenant_id)
                ->firstOrFail();

            $tenantDomain = $tenant->domains->first();

            if ($tenantDomain) {
                $url = "https://$tenantDomain->domain/shop/orders/$order_id";
                return redirect($url);
            }

            abort(404);
        });

        // ================================================================================

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
}
