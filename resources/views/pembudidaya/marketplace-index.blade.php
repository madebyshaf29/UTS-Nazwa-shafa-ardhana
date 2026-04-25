@extends('layouts.pembudidaya')

@section('title', 'Marketplace')
@section('subtitle', 'Belanja sarana budidaya, bibit, pakan, dan alat')

@section('content')
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <form method="GET" action="{{ route('pembudidaya.marketplace.index') }}" class="flex flex-col md:flex-row gap-3 md:items-end">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Kategori</label>
                <select name="kategori" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Semua</option>
                    <option value="pakan" {{ request('kategori') === 'pakan' ? 'selected' : '' }}>Pakan</option>
                    <option value="bibit" {{ request('kategori') === 'bibit' ? 'selected' : '' }}>Bibit</option>
                    <option value="alat" {{ request('kategori') === 'alat' ? 'selected' : '' }}>Alat</option>
                </select>
            </div>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                Filter
            </button>
            <a href="{{ route('pembudidaya.marketplace.cart') }}" class="text-sm text-blue-600 font-medium md:ml-auto">Lihat Keranjang</a>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @forelse($products as $product)
            @php
                $defaultImage = match($product->kategori) {
                    'pakan' => asset('assets/img/marketplace-pakan.svg'),
                    'bibit' => asset('assets/img/marketplace-bibit.svg'),
                    default => asset('assets/img/marketplace-alat.svg'),
                };
                $imageUrl = $product->gambar_produk ? asset('storage/' . $product->gambar_produk) : $defaultImage;
            @endphp
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 group hover:shadow-md transition-shadow cursor-pointer" 
                 onclick="openProductDetail({{ json_encode([
                    'id' => $product->id,
                    'nama' => $product->nama_produk,
                    'kategori' => ucfirst($product->kategori),
                    'komoditas' => $product->komoditas->nama ?? 'Umum',
                    'deskripsi' => $product->deskripsi ?: 'Tidak ada deskripsi.',
                    'spesifikasi' => $product->spesifikasi ?: 'Tidak ada detail spesifikasi.',
                    'lokasi' => $product->lokasi ?: 'Sidoarjo',
                    'estimasi' => $product->estimasi_pengiriman ?: '1-3 Hari',
                    'harga' => number_format($product->harga, 0, ',', '.'),
                    'stok' => $product->stok,
                    'image' => $imageUrl
                 ]) }})">
                <img src="{{ $imageUrl }}" alt="{{ $product->nama_produk }}" class="w-full h-40 object-cover rounded-lg mb-3 border border-gray-100 group-hover:scale-[1.02] transition-transform">
                <div class="flex justify-between items-start mb-2">
                    <p class="text-xs font-semibold uppercase text-blue-600">{{ $product->kategori }}</p>
                    <span class="text-[10px] bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-bold">
                        <i class="fa-solid fa-location-dot mr-1"></i>{{ $product->lokasi ?: 'Sidoarjo' }}
                    </span>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-1 line-clamp-1">{{ $product->nama_produk }}</h3>
                <p class="text-sm text-gray-500 mb-2">{{ $product->komoditas->nama ?? 'Umum' }}</p>
                <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $product->deskripsi ?: '-' }}</p>
                
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase">Harga</p>
                        <p class="font-bold text-blue-600">Rp {{ number_format($product->harga, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-gray-400 font-bold uppercase">Stok</p>
                        <p class="text-xs font-bold {{ $product->stok > 10 ? 'text-green-600' : 'text-amber-600' }}">
                            {{ $product->stok }} unit
                        </p>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-xl border border-gray-100 p-10 text-center text-gray-500">
                Belum ada produk marketplace.
            </div>
        @endforelse
    </div>

    <!-- Product Detail Modal (Redesigned & Robust) -->
    <div id="product-modal" class="hidden fixed inset-0 z-[110] flex items-center justify-center bg-black/60 backdrop-blur-md p-4 md:p-8">
        <div class="bg-white rounded-[32px] shadow-2xl w-full max-w-5xl max-h-[95vh] overflow-hidden flex flex-col md:flex-row transform transition-all duration-300 scale-95 opacity-0" id="product-card">
            
            <!-- Left: Image Section (Better Aspect Ratio) -->
            <div class="md:w-1/2 bg-gray-50 flex items-center justify-center relative border-b md:border-b-0 md:border-r border-gray-100 min-h-[300px]">
                <img id="detail-image" src="" class="w-full h-full object-cover">
                
                <!-- Close Button -->
                <button onclick="closeProductDetail()" class="absolute top-4 left-4 w-10 h-10 bg-white/80 backdrop-blur rounded-full flex items-center justify-center text-gray-800 hover:bg-white transition-all shadow-md z-20">
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <!-- Status Badges Overlay -->
                <div class="absolute bottom-4 left-4 right-4 flex flex-col gap-2 z-10">
                    <div class="bg-white/90 backdrop-blur-sm p-3 rounded-xl shadow-sm border border-white/20">
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-tighter mb-0.5">Dikirim Dari</p>
                        <div class="flex items-center gap-1.5 text-gray-800">
                            <i class="fa-solid fa-location-dot text-blue-500 text-xs"></i>
                            <span id="detail-lokasi" class="font-bold text-xs"></span>
                        </div>
                    </div>
                    <div class="bg-white/90 backdrop-blur-sm p-3 rounded-xl shadow-sm border border-white/20">
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-tighter mb-0.5">Estimasi Pengiriman</p>
                        <div class="flex items-center gap-1.5 text-gray-800">
                            <i class="fa-solid fa-truck-fast text-green-500 text-xs"></i>
                            <span id="detail-estimasi" class="font-bold text-xs"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Content & Action Section -->
            <div class="md:w-1/2 flex flex-col bg-white overflow-hidden">
                <!-- Scrollable Body -->
                <div class="p-6 md:p-10 overflow-y-auto flex-1">
                    <div class="mb-6">
                        <p id="detail-kategori" class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-1"></p>
                        <h2 id="detail-nama" class="text-2xl md:text-3xl font-black text-gray-800 leading-tight"></h2>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-blue-50 p-4 rounded-2xl border border-blue-100">
                            <p class="text-[9px] font-black text-blue-400 uppercase mb-1">Harga Satuan</p>
                            <p class="text-xl font-black text-blue-600">Rp <span id="detail-harga"></span></p>
                        </div>
                        <div class="bg-amber-50 p-4 rounded-2xl border border-amber-100 text-right">
                            <p class="text-[9px] font-black text-amber-400 uppercase mb-1">Tersedia</p>
                            <p class="text-xl font-black text-amber-600"><span id="detail-stok"></span> <span class="text-xs">Unit</span></p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="group">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-1 h-4 bg-blue-600 rounded-full"></div>
                                <h4 class="text-xs font-black text-gray-800 uppercase tracking-widest">Deskripsi</h4>
                            </div>
                            <p id="detail-deskripsi" class="text-sm text-gray-500 leading-relaxed"></p>
                        </div>

                        <div class="pt-6 border-t border-gray-50">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-1 h-4 bg-blue-600 rounded-full"></div>
                                <h4 class="text-xs font-black text-gray-800 uppercase tracking-widest">Spesifikasi Detail</h4>
                            </div>
                            <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-100">
                                <p id="detail-spesifikasi" class="text-sm text-gray-600 whitespace-pre-line leading-relaxed"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fixed Footer Action -->
                <div class="p-6 md:p-8 bg-gray-50 border-t border-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Harga</p>
                            <p class="text-2xl font-black text-blue-600">Rp <span id="modal-total-price"></span></p>
                        </div>
                        <div class="text-right">
                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Stok Tersedia</p>
                            <p class="text-sm font-bold text-gray-700"><span id="detail-stok"></span> Unit</p>
                        </div>
                    </div>

                    <form action="{{ route('pembudidaya.marketplace.cart.add') }}" method="POST" class="flex flex-col sm:flex-row gap-3">
                        @csrf
                        <input type="hidden" name="product_id" id="modal-add-product-id">
                        <div class="flex items-center bg-white rounded-xl p-1 border border-gray-200 shadow-sm w-full sm:w-auto">
                            <button type="button" onclick="adjustQty(-1)" class="w-12 h-12 flex items-center justify-center text-gray-400 hover:text-blue-600 transition-colors"><i class="fa-solid fa-minus"></i></button>
                            <input type="number" name="qty" id="modal-qty" value="1" min="1" class="w-12 text-center font-black text-gray-800 bg-transparent outline-none text-sm">
                            <button type="button" onclick="adjustQty(1)" class="w-12 h-12 flex items-center justify-center text-gray-400 hover:text-blue-600 transition-colors"><i class="fa-solid fa-plus"></i></button>
                        </div>
                        <button class="flex-1 bg-blue-600 text-white rounded-xl font-black py-4 hover:bg-blue-700 shadow-lg shadow-blue-600/20 transition-all flex items-center justify-center gap-2 text-sm uppercase tracking-wider">
                            <i class="fa-solid fa-cart-plus"></i>
                            Tambah ke Keranjang
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentProductPrice = 0;

        function openProductDetail(product) {
            currentProductPrice = parseInt(product.harga.replace(/\./g, ''));
            
            document.getElementById('detail-image').src = product.image;
            document.getElementById('detail-nama').innerText = product.nama;
            document.getElementById('detail-kategori').innerText = product.kategori;
            document.getElementById('detail-harga').innerText = product.harga;
            document.getElementById('detail-stok').innerText = product.stok;
            document.getElementById('detail-deskripsi').innerText = product.deskripsi;
            document.getElementById('detail-spesifikasi').innerText = product.spesifikasi;
            document.getElementById('detail-lokasi').innerText = product.lokasi;
            document.getElementById('detail-estimasi').innerText = product.estimasi;
            document.getElementById('modal-add-product-id').value = product.id;
            document.getElementById('modal-qty').value = 1;
            
            updateModalTotal(1);

            const modal = document.getElementById('product-modal');
            const card = document.getElementById('product-card');

            modal.classList.remove('hidden');
            setTimeout(() => {
                card.classList.remove('scale-95', 'opacity-0');
                card.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeProductDetail() {
            const modal = document.getElementById('product-modal');
            const card = document.getElementById('product-card');

            card.classList.remove('scale-100', 'opacity-100');
            card.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        function adjustQty(amount) {
            const input = document.getElementById('modal-qty');
            const stok = parseInt(document.getElementById('detail-stok').innerText);
            let val = parseInt(input.value) + amount;
            if(val < 1) val = 1;
            if(val > stok) val = stok;
            input.value = val;
            
            updateModalTotal(val);
        }

        function updateModalTotal(qty) {
            const total = currentProductPrice * qty;
            document.getElementById('modal-total-price').innerText = total.toLocaleString('id-ID');
        }
    </script>

    <div class="mt-6">
        {{ $products->withQueryString()->links() }}
    </div>
@endsection
