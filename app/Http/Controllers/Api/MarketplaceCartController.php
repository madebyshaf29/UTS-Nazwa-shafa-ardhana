<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceCart;
use App\Models\MarketplaceProduct;
use Illuminate\Http\Request;

class MarketplaceCartController extends Controller
{
    public function show(Request $request)
    {
        $cart = $this->resolveCart($request->user()->id_user);

        return response()->json($this->cartResponse($cart));
    }

    public function addItem(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:marketplace_products,id'],
            'qty' => ['required', 'integer', 'min:1'],
        ]);

        $product = MarketplaceProduct::findOrFail($validated['product_id']);

        if (!$product->is_active) {
            return response()->json(['message' => 'Produk sedang tidak aktif.'], 422);
        }

        if ($product->stok < $validated['qty']) {
            return response()->json(['message' => 'Stok tidak mencukupi.'], 422);
        }

        $cart = $this->resolveCart($request->user()->id_user);
        $item = $cart->items()->where('product_id', $product->id)->first();
        $newQty = $validated['qty'] + ($item->qty ?? 0);

        if ($product->stok < $newQty) {
            return response()->json(['message' => 'Jumlah item melebihi stok.'], 422);
        }

        $cart->items()->updateOrCreate(
            ['product_id' => $product->id],
            [
                'qty' => $newQty,
                'harga_saat_dimasukkan' => $product->harga,
            ]
        );

        return response()->json($this->cartResponse($cart->fresh(['items.product'])));
    }

    public function updateItem(Request $request, $itemId)
    {
        $validated = $request->validate([
            'qty' => ['required', 'integer', 'min:1'],
        ]);

        $cart = $this->resolveCart($request->user()->id_user);
        $item = $cart->items()->where('id', $itemId)->firstOrFail();
        $product = $item->product;

        if ($product->stok < $validated['qty']) {
            return response()->json(['message' => 'Stok tidak mencukupi.'], 422);
        }

        $item->update([
            'qty' => $validated['qty'],
            'harga_saat_dimasukkan' => $product->harga,
        ]);

        return response()->json($this->cartResponse($cart->fresh(['items.product'])));
    }

    public function removeItem(Request $request, $itemId)
    {
        $cart = $this->resolveCart($request->user()->id_user);
        $item = $cart->items()->where('id', $itemId)->firstOrFail();
        $item->delete();

        return response()->json($this->cartResponse($cart->fresh(['items.product'])));
    }

    protected function resolveCart($userId)
    {
        return MarketplaceCart::firstOrCreate(['user_id' => $userId])->load('items.product');
    }

    protected function cartResponse(MarketplaceCart $cart): array
    {
        $items = $cart->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'nama_produk' => $item->product->nama_produk,
                'qty' => $item->qty,
                'harga' => $item->harga_saat_dimasukkan,
                'subtotal' => $item->qty * $item->harga_saat_dimasukkan,
            ];
        })->values();

        return [
            'id' => $cart->id,
            'items' => $items,
            'subtotal' => $items->sum('subtotal'),
        ];
    }
}
