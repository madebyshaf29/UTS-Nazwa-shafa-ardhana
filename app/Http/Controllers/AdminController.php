<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Komoditas;
use App\Models\WilayahAdmin;
use App\Models\JenisBantuanAdmin;
use App\Models\TopikPendampingAdmin;
use App\Models\PermohonanBantuan;
use App\Models\PengajuanPendampingan;
use App\Models\MarketplacePayment;
use App\Models\MarketplaceProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function index()
{
    // 1. Card Statistik Riil
    $stats = [
        'total_pembudidaya' => User::where('role', 'pembudidaya')->count(),
        'bantuan_disalurkan' => PermohonanBantuan::where('status', 'selesai')->count(),
        'permohonan_pending' => PermohonanBantuan::where('status', 'siap_disetujui_admin')->count(),
        'pendampingan_ini' => PengajuanPendampingan::whereMonth('created_at', now()->month)->count(),
    ];

    // 2. Data Trend Pendaftaran (6 Bulan Terakhir)
    $registrations = User::where('role', 'pembudidaya')
        ->select(
            DB::raw('count(id_user) as total'), 
            DB::raw("DATE_FORMAT(created_at, '%b') as month")
        )
        ->where('created_at', '>=', now()->subMonths(6))
        ->groupBy('month')
        ->orderBy(DB::raw('MIN(created_at)'), 'asc') 
        ->get();

    $trendData = [
        'labels' => $registrations->pluck('month'),
        'values' => $registrations->pluck('total'),
    ];

    // 3. Data Distribusi Komoditas (Jenis Ikan)
    $commodities = DB::table('usaha_budidaya')
        ->select('jenis_ikan', DB::raw('count(*) as total'))
        ->groupBy('jenis_ikan')
        ->get();
        
    $commodityData = [
        'labels' => $commodities->pluck('jenis_ikan'),
        'values' => $commodities->pluck('total'),
    ];

    // 4. Data Sebaran Wilayah (Kecamatan)
    $wilayah = DB::table('profil_pembudidaya')
        ->select('kecamatan', 
            DB::raw('count(*) as total'),
            DB::raw('sum(case when status_survei = "sudah" then 1 else 0 end) as terverifikasi'))
        ->groupBy('kecamatan')
        ->get();

    $regionData = [
        'labels' => $wilayah->pluck('kecamatan'),
        'total' => $wilayah->pluck('total'),
        'terverifikasi' => $wilayah->pluck('terverifikasi'),
    ];

    return view('admin.dashboard', compact('stats', 'trendData', 'commodityData', 'regionData'));
}

    public function komoditasIndex(Request $request)
{
    $query = DB::table('master_komoditas');

    // Fitur Cari Komoditas (image_6782e4.png)
    if ($request->has('search')) {
        $query->where('nama', 'like', '%' . $request->search . '%');
    }

    $komoditas = $query->paginate(10);

    return view('admin.master.komoditas', compact('komoditas'));
}

    public function komoditasStore(Request $request)
{
    // 1. Validasi input
    $request->validate([
        'nama' => 'required|string|max:255|unique:master_komoditas,nama',
    ]);

    // 2. Simpan ke database menggunakan Model Komoditas
    Komoditas::create([
        'nama' => $request->nama,
        'status' => 'Aktif' // Default status aktif sesuai gambar tabel
    ]);

    // 3. Kembali ke halaman sebelumnya dengan pesan sukses
    return back()->with('success_crud', 'Komoditas baru berhasil ditambahkan!');
}

    public function komoditasUpdate(Request $request, $id)
{
    // 1. Validasi input
    $request->validate([
        // Pastikan nama unik, tapi abaikan untuk ID yang sedang diedit ini
        'nama' => 'required|string|max:255|unique:master_komoditas,nama,' . $id,
    ]);

    // 2. Cari data berdasarkan ID
    $komoditas = Komoditas::findOrFail($id);

    // 3. Update data
    $komoditas->update([
        'nama' => $request->nama,
    ]);

    // 4. Kembali dengan pesan sukses
    return back()->with('success_crud', 'Data komoditas berhasil diperbarui!');
}

    public function komoditasDestroy($id)
    {
        // 1. Cari data komoditas berdasarkan ID yang dikirim dari form
        // Jika ID tidak ditemukan, Laravel akan otomatis menampilkan error 404.
        $komoditas = Komoditas::findOrFail($id);

        // 2. Jalankan fungsi hapus
        $komoditas->delete();

        // 3. Kembali ke halaman sebelumnya dengan pesan sukses
        // Pesan ini akan muncul di bagian atas tabel jika Anda sudah memasang alert success.
        return back()->with('success_crud', 'Data komoditas "' . $komoditas->nama . '" berhasil dihapus!');
    }

    public function wilayahIndex(Request $request)
{
    $query = WilayahAdmin::query();
    
    // Fitur cari nama wilayah (image_7431ca.png)
    if ($request->has('search')) {
        $query->where('nama', 'like', '%' . $request->search . '%');
    }
    
    $wilayah = $query->paginate(10);
    return view('admin.master.wilayah', compact('wilayah'));
}

