<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceProduct;
use Illuminate\Http\Request;

class MarketplaceProductController extends Controller
{
    public function index(Request $request)
    {
        $products = MarketplaceProduct::query()
            ->with('komoditas')
            ->when($request->filled('kategori'), function ($query) use ($request) {
                $query->where('kategori', $request->kategori);
            })
            ->when($request->filled('komoditas_id'), function ($query) use ($request) {
                $query->where('komoditas_id', $request->komoditas_id);
            })
            ->when($request->boolean('hanya_aktif', true), function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('nama_produk')
            ->paginate(10);

        return response()->json($products);
    }

    public function show($id)
    {
        $product = MarketplaceProduct::with('komoditas')->findOrFail($id);

        return response()->json($product);
    }
}
