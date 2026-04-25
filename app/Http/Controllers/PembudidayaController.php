<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\PermohonanBantuan;
use App\Models\PengajuanPendampingan;
use App\Models\ProfilPembudidaya;
use App\Models\Komoditas;
use App\Models\wilayahAdmin;
use App\Models\TopikPendampingAdmin;
use App\Models\UsahaBudidaya;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceCart;
use App\Models\MarketplaceOrder;
use Carbon\Carbon;

class PembudidayaController extends Controller
{
    // ==========================================================
    // 1. DASHBOARD
    // ==========================================================
    public function dashboard()
{
    $id_user = Auth::user()->id_user;

    // 1. Statistik Riil
    $total_permohonan = PermohonanBantuan::where('id_user', $id_user)->count();
    $pendampingan_selesai = PengajuanPendampingan::where('id_user', $id_user)
                            ->where('status', 'selesai')->count();
   $total_bantuan = PermohonanBantuan::where('id_user', $id_user)
        ->whereIn('status', ['disetujui_admin', 'selesai', 'dikirim']) // Hanya hitung jika sudah ACC
        ->sum('nilai_estimasi');

    // 2. Profil & Usaha Data
    $profil = ProfilPembudidaya::where('id_user', $id_user)->first();
    $usaha = DB::table('usaha_budidaya')
                ->where('id_profil_pembudidaya', $profil->id_profil_pembudidaya ?? 0)
                ->first();

    // 3. Logic Status Verifikasi (Urutan pengecekan sudah benar)
    if (!$profil || !$profil->NIK) {
        $status_verifikasi = 'Data Profil Belum Lengkap';
    } elseif (!$usaha) {
        $status_verifikasi = 'Data Usaha Belum Diisi';
    } elseif ($usaha->status_izin == 'disetujui') {
        $status_verifikasi = 'Lengkap'; 
    } elseif ($usaha->status_izin == 'revisi') {
        $status_verifikasi = 'Perlu Revisi Data';
    } elseif ($usaha->status_izin == 'ditolak') {
        $status_verifikasi = 'Ditolak';
    } else {
        $status_verifikasi = 'Menunggu Verifikasi UPT';
    }

    // 4. Logic Timeline Terpadu (PENTING: Urutkan sebelum di-map)
    // Ambil data bantuan
    $bantuan = PermohonanBantuan::where('id_user', $id_user)
            ->select('jenis_bantuan as title', 'created_at', 'updated_at', 'status', DB::raw("'bantuan' as tipe"))
            ->get();

    // Ambil data pendampingan
    $pendampingan = PengajuanPendampingan::where('id_user', $id_user)
            ->select('topik as title', 'created_at', 'updated_at', 'status', DB::raw("'pendampingan' as tipe"))
            ->get();

    // Gabungkan, urutkan berdasarkan waktu pembaruan terakhir (updated_at), lalu ambil 5
    $timeline_activities = $bantuan->concat($pendampingan)
            ->sortByDesc('updated_at') // Menggunakan updated_at agar status baru muncul di atas
            ->take(5)
            ->map(function($item) {
                $prefix = ($item->tipe == 'bantuan') ? 'Bantuan: ' : 'Pendampingan: ';
                return [
                    'title' => $prefix . ucfirst($item->title),
                    // Format waktu dilakukan setelah sorting agar sorting akurat
                    'date'  => $item->updated_at->diffForHumans(), 
                    'description' => 'Status: ' . ucfirst(str_replace('_', ' ', $item->status)),
                    'status' => ($item->status == 'selesai') ? 'done' : 'current'
                ];
            });

    return view('pembudidaya.dashboard', compact(
        'total_permohonan', 'pendampingan_selesai', 'status_verifikasi', 'total_bantuan', 'timeline_activities'
    ));
}