public function wilayahStore(Request $request)
{
    $request->validate(['nama' => 'required|string|max:255|unique:master_wilayah,nama']);
    
    WilayahAdmin::create([
        'nama' => $request->nama,
        'status' => 'Aktif'
    ]);

    return back()->with('success_crud', 'Data Berhasil Disimpan'); // Memicu pop-up sukses
}

public function wilayahUpdate(Request $request, $id)
{
    $request->validate(['nama' => 'required|string|max:255|unique:master_wilayah,nama,'.$id]);
    
    WilayahAdmin::findOrFail($id)->update(['nama' => $request->nama]);
    
    return back()->with('success_crud', 'Data Berhasil Diperbarui');
}

public function wilayahDestroy($id)
{
    WilayahAdmin::findOrFail($id)->delete();
    return back()->with('success_crud', 'Data Berhasil Dihapus');
}

public function jenisBantuanIndex(Request $request)
{
    $query = JenisBantuanAdmin::query();
    if ($request->has('search')) {
        $query->where('nama_bantuan', 'like', '%' . $request->search . '%')
              ->orWhere('kategori', 'like', '%' . $request->search . '%');
    }
    $bantuan = $query->paginate(10);
    return view('admin.master.jenis-bantuan', compact('bantuan'));
}

public function jenisBantuanStore(Request $request)
{
    $request->validate([
        'nama_bantuan' => 'required|string|max:255',
        'kategori' => 'required|string|max:255'
    ]);

    JenisBantuanAdmin::create([
        'nama_bantuan' => $request->nama_bantuan,
        'kategori' => $request->kategori,
        'status' => 'Aktif'
    ]);

    return back()->with('success_crud', 'Data Berhasil Disimpan');
}

public function jenisBantuanUpdate(Request $request, $id)
{
    $request->validate([
        'nama_bantuan' => 'required|string|max:255',
        'kategori' => 'required|string|max:255'
    ]);

    JenisBantuanAdmin::findOrFail($id)->update([
        'nama_bantuan' => $request->nama_bantuan,
        'kategori' => $request->kategori
    ]);

    return back()->with('success_crud', 'Data Berhasil Diperbarui');
}

public function jenisBantuanDestroy($id)
{
    JenisBantuanAdmin::findOrFail($id)->delete();
    return back()->with('success_crud', 'Data Berhasil Dihapus');
}

public function topikIndex(Request $request)
{
    $query = TopikPendampingAdmin::query();
    if ($request->has('search')) {
        $query->where('nama_topik', 'like', '%' . $request->search . '%')
              ->orWhere('kategori', 'like', '%' . $request->search . '%');
    }
    $topik = $query->paginate(10);
    return view('admin.master.topik-pendamping', compact('topik'));
}

public function topikStore(Request $request)
{
    $request->validate([
        'nama_topik' => 'required|string|max:255',
        'kategori' => 'required|string|max:255',
        'deskripsi' => 'required'
    ]);

    TopikPendampingAdmin::create($request->all());

    return back()->with('success_crud', 'Data Berhasil Disimpan');
}

