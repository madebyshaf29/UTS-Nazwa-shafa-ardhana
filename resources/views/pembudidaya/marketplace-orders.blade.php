@extends('layouts.pembudidaya')

@section('title', 'Pesanan Marketplace')
@section('subtitle', 'Pantau status pembayaran dan pengiriman')

@section('content')
    @if(session('error'))
        <div class="mb-4 bg-red-50 text-red-700 border border-red-200 rounded-lg px-4 py-3 text-sm">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 bg-red-50 text-red-700 border border-red-200 rounded-lg px-4 py-3 text-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-bold text-gray-800 mb-4">Riwayat Pesanan</h3>

        @forelse($orders as $order)
            <div class="border border-gray-100 rounded-lg p-4 mb-4">
                <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-2 mb-2">
                    <div>
                        <p class="font-semibold text-gray-800">{{ $order->order_code }}</p>
                        <p class="text-xs text-gray-500">{{ $order->created_at->format('d M Y H:i') }}</p>
                    </div>
                    <div class="text-sm">
                        @if($order->status_pembayaran !== 'menunggu_pembayaran')
                            <span class="px-2 py-1 bg-green-50 text-green-700 rounded">
                                {{ $order->status_pembayaran === 'dibayar' ? 'Success' : $order->status_pembayaran }}
                            </span>
                        @endif
                        
                        @if($order->status_pesanan !== 'menunggu_pembayaran')
                            <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded">
                                {{ ucfirst($order->status_pesanan) }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="text-sm text-gray-600 mb-2">
                    @foreach($order->items as $item)
                        <div>{{ $item->nama_produk }} ({{ $item->qty }}x) - Rp {{ number_format($item->subtotal, 0, ',', '.') }}</div>
                    @endforeach
                </div>

                <p class="font-bold text-gray-900">Total: Rp {{ number_format($order->total, 0, ',', '.') }}</p>

                @if($order->status_pembayaran === 'menunggu_pembayaran')
                    <div class="mt-3 flex gap-2">
                        <button
                            type="button"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 pay-button"
                            data-order-id="{{ $order->id }}"
                        >
                            Bayar Sekarang
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 sync-button"
                            data-order-id="{{ $order->id }}"
                        >
                            Cek Status Pembayaran
                        </button>
                    </div>
                @elseif($order->status_pembayaran === 'dibayar' && $order->status_pesanan !== 'selesai')
                    <div class="mt-3">
                        <form action="{{ route('pembudidaya.marketplace.orders.complete', $order->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">
                                Selesaikan Pesanan (Diterima)
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        @empty
            <div class="text-sm text-gray-500">Belum ada pesanan marketplace.</div>
        @endforelse

        <div class="mt-4">
            {{ $orders->links() }}
        </div>
    </div>

    @if(!empty($midtransClientKey))
        <script
            src="{{ $midtransIsProduction ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
            data-client-key="{{ $midtransClientKey }}">
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.pay-button').forEach(function (button) {
                    button.addEventListener('click', async function () {
                        const orderId = button.dataset.orderId;
                        button.disabled = true;
                        button.innerText = 'Memproses...';
 
                        try {
                            const response = await fetch(`/pembudidaya/marketplace/orders/${orderId}/snap-token`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                }
                            });
 
                            const result = await response.json();
                            if (!response.ok || !result.token) {
                                throw new Error(result.message || 'Gagal membuat token pembayaran.');
                            }
 
                            window.snap.pay(result.token, {
                                onSuccess: async function () {
                                    await syncPayment(orderId);
                                    window.location.reload();
                                },
                                onPending: async function () {
                                    await syncPayment(orderId);
                                    window.location.reload();
                                },
                                onError: function () {
                                    alert('Pembayaran gagal. Silakan coba lagi.');
                                },
                                onClose: function () {
                                    button.disabled = false;
                                    button.innerText = 'Bayar Sekarang';
                                }
                            });
                        } catch (error) {
                            alert(error.message);
                            button.disabled = false;
                            button.innerText = 'Bayar Sekarang';
                        }
                    });
                });
 
                document.querySelectorAll('.sync-button').forEach(function (button) {
                    button.addEventListener('click', async function () {
                        const orderId = button.dataset.orderId;
                        button.disabled = true;
                        button.innerText = 'Mengecek...';
                        
                        const result = await syncPayment(orderId);
                        if(result) {
                            // Tampilkan status menggunakan fungsi global showSuccess dari layout
                            const statusMap = {
                                'menunggu_pembayaran': 'Belum Dibayar',
                                'dibayar': 'Success',
                                'gagal': 'Pembayaran Gagal',
                                'expired': 'Pembayaran Kadaluarsa'
                            };
                            const statusText = statusMap[result.status_pembayaran] || result.status_pembayaran;
                            
                            showSuccess("Status: " + statusText);
                            
                            // Jika sudah dibayar, reload halaman
                            if(result.status_pembayaran === 'dibayar') {
                                setTimeout(() => window.location.reload(), 2000);
                            } else {
                                button.disabled = false;
                                button.innerText = 'Cek Status Pembayaran';
                            }
                        } else {
                            button.disabled = false;
                            button.innerText = 'Cek Status Pembayaran';
                        }
                    });
                });
            });
 
            async function syncPayment(orderId) {
                try {
                    const response = await fetch(`/pembudidaya/marketplace/orders/${orderId}/sync-payment`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    return await response.json();
                } catch (error) {
                    console.error(error);
                    return null;
                }
            }
        </script>
    @endif
@endsection