    // ==========================================================
    // 2. PROFIL
    // ==========================================================
    public function profil() {
        // Ambil user beserta profilnya
        $user = Auth::user();
        $profil = ProfilPembudidaya::where('id_user', $user->id_user)->first();
        $master_komoditas = Komoditas::where('status', 'Aktif')->orderBy('nama', 'asc')->get();
        $master_wilayah = WilayahAdmin::where('status', 'Aktif')->orderBy('nama', 'asc')->get();
        return view('pembudidaya.profil', compact('user', 'profil','master_komoditas', 'master_wilayah'));
    }
    

    public function updateProfil(Request $request)
{
    $id_user = Auth::user()->id_user;
    
    // Ambil data profil yang sudah ada atau siapkan variabel null
    $profil = ProfilPembudidaya::where('id_user', $id_user)->first();

    // --- BAGIAN 1: UPDATE DATA DIRI (Hanya jika input 'nama' ada) ---
    if ($request->has('nama')) {
        $request->validate([
            'nama' => 'required|string|max:255',
            'NIK' => 'required|numeric|digits:16',
            'nomor_hp' => 'required',
            'alamat' => 'required|string',
            'kecamatan' => 'required|string',
        ]);

        $profil = ProfilPembudidaya::updateOrCreate(
            ['id_user' => $id_user], 
            [
                'nama' => $request->nama,
                'NIK'  => $request->NIK,
                'alamat' => $request->alamat,
                'nomor_hp' => $request->nomor_hp,
                'kecamatan' => $request->kecamatan ?? '-',
                'desa' => $request->desa ?? '-',
                'tipe_pembudidaya' => 'Perorangan',
            ]
        );
    }

    // --- BAGIAN 2: UPDATE DETAIL USAHA (Hanya jika input 'jenis_ikan' ada) ---
    if ($request->has('jenis_ikan')) {
        $request->validate([
            'jenis_ikan' => 'required',
            'luas_kolam' => 'required|numeric',
            'tipe_kolam' => 'required',
        ]);

        // Pastikan profil sudah ada sebelum membuat detail usaha
        if (!$profil) {
            $profil = ProfilPembudidaya::updateOrCreate(['id_user' => $id_user], [
                'nama' => Auth::user()->nama_lengkap,
                'nomor_hp' => Auth::user()->nomor_hp,
            ]);
        }

        UsahaBudidaya::updateOrCreate(
            ['id_profil_pembudidaya' => $profil->id_profil_pembudidaya],
            [
                'jenis_ikan' => $request->jenis_ikan,
                'luas_kolam' => $request->luas_kolam,
                'tipe_kolam'  => $request->tipe_kolam,
                'jumlah_kolam' => 1,
                'kapasitas_produksi' => '0'
            ]
        );
    }

    return back()->with('success', 'Data Berhasil Diperbarui');
}
    // ==========================================================
    // 3. AJUKAN BANTUAN
    // ==========================================================
    public function ajukanBantuan() {
        return view('pembudidaya.ajukan');
    }

    public function storeBantuan(Request $request) {
        $request->validate([
            'jenis_bantuan' => 'required',
            'detail_kebutuhan' => 'required',
            'file_permohonan' => 'required|mimes:pdf|max:5120', // Max 5MB
            'file_legalitas' => 'required|mimes:pdf,jpg,jpeg|max:5120',
        ]);

        $path1 = $request->file('file_permohonan')->store('dokumen', 'public');
        $path2 = $request->file('file_legalitas')->store('dokumen', 'public');

        PermohonanBantuan::create([
            'id_user' => Auth::user()->id_user,
            'no_tiket' => 'PB-' . date('ymd') . '-' . rand(100, 999),
            'jenis_bantuan' => $request->jenis_bantuan,
            'detail_kebutuhan' => $request->detail_kebutuhan,
            'file_proposal' => $path1,
            'file_legalitas' => $path2,
            'status' => 'pending'
        ]);

        return redirect()->route('pembudidaya.status')->with('success', 'Permohonan Berhasil Dikirim!');
    }