public function topikUpdate(Request $request, $id)
{
    $request->validate([
        'nama_topik' => 'required|string|max:255',
        'kategori' => 'required|string|max:255',
        'deskripsi' => 'required'
    ]);

    TopikPendampingAdmin::findOrFail($id)->update($request->all());
    return back()->with('success_crud', 'Data Berhasil Diperbarui');
}

public function topikDestroy($id)
{
    TopikPendampingAdmin::findOrFail($id)->delete();
    return back()->with('success_crud', 'Data Berhasil Dihapus');
}

public function permohonanIndex(Request $request)
{
    // 1. Statistik untuk Kartu Atas
    $stats = [
        'total' => PermohonanBantuan::count(),
        'disetujui' => PermohonanBantuan::where('status', 'disetujui_admin')->count(),
        'ditolak' => PermohonanBantuan::where('status', 'ditolak')->count(),
        'menunggu' => PermohonanBantuan::where('status', 'siap_disetujui_admin')->count(),
    ];

    // 2. Ambil Data Tabel dengan Join
    $query = PermohonanBantuan::join('profil_pembudidaya', 'permohonan_bantuans.id_user', '=', 'profil_pembudidaya.id_user')
        ->select(
            'permohonan_bantuans.*',
            'profil_pembudidaya.nama as nama_pemohon',
            'profil_pembudidaya.desa as lokasi',
            'profil_pembudidaya.kecamatan'
        );

    // Pencarian
    if ($request->has('search')) {
        $query->where('profil_pembudidaya.nama', 'like', '%' . $request->search . '%');
    }

    $permohonan = $query->latest()->paginate(10);

    return view('admin.permohonan.index', compact('stats', 'permohonan'));
}

    public function getDetailData($id)
{
    // Ambil data bantuan beserta profil pembudidaya
    $data = PermohonanBantuan::join('profil_pembudidaya', 'permohonan_bantuans.id_user', '=', 'profil_pembudidaya.id_user')
        ->select(
            'permohonan_bantuans.*', 
            'profil_pembudidaya.nama as nama_pemohon'
        )
        ->where('permohonan_bantuans.id', $id)
        ->first();

    if (!$data) {
        return response()->json(['message' => 'Data tidak ditemukan'], 404);
    }

    return response()->json($data);
}

    public function permohonanUpdateVerifikasi(Request $request, $id)
{
    $request->validate([
        'skala_prioritas' => 'required',
        'catatan_petugas' => 'required',
        'status' => 'required',
        'nilai_estimasi' => 'required|numeric'
    ]);

    $permohonan = PermohonanBantuan::findOrFail($id);
    
    $permohonan->update([
        'skala_prioritas' => $request->skala_prioritas,
        'catatan_petugas' => $request->catatan_petugas,
        'status' => $request->status,
        'nilai_estimasi' => $request->nilai_estimasi,
        'updated_at' => now()
    ]);

    return back()->with('success_crud', 'Hasil Verifikasi Berhasil Disimpan');
}

    public function permohonanDestroy($id)
{
    // 1. Cari data permohonan
    $permohonan = PermohonanBantuan::findOrFail($id);

    // 2. Hapus data dari database
    $permohonan->delete();

    // 3. Kembali dengan pesan sukses untuk memicu pop-up
    return back()->with('success_crud', 'Data Permohonan Berhasil Dihapus');
}
public function pendampinganIndex(Request $request)
{
    $tab = $request->get('tab', 'monitoring');

    // 1. Statistik Kartu Atas (image_8241ae.png)
    $stats = [
        'total' => PengajuanPendampingan::count(),
        'selesai' => PengajuanPendampingan::where('status', 'selesai')->count(),
        'berjalan' => PengajuanPendampingan::where('status', 'sedang_berjalan')->count(),
        'menunggu' => PengajuanPendampingan::where('status', 'dijadwalkan')->count(),
    ];

    if ($tab == 'rekap') {
        // 2. Data Rekap Topik (image_8241ae.png)
        $topikStats = DB::table('pengajuan_pendampingans')
        ->select('topik', DB::raw('count(*) as total'))
        ->groupBy('topik')
        ->get();

        // 3. Data Rekap Wilayah (image_8241ae.png)
       $wilayahStats = DB::table('pengajuan_pendampingans')
        ->join('profil_pembudidaya', 'pengajuan_pendampingans.id_user', '=', 'profil_pembudidaya.id_user')
        ->select('profil_pembudidaya.kecamatan as wilayah', DB::raw('count(*) as total'))
        ->groupBy('profil_pembudidaya.kecamatan')
        ->get();

        return view('admin.pendampingan.index', compact('stats', 'topikStats', 'wilayahStats', 'tab'));
    }

    // Logika Monitoring Pelaksana (Data Tabel)
    $pendampingan = PengajuanPendampingan::leftJoin('users as petugas', 'pengajuan_pendampingans.id_petugas', '=', 'petugas.id_user')
        ->leftJoin('profil_pembudidaya', 'pengajuan_pendampingans.id_user', '=', 'profil_pembudidaya.id_user')
        ->leftJoin('master_topik_pendamping', 'pengajuan_pendampingans.id_topik', '=', 'master_topik_pendamping.id')
        ->select('pengajuan_pendampingans.*', 'petugas.nama_lengkap as nama_petugas', 'profil_pembudidaya.nama as nama_pembudidaya', 'master_topik_pendamping.nama_topik')
        ->latest()->paginate(10);

    return view('admin.pendampingan.index', compact('stats', 'pendampingan', 'tab'));
}

    // app/Http/Controllers/AdminController.php

