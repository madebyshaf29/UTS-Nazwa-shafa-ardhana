<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceOrder;
use App\Models\MarketplacePayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MarketplacePaymentController extends Controller
{
    public function midtransWebhook(Request $request)
    {
        $serverKey = config('services.midtrans.server_key');
        if (!$serverKey) {
            return response()->json(['message' => 'Midtrans key belum dikonfigurasi.'], 500);
        }

        $signature = hash(
            'sha512',
            ($request->order_id ?? '') .
            ($request->status_code ?? '') .
            ($request->gross_amount ?? '') .
            $serverKey
        );

        if (($request->signature_key ?? '') !== $signature) {
            return response()->json(['message' => 'Signature tidak valid.'], 403);
        }

        $order = MarketplaceOrder::where('order_code', $request->order_id)->first();
        if (!$order) {
            return response()->json(['message' => 'Order tidak ditemukan.'], 404);
        }

        $this->storePaymentLog($order, $request->all());
        $this->applyOrderStatus($order, $request->all());

        Log::info('Webhook Midtrans diproses.', [
            'order_code' => $order->order_code,
            'transaction_status' => $request->transaction_status,
        ]);

        return response()->json(['message' => 'OK']);
    }

    public function syncOrderStatus(Request $request, $id)
    {
        $order = MarketplaceOrder::where('user_id', $request->user()->id_user)->findOrFail($id);

        $serverKey = config('services.midtrans.server_key');
        if (!$serverKey) {
            return response()->json(['message' => 'Midtrans key belum dikonfigurasi.'], 500);
        }

        $isProduction = (bool) config('services.midtrans.is_production', false);
        $baseUrl = $isProduction
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';

        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->get($baseUrl . '/v2/' . $order->order_code . '/status');

        if (!$response->successful()) {
            return response()->json(['message' => 'Gagal sinkron status transaksi dari Midtrans.'], 500);
        }

        $payload = $response->json();
        $this->storePaymentLog($order, $payload);
        $this->applyOrderStatus($order, $payload);

        return response()->json([
            'message' => 'Status pembayaran tersinkron.',
            'status_pembayaran' => $order->fresh()->status_pembayaran,
            'status_pesanan' => $order->fresh()->status_pesanan,
        ]);
    }

    protected function storePaymentLog(MarketplaceOrder $order, array $payload): void
    {
        MarketplacePayment::create([
            'order_id' => $order->id,
            'provider' => 'midtrans',
            'transaction_id' => $payload['transaction_id'] ?? null,
            'order_code' => $payload['order_id'] ?? $order->order_code,
            'payment_type' => $payload['payment_type'] ?? null,
            'transaction_status' => $payload['transaction_status'] ?? null,
            'fraud_status' => $payload['fraud_status'] ?? null,
            'status_code' => $payload['status_code'] ?? null,
            'gross_amount' => $payload['gross_amount'] ?? null,
            'signature_key' => $payload['signature_key'] ?? null,
            'raw_payload' => $payload,
            'paid_at' => in_array($payload['transaction_status'] ?? null, ['capture', 'settlement'], true) ? Carbon::now() : null,
        ]);
    }

    protected function applyOrderStatus(MarketplaceOrder $order, array $payload): void
    {
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;

        if ($transactionStatus === 'capture') {
            if ($fraudStatus === 'challenge') {
                $order->update([
                    'status_pembayaran' => 'menunggu_pembayaran',
                    'status_pesanan' => 'menunggu_pembayaran',
                    'payment_reference' => $payload['transaction_id'] ?? null,
                ]);
                return;
            }

            $order->update([
                'status_pembayaran' => 'dibayar',
                'status_pesanan' => 'diproses',
                'payment_reference' => $payload['transaction_id'] ?? null,
            ]);
            return;
        }

        if ($transactionStatus === 'settlement') {
            $order->update([
                'status_pembayaran' => 'dibayar',
                'status_pesanan' => 'diproses',
                'payment_reference' => $payload['transaction_id'] ?? null,
            ]);
            return;
        }

        if ($transactionStatus === 'pending') {
            $order->update([
                'status_pembayaran' => 'menunggu_pembayaran',
                'status_pesanan' => 'menunggu_pembayaran',
                'payment_reference' => $payload['transaction_id'] ?? null,
            ]);
            return;
        }

        if (in_array($transactionStatus, ['deny', 'cancel'], true)) {
            $order->update([
                'status_pembayaran' => 'gagal',
                'status_pesanan' => 'dibatalkan',
                'payment_reference' => $payload['transaction_id'] ?? null,
            ]);
            return;
        }

        if ($transactionStatus === 'expire') {
            $order->update([
                'status_pembayaran' => 'expired',
                'status_pesanan' => 'dibatalkan',
                'payment_reference' => $payload['transaction_id'] ?? null,
            ]);
        }
    }
}
