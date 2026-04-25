@extends('layouts.admin')

@section('content')
<div class="p-8 space-y-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Kelola Produk Marketplace</h1>
        <p class="text-sm text-gray-400">Manajemen data produk, stok, harga, dan status aktif</p>
    </div>

    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Tambah Produk Baru</h3>
        <form action="{{ route('admin.marketplace.products.store') }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @csrf
            <input name="sku" placeholder="SKU (contoh: BIBIT-NILA-002)" class="border border-gray-200 rounded-xl px-4 py-2 text-sm" required>
            <input name="nama_produk" placeholder="Nama Produk" class="border border-gray-200 rounded-xl px-4 py-2 text-sm md:col-span-2" required>
            <select name="kategori" class="border border-gray-200 rounded-xl px-4 py-2 text-sm" required>
                <option value="">Pilih Kategori</option>
                <option value="pakan">Pakan</option>
                <option value="bibit">Bibit</option>
                <option value="alat">Alat</option>
            </select>
            <select name="komoditas_id" class="border border-gray-200 rounded-xl px-4 py-2 text-sm">
                <option value="">Komoditas (Opsional)</option>
                @foreach($komoditas as $item)
                    <option value="{{ $item->id }}">{{ $item->nama }}</option>
                @endforeach
            </select>
            <input type="number" min="1" name="harga" placeholder="Harga" class="border border-gray-200 rounded-xl px-4 py-2 text-sm" required>
            <input type="number" min="0" name="stok" placeholder="Stok" class="border border-gray-200 rounded-xl px-4 py-2 text-sm" required>
            <input type="file" name="gambar_produk" accept=".jpg,.jpeg,.png,.webp" class="border border-gray-200 rounded-xl px-4 py-2 text-sm">
            <input name="lokasi" placeholder="Lokasi Penjual (Sidoarjo)" class="border border-gray-200 rounded-xl px-4 py-2 text-sm">
            <input name="estimasi_pengiriman" placeholder="Estimasi (1-3 Hari)" class="border border-gray-200 rounded-xl px-4 py-2 text-sm">
            <textarea name="deskripsi" rows="2" placeholder="Deskripsi singkat produk..." class="border border-gray-200 rounded-xl px-4 py-2 text-sm"></textarea>
            <textarea name="spesifikasi" rows="2" placeholder="Spesifikasi detail (Nutrisi/Bahan/etc)..." class="border border-gray-200 rounded-xl px-4 py-2 text-sm md:col-span-2"></textarea>
            <button class="bg-purple-600 text-white rounded-xl px-4 py-2 text-sm font-bold hover:bg-purple-700">Simpan Produk</button>
        </form>
    </div>

    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <form method="GET" action="{{ route('admin.marketplace.products') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama produk / SKU" class="border border-gray-200 rounded-xl px-4 py-2 text-sm">
                <select name="kategori" class="border border-gray-200 rounded-xl px-4 py-2 text-sm">
                    <option value="">Semua Kategori</option>
                    <option value="pakan" {{ request('kategori') === 'pakan' ? 'selected' : '' }}>Pakan</option>
                    <option value="bibit" {{ request('kategori') === 'bibit' ? 'selected' : '' }}>Bibit</option>
                    <option value="alat" {{ request('kategori') === 'alat' ? 'selected' : '' }}>Alat</option>
                </select>
                <select name="status" class="border border-gray-200 rounded-xl px-4 py-2 text-sm">
                    <option value="">Semua Status</option>
                    <option value="aktif" {{ request('status') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="nonaktif" {{ request('status') === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                </select>
                <div class="flex gap-2">
                    <button class="bg-purple-600 text-white rounded-xl px-4 py-2 text-sm font-bold hover:bg-purple-700">Filter</button>
                    <a href="{{ route('admin.marketplace.products') }}" class="bg-gray-100 text-gray-700 rounded-xl px-4 py-2 text-sm font-bold hover:bg-gray-200">Reset</a>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 text-[11px] font-black text-gray-400 uppercase tracking-widest">
                        <th class="px-5 py-4">SKU</th>
                        <th class="px-5 py-4">Produk</th>
                        <th class="px-5 py-4">Gambar</th>
                        <th class="px-5 py-4">Kategori</th>
                        <th class="px-5 py-4">Harga</th>
                        <th class="px-5 py-4">Stok</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($products as $product)
                        <tr>
                            <td class="px-5 py-4 text-sm font-bold text-gray-700">{{ $product->sku }}</td>
                            <td class="px-5 py-4">
                                <p class="text-sm font-bold text-gray-800">{{ $product->nama_produk }}</p>
                                <p class="text-xs text-gray-500">{{ $product->komoditas->nama ?? 'Umum' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                @php
                                    $defaultImage = match($product->kategori) {
                                        'bibit' => asset('assets/img/marketplace-bibit.svg'),
                                        'pakan' => asset('assets/img/marketplace-pakan.svg'),
                                        default => asset('assets/img/marketplace-alat.svg'),
                                    };
                                    $imageUrl = $product->gambar_produk ? asset('storage/' . $product->gambar_produk) : $defaultImage;
                                @endphp
                                <img src="{{ $imageUrl }}" alt="{{ $product->nama_produk }}" class="h-12 w-12 object-cover rounded-lg border border-gray-100">
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600">{{ $product->kategori }}</td>
                            <td class="px-5 py-4 text-sm font-bold text-gray-700">Rp {{ number_format($product->harga, 0, ',', '.') }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $product->stok }}</td>
                            <td class="px-5 py-4">
                                <span class="px-3 py-1 rounded-lg text-xs font-bold {{ $product->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-col gap-2">
                                    <form action="{{ route('admin.marketplace.products.toggle', $product->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button class="text-xs px-3 py-1 rounded-lg bg-blue-100 text-blue-700 font-bold">
                                            {{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                    <button
                                        type="button"
                                        class="text-xs px-3 py-1 rounded-lg bg-purple-100 text-purple-700 font-bold"
                                        onclick="openEditModal(this)"
                                        data-id="{{ $product->id }}"
                                        data-sku="{{ $product->sku }}"
                                        data-nama="{{ $product->nama_produk }}"
                                        data-kategori="{{ $product->kategori }}"
                                        data-komoditas="{{ $product->komoditas_id }}"
                                        data-deskripsi="{{ $product->deskripsi }}"
                                        data-harga="{{ $product->harga }}"
                                        data-stok="{{ $product->stok }}"
                                        data-lokasi="{{ $product->lokasi }}"
                                        data-estimasi="{{ $product->estimasi_pengiriman }}"
                                        data-spesifikasi="{{ $product->spesifikasi }}"
                                    >
                                        Edit
                                    </button>
                                    <form action="{{ route('admin.marketplace.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Hapus produk ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-xs px-3 py-1 rounded-lg bg-red-100 text-red-700 font-bold">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-sm text-gray-500">Belum ada data produk.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-5 bg-gray-50 border-t border-gray-100">
            {{ $products->links('pagination::tailwind') }}
        </div>
    </div>
</div>

<div id="editModal" class="fixed inset-0 hidden z-50 bg-black/40 items-center justify-center p-4">
    <div class="bg-white w-full max-w-xl rounded-2xl p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Edit Produk</h3>
        <form id="editForm" method="POST" enctype="multipart/form-data" class="space-y-3">
            @csrf
            @method('PUT')
            <input id="edit_sku" name="sku" class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm" required>
            <input id="edit_nama_produk" name="nama_produk" class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm" required>
            <select id="edit_kategori" name="kategori" class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm" required>
                <option value="pakan">Pakan</option>
                <option value="bibit">Bibit</option>
                <option value="alat">Alat</option>
            </select>
            <select id="edit_komoditas_id" name="komoditas_id" class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm">
                <option value="">Komoditas (Opsional)</option>
                @foreach($komoditas as $item)
                    <option value="{{ $item->id }}">{{ $item->nama }}</option>
                @endforeach
            </select>
            <input id="edit_harga" type="number" min="1" name="harga" class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm" required>
            <input id="edit_stok" type="number" min="0" name="stok" class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm" required>
            <div>
                <p class="text-xs text-gray-400 mb-1">Gambar saat ini</p>
                <img id="edit_preview" src="" alt="Preview Gambar Produk" class="h-20 w-20 object-cover rounded-lg border border-gray-100 hidden">
                <p id="edit_no_preview" class="text-xs text-gray-400">Belum ada gambar.</p>
            </div>
            <input type="file" name="gambar_produk" accept=".jpg,.jpeg,.png,.webp" class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm">
            <input id="edit_lokasi" name="lokasi" placeholder="Lokasi" class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm">
            <input id="edit_estimasi" name="estimasi_pengiriman" placeholder="Estimasi Pengiriman" class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm">
            <textarea id="edit_deskripsi" name="deskripsi" rows="2" placeholder="Deskripsi singkat" class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm"></textarea>
            <textarea id="edit_spesifikasi" name="spesifikasi" rows="3" placeholder="Spesifikasi detail" class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm"></textarea>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded-xl bg-gray-100 text-gray-700 text-sm font-bold">Batal</button>
                <button class="px-4 py-2 rounded-xl bg-purple-600 text-white text-sm font-bold">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(button) {
        const id = button.dataset.id;
        const sku = button.dataset.sku;
        const nama = button.dataset.nama;
        const kategori = button.dataset.kategori;
        const komoditasId = button.dataset.komoditas;
        const deskripsi = button.dataset.deskripsi || '';
        const harga = button.dataset.harga;
        const stok = button.dataset.stok;
        document.getElementById('editForm').action = `/admin/marketplace/products/${id}`;
        document.getElementById('edit_sku').value = sku;
        document.getElementById('edit_nama_produk').value = nama;
        document.getElementById('edit_kategori').value = kategori;
        document.getElementById('edit_komoditas_id').value = komoditasId || '';
        document.getElementById('edit_deskripsi').value = deskripsi;
        document.getElementById('edit_harga').value = harga;
        document.getElementById('edit_stok').value = stok;
        document.getElementById('edit_lokasi').value = button.dataset.lokasi || '';
        document.getElementById('edit_estimasi').value = button.dataset.estimasi || '';
        document.getElementById('edit_spesifikasi').value = button.dataset.spesifikasi || '';
        const row = button.closest('tr');
        const img = row.querySelector('img');
        const preview = document.getElementById('edit_preview');
        const noPreview = document.getElementById('edit_no_preview');
        if (img && img.src) {
            preview.src = img.src;
            preview.classList.remove('hidden');
            noPreview.classList.add('hidden');
        } else {
            preview.classList.add('hidden');
            noPreview.classList.remove('hidden');
        }
        document.getElementById('editModal').classList.remove('hidden');
        document.getElementById('editModal').classList.add('flex');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.remove('flex');
        document.getElementById('editModal').classList.add('hidden');
    }
</script>
@endsection