public function getPendampinganDetail($id)
{
    $data = PengajuanPendampingan::join('users', 'pengajuan_pendampingans.id_user', '=', 'users.id_user')
        ->join('profil_pembudidaya', 'users.id_user', '=', 'profil_pembudidaya.id_user')
        ->leftJoin('usaha_budidaya', 'profil_pembudidaya.id_profil_pembudidaya', '=', 'usaha_budidaya.id_profil_pembudidaya')
        ->select(
            'pengajuan_pendampingans.*',
            'pengajuan_pendampingans.jam_kunjungan',
            'profil_pembudidaya.nama as nama_pembudidaya',
            'profil_pembudidaya.nomor_hp',
            'profil_pembudidaya.alamat',
            'profil_pembudidaya.desa',
            'profil_pembudidaya.kecamatan',
            'usaha_budidaya.jenis_ikan',
            'usaha_budidaya.luas_kolam',
            'usaha_budidaya.jumlah_kolam'
        )
        ->where('pengajuan_pendampingans.id', $id)
        ->first();

    if (!$data) {
        return response()->json(['message' => 'Data tidak ditemukan'], 404);
    }

    return response()->json($data);
}

    public function laporanIndex()
{
    $now = Carbon::now();


    $ringkasan = [
        'pendaftar_baru' => User::where('role', 'pembudidaya')
                            ->whereMonth('created_at', $now->month)->count(),
        'verifikasi_selesai' => DB::table('permohonan_bantuans')
                            ->where('status', '!=', 'pending')
                            ->whereMonth('updated_at', $now->month)->count(),
    ];

    $topKomoditas = DB::table('usaha_budidaya')
        ->select('jenis_ikan as nama', DB::raw('count(*) as jumlah'))
        ->groupBy('jenis_ikan')
        ->orderBy('jumlah', 'desc')
        ->limit(5)
        ->get()
        ->map(function($item) {
            return [
                'nama' => $item->nama,
                'jumlah' => $item->jumlah
            ];
        });

    return view('admin.laporan.index', compact('ringkasan', 'topKomoditas'));
}

public function marketplacePayments(Request $request)
{
    $query = MarketplacePayment::query()
        ->with(['order.user'])
        ->latest();

    if ($request->filled('status')) {
        $query->where('transaction_status', $request->status);
    }

    if ($request->filled('payment_type')) {
        $query->where('payment_type', $request->payment_type);
    }

    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('order_code', 'like', '%' . $search . '%')
                ->orWhere('transaction_id', 'like', '%' . $search . '%');
        });
    }

    if ($request->filled('tanggal_awal')) {
        $query->whereDate('created_at', '>=', $request->tanggal_awal);
    }

    if ($request->filled('tanggal_akhir')) {
        $query->whereDate('created_at', '<=', $request->tanggal_akhir);
    }

    $payments = $query->paginate(15)->withQueryString();

    $stats = [
        'total' => MarketplacePayment::count(),
        'berhasil' => MarketplacePayment::whereIn('transaction_status', ['settlement', 'capture'])->count(),
        'pending' => MarketplacePayment::where('transaction_status', 'pending')->count(),
        'gagal' => MarketplacePayment::whereIn('transaction_status', ['deny', 'cancel', 'expire'])->count(),
    ];

    $statusOptions = MarketplacePayment::query()
        ->whereNotNull('transaction_status')
        ->distinct()
        ->pluck('transaction_status');

    $paymentTypeOptions = MarketplacePayment::query()
        ->whereNotNull('payment_type')
        ->distinct()
        ->pluck('payment_type');

    return view('admin.marketplace.payments', compact(
        'payments',
        'stats',
        'statusOptions',
        'paymentTypeOptions'
    ));
}