    // ==========================================================
    // 4. STATUS & LACAK
    // ==========================================================
   public function statusLacak() {
    $id_user = Auth::id();
    
    // 1. Ambil profil
    $profil = ProfilPembudidaya::where('id_user', $id_user)->first();
    
    // 2. Ambil status izin dari usaha_budidaya
    $status_izin = DB::table('usaha_budidaya')
                    ->where('id_profil_pembudidaya', $profil->id_profil_pembudidaya ?? 0)
                    ->value('status_izin'); // Ambil nilai kolom status_izin saja

    // 3. Ambil riwayat permohonan bantuan
    $permohonan = PermohonanBantuan::where('id_user', $id_user)
                    ->orderBy('created_at', 'desc')
                    ->get();
    
    // Kirim data status_izin ke view
    return view('pembudidaya.status', compact('permohonan', 'profil', 'status_izin'));
}
    // ==========================================================
    // 5. PENERIMAAN (KONFIRMASI)
    // ==========================================================
    public function penerimaan() {
        // Hanya ambil yang statusnya 'dikirim' (siap konfirmasi) atau 'selesai' (sudah dikonfirmasi)
        $daftar_bantuan = PermohonanBantuan::where('id_user', Auth::user()->id_user)
                            ->whereIn('status', ['dikirim', 'selesai'])
                            ->orderBy('updated_at', 'desc')
                            ->get();

        return view('pembudidaya.penerimaan', compact('daftar_bantuan'));
    }

    public function storeKonfirmasi(Request $request) {
        $request->validate([
            'kode_tiket' => 'required',
            'tanggal_terima' => 'required|date',
            'foto_bukti' => 'required|image|max:5120'
        ]);

        $bantuan = PermohonanBantuan::where('no_tiket', $request->kode_tiket)->firstOrFail();
        $path = $request->file('foto_bukti')->store('bukti_terima', 'public');

        $bantuan->update([
            'status' => 'selesai',
            'tanggal_diterima' => $request->tanggal_terima,
            'catatan_penerimaan' => $request->catatan,
            'foto_bukti_terima' => $path
        ]);

        return back()->with('success', 'Konfirmasi Penerimaan Berhasil!');
    }

    // ==========================================================
    // 6. PENDAMPINGAN TEKNIS (Ajukan & Feedback)
    // ==========================================================
    public function ajukanPendampingan() {
        $daftar_topik = TopikPendampingAdmin::orderBy('nama_topik', 'asc')->get();
        return view('pembudidaya.pendampingan-ajukan', compact('daftar_topik'));
    }

    public function storePendampingan(Request $request) {
        $request->validate([
            'topik_pendampingan' => 'required',
            'detail_kebutuhan' => 'required',
        ]);

        PengajuanPendampingan::create([
            'id_user' => Auth::user()->id_user,
            'topik' => $request->topik_pendampingan,
            'detail_keluhan' => $request->detail_kebutuhan,
            'status' => 'pending'
        ]);

        return redirect()->route('pembudidaya.pendampingan.jadwal')->with('success', 'Pengajuan Pendampingan Terkirim!');
    }

