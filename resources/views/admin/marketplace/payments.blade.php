@extends('layouts.admin')

@section('content')
<div class="p-8 space-y-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Monitoring Pembayaran Marketplace</h1>
        <p class="text-sm text-gray-400">Audit riwayat transaksi Midtrans seluruh pembudidaya</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Total Log</p>
            <p class="text-2xl font-bold text-gray-800 mt-2">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Success</p>
            <p class="text-2xl font-bold text-green-600 mt-2">{{ number_format($stats['berhasil']) }}</p>
        </div>
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Pending</p>
            <p class="text-2xl font-bold text-amber-600 mt-2">{{ number_format($stats['pending']) }}</p>
        </div>
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Gagal/Expired</p>
            <p class="text-2xl font-bold text-red-600 mt-2">{{ number_format($stats['gagal']) }}</p>
        </div>
    </div>

    <div class="bg-white rounded-[32px] border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <form method="GET" action="{{ route('admin.marketplace.payments') }}" class="grid grid-cols-1 md:grid-cols-6 gap-3">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari order/transaction id"
                    class="md:col-span-2 border border-gray-200 rounded-xl px-4 py-2 text-sm"
                >

                <select name="status" class="border border-gray-200 rounded-xl px-3 py-2 text-sm">
                    <option value="">Semua status</option>
                    @foreach($statusOptions as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                    @endforeach
                </select>

                <select name="payment_type" class="border border-gray-200 rounded-xl px-3 py-2 text-sm">
                    <option value="">Semua metode</option>
                    @foreach($paymentTypeOptions as $paymentType)
                        <option value="{{ $paymentType }}" {{ request('payment_type') === $paymentType ? 'selected' : '' }}>{{ $paymentType }}</option>
                    @endforeach
                </select>

                <input type="date" name="tanggal_awal" value="{{ request('tanggal_awal') }}" class="border border-gray-200 rounded-xl px-3 py-2 text-sm">
                <input type="date" name="tanggal_akhir" value="{{ request('tanggal_akhir') }}" class="border border-gray-200 rounded-xl px-3 py-2 text-sm">

                <div class="md:col-span-6 flex gap-2">
                    <button class="px-4 py-2 bg-purple-600 text-white rounded-xl text-sm font-bold hover:bg-purple-700">Terapkan Filter</button>
                    <a href="{{ route('admin.marketplace.payments') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl text-sm font-bold hover:bg-gray-200">Reset</a>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 text-[11px] font-black text-gray-400 uppercase tracking-widest">
                        <th class="px-6 py-4">Waktu</th>
                        <th class="px-6 py-4">Order</th>
                        <th class="px-6 py-4">Pembudidaya</th>
                        <th class="px-6 py-4">Transaction ID</th>
                        <th class="px-6 py-4">Metode</th>
                        <th class="px-6 py-4">Nominal</th>
                        <th class="px-6 py-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($payments as $payment)
                        <tr class="hover:bg-gray-50/70">
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 text-sm font-bold text-gray-700">{{ $payment->order_code }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $payment->order->user->nama_lengkap ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $payment->transaction_id ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $payment->payment_type ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm font-bold text-gray-700">
                                Rp {{ number_format((float) ($payment->gross_amount ?? 0), 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $status = $payment->transaction_status ?? '-';
                                    $statusText = $status;
                                    $statusClass = 'bg-gray-100 text-gray-600';
                                    
                                    if (in_array($status, ['settlement', 'capture'])) {
                                        $statusClass = 'bg-green-100 text-green-700';
                                        $statusText = 'Success';
                                    }
                                    if ($status === 'pending') {
                                        $statusClass = 'bg-amber-100 text-amber-700';
                                        $statusText = 'Pending';
                                    }
                                    if (in_array($status, ['deny', 'cancel', 'expire'])) {
                                        $statusClass = 'bg-red-100 text-red-700';
                                        $statusText = 'Gagal';
                                    }
                                @endphp
                                <span class="px-3 py-1 rounded-lg text-xs font-bold uppercase {{ $statusClass }}">{{ $statusText }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">Belum ada data pembayaran marketplace.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-6 border-t border-gray-100 bg-gray-50/40 flex justify-between items-center">
            <p class="text-xs text-gray-400">Menampilkan {{ $payments->firstItem() ?? 0 }} - {{ $payments->lastItem() ?? 0 }} dari {{ $payments->total() }} data</p>
            {{ $payments->links('pagination::tailwind') }}
        </div>
    </div>
</div>
@endsection