public function marketplaceProducts(Request $request)
{
    $query = MarketplaceProduct::query()->with('komoditas');

    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('nama_produk', 'like', '%' . $search . '%')
                ->orWhere('sku', 'like', '%' . $search . '%');
        });
    }

    if ($request->filled('kategori')) {
        $query->where('kategori', $request->kategori);
    }

    if ($request->filled('status')) {
        $query->where('is_active', $request->status === 'aktif');
    }

    $products = $query->latest()->paginate(12)->withQueryString();
    $komoditas = Komoditas::where('status', 'Aktif')->orderBy('nama')->get();

    return view('admin.marketplace.products', compact('products', 'komoditas'));
}

public function marketplaceProductsStore(Request $request)
{
    $validated = $request->validate([
        'sku' => 'required|string|max:100|unique:marketplace_products,sku',
        'nama_produk' => 'required|string|max:255',
        'kategori' => 'required|in:pakan,bibit,alat',
        'komoditas_id' => 'nullable|exists:master_komoditas,id',
        'deskripsi' => 'nullable|string',
        'gambar_produk' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'harga' => 'required|integer|min:1',
        'stok' => 'required|integer|min:0',
        'lokasi' => 'nullable|string|max:255',
        'estimasi_pengiriman' => 'nullable|string|max:255',
        'spesifikasi' => 'nullable|string',
    ]);

    if ($request->hasFile('gambar_produk')) {
        $validated['gambar_produk'] = $request->file('gambar_produk')->store('marketplace-products', 'public');
    }

    $validated['is_active'] = true;
    MarketplaceProduct::create($validated);

    return back()->with('success_crud', 'Produk marketplace berhasil ditambahkan.');
}

public function marketplaceProductsUpdate(Request $request, $id)
{
    $product = MarketplaceProduct::findOrFail($id);

    $validated = $request->validate([
        'sku' => 'required|string|max:100|unique:marketplace_products,sku,' . $product->id,
        'nama_produk' => 'required|string|max:255',
        'kategori' => 'required|in:pakan,bibit,alat',
        'komoditas_id' => 'nullable|exists:master_komoditas,id',
        'deskripsi' => 'nullable|string',
        'gambar_produk' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'harga' => 'required|integer|min:1',
        'stok' => 'required|integer|min:0',
        'lokasi' => 'nullable|string|max:255',
        'estimasi_pengiriman' => 'nullable|string|max:255',
        'spesifikasi' => 'nullable|string',
    ]);

    if ($request->hasFile('gambar_produk')) {
        if ($product->gambar_produk) {
            Storage::disk('public')->delete($product->gambar_produk);
        }
        $validated['gambar_produk'] = $request->file('gambar_produk')->store('marketplace-products', 'public');
    }

    $product->update($validated);

    return back()->with('success_crud', 'Produk marketplace berhasil diperbarui.');
}

public function marketplaceProductsToggle($id)
{
    $product = MarketplaceProduct::findOrFail($id);
    $product->update(['is_active' => !$product->is_active]);

    return back()->with('success_crud', 'Status produk berhasil diubah.');
}

public function marketplaceProductsDestroy($id)
{
    $product = MarketplaceProduct::findOrFail($id);
    if ($product->gambar_produk) {
        Storage::disk('public')->delete($product->gambar_produk);
    }
    $product->delete();

    return back()->with('success_crud', 'Produk marketplace berhasil dihapus.');
}
}