    public function jadwalFeedback() {
        $id_user = Auth::user()->id_user;

        // Jadwal Mendatang (Status: dijadwalkan)
        // Logikanya: Tanggal jadwal belum lewat
        $jadwal_mendatang = PengajuanPendampingan::where('id_user', $id_user)
            ->where('status', 'dijadwalkan')
            ->orderBy('jadwal_pendampingan', 'asc')
            ->get();

        // List Feedback (Status: selesai, atau bisa juga 'dijadwalkan' tapi tanggal sudah lewat)
        // Disini kita ambil semua history untuk ditampilkan di tab feedback
        $list_feedback = PengajuanPendampingan::where('id_user', $id_user)
            ->whereIn('status', ['selesai', 'dijadwalkan'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pembudidaya.pendampingan-jadwal', compact('jadwal_mendatang', 'list_feedback'));
    }

    public function storeFeedback(Request $request)
{
    $request->validate([
        'id_pendampingan' => 'required|exists:pengajuan_pendampingans,id',
        'rating' => 'required|integer|min:1|max:5',
        'ulasan_feedback' => 'required|string|min:5',
    ]);

    $pendampingan = PengajuanPendampingan::findOrFail($request->id_pendampingan);
    
    // Simpan feedback
    $pendampingan->update([
        'rating' => $request->rating,
        'ulasan_feedback' => $request->ulasan_feedback,
        'updated_at' => now()
    ]);

    // Kembali dengan pesan sukses untuk memicu modal ungu/hijau
    return redirect()->back()->with('success_crud', 'Terima kasih! Feedback Anda telah kami terima.');
}

    public function marketplaceIndex(Request $request)
    {
        $products = MarketplaceProduct::query()
            ->with('komoditas')
            ->where('is_active', true)
            ->when($request->filled('kategori'), function ($query) use ($request) {
                $query->where('kategori', $request->kategori);
            })
            ->orderBy('nama_produk')
            ->paginate(12);

        return view('pembudidaya.marketplace-index', compact('products'));
    }

    public function marketplaceCart()
    {
        $cart = MarketplaceCart::firstOrCreate([
            'user_id' => Auth::user()->id_user,
        ])->load('items.product');

        $subtotal = $cart->items->sum(function ($item) {
            return $item->qty * $item->harga_saat_dimasukkan;
        });

        return view('pembudidaya.marketplace-cart', compact('cart', 'subtotal'));
    }

    public function marketplaceAddToCart(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:marketplace_products,id',
            'qty' => 'required|integer|min:1',
        ]);

        $product = MarketplaceProduct::findOrFail($validated['product_id']);
        if (!$product->is_active) {
            return back()->with('error', 'Produk tidak aktif.');
        }

        $cart = MarketplaceCart::firstOrCreate(['user_id' => Auth::user()->id_user]);
        $item = $cart->items()->where('product_id', $product->id)->first();
        $newQty = ($item->qty ?? 0) + $validated['qty'];

        if ($product->stok < $newQty) {
            return back()->with('error', 'Stok tidak mencukupi.');
        }

        $cart->items()->updateOrCreate(
            ['product_id' => $product->id],
            [
                'qty' => $newQty,
                'harga_saat_dimasukkan' => $product->harga,
            ]
        );

        return redirect()->route('pembudidaya.marketplace.cart')->with('success', 'Produk masuk ke keranjang.');
    }

    public function marketplaceUpdateCartItem(Request $request, $itemId)
    {
        $validated = $request->validate([
            'qty' => 'required|integer|min:1',
        ]);

        $cart = MarketplaceCart::where('user_id', Auth::user()->id_user)->firstOrFail();
        $item = $cart->items()->where('id', $itemId)->firstOrFail();

        if ($item->product->stok < $validated['qty']) {
            return back()->with('error', 'Stok tidak mencukupi.');
        }

        $item->update([
            'qty' => $validated['qty'],
            'harga_saat_dimasukkan' => $item->product->harga,
        ]);

        return back()->with('success', 'Keranjang diperbarui.');
    }

    public function marketplaceRemoveCartItem($itemId)
    {
        $cart = MarketplaceCart::where('user_id', Auth::user()->id_user)->firstOrFail();
        $item = $cart->items()->where('id', $itemId)->firstOrFail();
        $item->delete();

        return back()->with('success', 'Item dihapus dari keranjang.');
    }

