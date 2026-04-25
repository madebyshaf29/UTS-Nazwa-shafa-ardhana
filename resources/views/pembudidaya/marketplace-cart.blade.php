@extends('layouts.pembudidaya')

@section('title', 'Keranjang Marketplace')
@section('subtitle', 'Review belanjaan sebelum checkout')

@section('content')
    @if(session('error'))
        <div class="mb-4 bg-red-50 text-red-700 border border-red-200 rounded-lg px-4 py-3 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-gray-800 mb-4">Item Keranjang</h3>

            @forelse($cart->items as $item)
                <div class="bg-gray-50/50 rounded-2xl p-5 mb-4 border border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex items-center gap-4 w-full md:w-auto">
                        @php
                            $defaultImage = match($item->product->kategori) {
                                'bibit' => asset('assets/img/marketplace-bibit.svg'),
                                'pakan' => asset('assets/img/marketplace-pakan.svg'),
                                default => asset('assets/img/marketplace-alat.svg'),
                            };
                            $imageUrl = $item->product->gambar_produk ? asset('storage/' . $item->product->gambar_produk) : $defaultImage;
                        @endphp
                        <img src="{{ $imageUrl }}" class="w-16 h-16 object-cover rounded-xl border border-white shadow-sm">
                        <div>
                            <p class="font-bold text-gray-800">{{ $item->product->nama_produk }}</p>
                            <p class="text-xs text-gray-500">Harga: Rp {{ number_format($item->harga_saat_dimasukkan, 0, ',', '.') }}</p>
                            <p class="text-sm font-bold text-blue-600 mt-1">Subtotal: Rp {{ number_format($item->qty * $item->harga_saat_dimasukkan, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-6 w-full md:w-auto justify-between md:justify-end">
                        <form action="{{ route('pembudidaya.marketplace.cart.update', $item->id) }}" method="POST" class="flex items-center bg-white border border-gray-200 rounded-xl px-1">
                            @csrf
                            @method('PATCH')
                            <button type="button" onclick="updateQty(this, -1)" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-blue-600 transition-colors">
                                <i class="fa-solid fa-minus text-xs"></i>
                            </button>
                            <input type="number" name="qty" value="{{ $item->qty }}" min="1" max="{{ $item->product->stok }}" class="w-10 text-center font-bold text-gray-800 border-none outline-none text-sm pointer-events-none">
                            <button type="button" onclick="updateQty(this, 1)" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-blue-600 transition-colors">
                                <i class="fa-solid fa-plus text-xs"></i>
                            </button>
                        </form>

                        <form action="{{ route('pembudidaya.marketplace.cart.remove', $item->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button class="w-10 h-10 flex items-center justify-center bg-red-50 text-red-500 rounded-xl hover:bg-red-500 hover:text-white transition-all">
                                <i class="fa-solid fa-trash-can text-sm"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="text-center py-12">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-cart-shopping text-gray-200 text-3xl"></i>
                    </div>
                    <p class="text-gray-400 font-medium">Keranjang kamu masih kosong.</p>
                    <a href="{{ route('pembudidaya.marketplace.index') }}" class="inline-block mt-4 text-blue-600 font-bold hover:underline">Mulai Belanja</a>
                </div>
            @endforelse

            <script>
                function updateQty(btn, delta) {
                    const form = btn.closest('form');
                    const input = form.querySelector('input[name="qty"]');
                    let newVal = parseInt(input.value) + delta;
                    
                    if (newVal >= 1 && newVal <= parseInt(input.max)) {
                        input.value = newVal;
                        form.submit();
                    } else if (newVal > parseInt(input.max)) {
                        alert('Stok tidak mencukupi (Maks: ' + input.max + ')');
                    }
                }
            </script>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-gray-800 mb-4">Checkout</h3>
            <p class="text-sm text-gray-500 mb-1">Subtotal</p>
            <p class="text-xl font-bold text-gray-900 mb-4">Rp {{ number_format($subtotal, 0, ',', '.') }}</p>

            <form action="{{ route('pembudidaya.marketplace.checkout') }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="text-sm text-gray-600">Nama Penerima</label>
                    <input name="nama_penerima" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="text-sm text-gray-600">Nomor HP Penerima</label>
                    <input name="nomor_hp_penerima" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="text-sm text-gray-600">Alamat Pengiriman</label>
                    <textarea name="alamat_pengiriman" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" rows="3" required></textarea>
                </div>

                <button class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg font-medium text-sm">
                    Checkout Sekarang
                </button>
            </form>
        </div>
    </div>
@endsection
