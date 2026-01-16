<?php

namespace Modules\LatestProductGeneral\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Traits\HasMultiStoreModuleSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\LatestProductGeneral\Models\LatestProduct;

class SettingsController extends Controller
{
    use HasMultiStoreModuleSettings;

    protected function getModuleSlug(): string
    {
        return 'latest-product-general';
    }

    protected function getDefaultSettings(): array
    {
        return [
            'enabled' => true,
            'title' => 'New Arrivals',
            'max_products' => 12,
            'auto_select' => true,
            'days_as_new' => 30,
            'sort_order' => 0,
        ];
    }

    public function index(): Response
    {
        $data = $this->getMultiStoreData();

        $latestProducts = LatestProduct::with('product.translations')
            ->manual()
            ->ordered()
            ->get();

        $products = Product::with('translations')
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'sku', 'price', 'image', 'created_at']);

        $data['latestProducts'] = $latestProducts;
        $data['products'] = $products;

        return Inertia::render('LatestProductGeneral::Admin/Settings', $data);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'is_enabled' => 'boolean',
            'settings.enabled' => 'boolean',
            'settings.title' => 'required|string|max:255',
            'settings.max_products' => 'required|integer|min:1|max:50',
            'settings.auto_select' => 'boolean',
            'settings.days_as_new' => 'required|integer|min:1|max:365',
            'settings.sort_order' => 'integer|min:0',
        ]);

        return $this->saveStoreSettings($request);
    }

    public function addProduct(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $maxOrder = LatestProduct::max('sort_order') ?? 0;

        LatestProduct::updateOrCreate(
            ['product_id' => $validated['product_id']],
            ['sort_order' => $maxOrder + 1, 'is_active' => true, 'is_manual' => true]
        );

        return back()->with('success', 'Product added to latest.');
    }

    public function removeProduct(LatestProduct $latestProduct): RedirectResponse
    {
        $latestProduct->delete();

        return back()->with('success', 'Product removed from latest.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|integer|exists:latest_products,id',
            'products.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['products'] as $item) {
            LatestProduct::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return back()->with('success', 'Order updated.');
    }

    public function toggle(LatestProduct $latestProduct): RedirectResponse
    {
        $latestProduct->update(['is_active' => !$latestProduct->is_active]);

        return back()->with('success', 'Status updated.');
    }
}