<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceCart;
use App\Models\MarketplaceOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarketplaceOrderController extends Controller
{
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'alamat_pengiriman' => ['required', 'string'],
            'nama_penerima' => ['required', 'string', 'max:255'],
            'nomor_hp_penerima' => ['required', 'string', 'max:50'],
        ]);

        $cart = MarketplaceCart::where('user_id', $request->user()->id_user)
            ->with('items.product')
            ->firstOrFail();

        if ($cart->items->isEmpty()) {
            return response()->json(['message' => 'Keranjang kosong.'], 422);
        }

        $order = DB::transaction(function () use ($cart, $validated, $request) {
            $subtotal = 0;
            foreach ($cart->items as $item) {
                if ($item->product->stok < $item->qty) {
                    throw ValidationException::withMessages([
                        'stok' => "Stok produk {$item->product->nama_produk} tidak mencukupi.",
                    ]);
                }
                $subtotal += ($item->qty * $item->product->harga);
            }

            $order = MarketplaceOrder::create([
                'order_code' => 'MP-' . now()->format('YmdHis') . '-' . mt_rand(100, 999),
                'user_id' => $request->user()->id_user,
                'subtotal' => $subtotal,
                'ongkir' => 0,
                'total' => $subtotal,
                'shipping_payload' => [
                    'alamat_pengiriman' => $validated['alamat_pengiriman'],
                    'nama_penerima' => $validated['nama_penerima'],
                    'nomor_hp_penerima' => $validated['nomor_hp_penerima'],
                ],
            ]);

            foreach ($cart->items as $item) {
                $hargaFinal = $item->product->harga;

                $order->items()->create([
                    'product_id' => $item->product_id,
                    'nama_produk' => $item->product->nama_produk,
                    'qty' => $item->qty,
                    'harga' => $hargaFinal,
                    'subtotal' => $item->qty * $hargaFinal,
                ]);

                $item->product->decrement('stok', $item->qty);
            }

            $cart->items()->delete();

            return $order->load('items');
        });

        return response()->json([
            'message' => 'Checkout berhasil. Lanjutkan pembayaran ke payment gateway.',
            'order' => $order,
        ], 201);
    }

    public function index(Request $request)
    {
        $orders = MarketplaceOrder::where('user_id', $request->user()->id_user)
            ->with('items')
            ->latest()
            ->paginate(10);

        return response()->json($orders);
    }

    public function show(Request $request, $id)
    {
        $order = MarketplaceOrder::where('user_id', $request->user()->id_user)
            ->with('items')
            ->findOrFail($id);

        return response()->json($order);
    }

    public function markAsPaid(Request $request, $id)
    {
        $validated = $request->validate([
            'payment_reference' => ['required', 'string', 'max:255'],
        ]);

        $order = MarketplaceOrder::where('user_id', $request->user()->id_user)->findOrFail($id);

        if ($order->status_pembayaran === 'dibayar') {
            return response()->json(['message' => 'Pesanan sudah dibayar.']);
        }

        $order->update([
            'status_pembayaran' => 'dibayar',
            'status_pesanan' => 'diproses',
            'payment_reference' => $validated['payment_reference'],
        ]);

        return response()->json([
            'message' => 'Pembayaran terkonfirmasi. Pesanan masuk status diproses.',
            'order' => $order->fresh('items'),
        ]);
    }
}