    public function marketplaceCheckout(Request $request)
    {
        $validated = $request->validate([
            'alamat_pengiriman' => 'required|string',
            'nama_penerima' => 'required|string|max:255',
            'nomor_hp_penerima' => 'required|string|max:50',
        ]);

        $cart = MarketplaceCart::where('user_id', Auth::user()->id_user)->with('items.product')->firstOrFail();
        if ($cart->items->isEmpty()) {
            return back()->with('error', 'Keranjang kosong.');
        }

        $subtotal = 0;
        foreach ($cart->items as $item) {
            if ($item->product->stok < $item->qty) {
                return back()->with('error', "Stok {$item->product->nama_produk} tidak cukup.");
            }
            $subtotal += $item->qty * $item->product->harga;
        }

        DB::transaction(function () use ($cart, $validated, $subtotal) {
            $order = MarketplaceOrder::create([
                'order_code' => 'MP-' . now()->format('YmdHis') . '-' . rand(100, 999),
                'user_id' => Auth::user()->id_user,
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
        });

        return redirect()->route('pembudidaya.marketplace.orders')->with('success', 'Checkout berhasil, lanjut ke pembayaran.');
    }

    public function marketplaceOrders()
    {
        $orders = MarketplaceOrder::where('user_id', Auth::user()->id_user)
            ->with(['items', 'payments'])
            ->latest()
            ->paginate(10);

        // Auto-heal status order dari log pembayaran jika webhook sempat telat.
        foreach ($orders as $order) {
            $latestPayment = $order->payments->sortByDesc('id')->first();
            if (!$latestPayment) {
                continue;
            }

            if (in_array($latestPayment->transaction_status, ['settlement', 'capture'], true)
                && $order->status_pembayaran !== 'dibayar') {
                $order->update([
                    'status_pembayaran' => 'dibayar',
                    'status_pesanan' => 'diproses',
                    'payment_reference' => $latestPayment->transaction_id,
                ]);
            }
        }

        $midtransClientKey = config('services.midtrans.client_key');
        $midtransIsProduction = (bool) config('services.midtrans.is_production', false);

        return view('pembudidaya.marketplace-orders', compact('orders', 'midtransClientKey', 'midtransIsProduction'));
    }

    public function marketplaceSnapToken($id)
    {
        $order = MarketplaceOrder::where('user_id', Auth::user()->id_user)
            ->with('items')
            ->findOrFail($id);

        if ($order->status_pembayaran === 'dibayar') {
            return response()->json(['message' => 'Pesanan ini sudah dibayar.'], 422);
        }

        $serverKey = config('services.midtrans.server_key');
        if (!$serverKey) {
            return response()->json(['message' => 'MIDTRANS_SERVER_KEY belum dikonfigurasi.'], 500);
        }

        $isProduction = (bool) config('services.midtrans.is_production', false);
        $baseUrl = $isProduction
            ? 'https://app.midtrans.com'
            : 'https://app.sandbox.midtrans.com';

        $itemDetails = $order->items->map(function ($item) {
            return [
                'id' => (string) $item->product_id,
                'price' => (int) $item->harga,
                'quantity' => (int) $item->qty,
                'name' => $item->nama_produk,
            ];
        })->values()->toArray();



        $payload = [
            'transaction_details' => [
                'order_id' => $order->order_code,
                'gross_amount' => (int) $order->total,
            ],
            'item_details' => $itemDetails,
            'customer_details' => [
                'first_name' => Auth::user()->nama_lengkap ?? 'Pembudidaya',
                'email' => Auth::user()->email,
                'phone' => Auth::user()->nomor_hp,
            ],
            'callbacks' => [
                'finish' => route('pembudidaya.marketplace.orders'),
            ],
        ];

        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->post($baseUrl . '/snap/v1/transactions', $payload);

        if (!$response->successful()) {
            Log::error('Midtrans snap token gagal.', [
                'order_code' => $order->order_code,
                'response_status' => $response->status(),
                'response_body' => $response->body(),
            ]);

            return response()->json(['message' => 'Gagal membuat Snap token dari Midtrans.'], 500);
        }

        $snap = $response->json();

        return response()->json([
            'token' => $snap['token'] ?? null,
            'redirect_url' => $snap['redirect_url'] ?? null,
        ]);
    }
    public function marketplaceCompleteOrder($id)
    {
        $order = MarketplaceOrder::where('user_id', Auth::user()->id_user)->findOrFail($id);

        if ($order->status_pembayaran !== 'dibayar') {
            return back()->with('error', 'Pesanan belum dibayar.');
        }

        $order->update([
            'status_pesanan' => 'selesai'
        ]);

        return back()->with('success', 'Pesanan telah diterima.');
    }
}