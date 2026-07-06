<?php

namespace App\Livewire\Frontend;

use App\Models\ShopProduct;
use App\Models\ShopUserSearch;
use App\Utils\ShopUtil;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class ShopSearchPage extends Component
{
    #[Locked]
    public Collection | null $products;

    #[Locked]
    public Collection | null $popularProducts;

    #[Locked]
    public Collection | null $recommendProducts;

    #[Locked]
    public \Illuminate\Support\Collection | null $userSearches;

    #[Url]
    public string $search = '';

    #[Url]
    public ?bool $focus = null;

    public function mount(): void
    {
        $this->search = trim($this->search);

        // if search more than 255 then cut
        if (strlen($this->search) > 255) {
            $this->search = substr($this->search, 0, 255);
        }

        $this->queryUserSearches();
        $this->queryPopularProducts();
        $this->searchProducts();
    }

    public function render()
    {
        return view('frontend.livewire.shop-search-page')
            ->layout('frontend.livewire.layout', [
                'showNavbar' => true,
                'showFooter' => false,
                'backUrl'    => ShopUtil::getHomeUrl(),
            ])
            ->title('ຄົ້ນຫາສິນຄ້າທີ່ຕ້ອງການເລີຍ');
    }

    public function queryUserSearches(): void
    {
        if (!$this->search && auth()->check()) {
            $this->userSearches = collect(DB::select("
                SELECT id, created_at, search_term
                FROM (
                    SELECT s.*,
                           ROW_NUMBER() OVER (PARTITION BY search_term ORDER BY created_at DESC) AS rn
                    FROM shop_user_searches s
                    WHERE user_id = ?
                      AND status = 'active'
                ) t
                WHERE rn = 1
                ORDER BY created_at DESC
                LIMIT 10
            ", [auth()->user()->id]));
        }
    }

    public function clearUserSearches(): void
    {
        if (auth()->check()) {
            ShopUserSearch::query()
                ->where('user_id', '=', auth()->user()->id)
                ->where('status', '=', 'active')
                ->update(['status' => 'inactive']);

            $this->userSearches = null;
        }
    }

    public function queryPopularProducts(): void
    {
        if (!$this->search) {
            $this->popularProducts = ShopProduct::query()
                ->select(['id', 'name', 'normal_price', 'price', 'images'])
                ->where('status', '=', 'active')
                ->orderByDesc('total_search')
                ->orderBy('sort_no')
                ->limit(10)
                ->get();
        }
    }

    public function searchProducts(): void
    {
        if (!$this->search) {
            $this->products = null;
            return;
        }

        // Increment total_search by 1 for up to 100 matched products in a single query
        ShopProduct::query()
            ->where('status', '=', 'active')
            ->where('name', 'like', '%' . $this->search . '%')
            ->orderBy('sort_no')
            ->limit(100)
            ->update(['total_search' => DB::raw('total_search + 1')]);

        // Fetch the updated products
        $this->products = ShopProduct::query()
            ->select(['id', 'name', 'normal_price', 'price', 'images'])
            ->where('status', '=', 'active')
            ->where('name', 'like', '%' . $this->search . '%')
            ->orderBy('sort_no')
            ->limit(100)
            ->get();

        // If there are no products found, set recommend products
        if ($this->products->isEmpty()) {
            $this->recommendProducts = ShopProduct::query()
                ->select(['id', 'name', 'normal_price', 'price', 'images'])
                ->where('status', '=', 'active')
                ->inRandomOrder()
                ->take(10)
                ->get();
        }

        // Save user search
        if (auth()->check()) {
            ShopUserSearch::create([
                'user_id'     => auth()->user()->id,
                'search_term' => $this->search,
                'status'      => 'active',
            ]);
        }
    }
}
