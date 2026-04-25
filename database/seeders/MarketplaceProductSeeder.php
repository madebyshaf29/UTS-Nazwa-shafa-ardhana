<?php

namespace Database\Seeders;

use App\Models\MarketplaceProduct;
use Illuminate\Database\Seeder;

class MarketplaceProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'sku' => 'BIBIT-NILA-001',
                'nama_produk' => 'Bibit Ikan Nila Premium',
                'kategori' => 'bibit',
                'deskripsi' => 'Bibit nila sehat ukuran 5-7 cm.',
                'harga' => 55000,
                'stok' => 120,
                'is_active' => true,
            ],
            [
                'sku' => 'PAKAN-LELE-001',
                'nama_produk' => 'Pakan Lele Protein 30%',
                'kategori' => 'pakan',
                'deskripsi' => 'Pakan pelet untuk pembesaran lele.',
                'harga' => 275000,
                'stok' => 75,
                'is_active' => true,
            ],
            [
                'sku' => 'ALAT-AERATOR-001',
                'nama_produk' => 'Aerator Kolam 60W',
                'kategori' => 'alat',
                'deskripsi' => 'Aerator untuk menjaga oksigen kolam budidaya.',
                'harga' => 320000,
                'stok' => 30,
                'is_active' => true,
            ],
            [
                'sku' => 'BIBIT-LELE-001',
                'nama_produk' => 'Bibit Lele Sangkuriang',
                'kategori' => 'bibit',
                'deskripsi' => 'Bibit lele unggul untuk pembesaran cepat.',
                'harga' => 45000,
                'stok' => 200,
                'is_active' => true,
            ],
            [
                'sku' => 'BIBIT-GURAME-001',
                'nama_produk' => 'Bibit Gurame Ukuran 4-6 cm',
                'kategori' => 'bibit',
                'deskripsi' => 'Bibit gurame sehat cocok untuk kolam tanah.',
                'harga' => 78000,
                'stok' => 90,
                'is_active' => true,
            ],
            [
                'sku' => 'PAKAN-NILA-001',
                'nama_produk' => 'Pakan Nila Pertumbuhan',
                'kategori' => 'pakan',
                'deskripsi' => 'Pelet apung untuk pertumbuhan nila fase pembesaran.',
                'harga' => 255000,
                'stok' => 60,
                'is_active' => true,
            ],
            [
                'sku' => 'PAKAN-UDANG-001',
                'nama_produk' => 'Pakan Udang Intensif',
                'kategori' => 'pakan',
                'deskripsi' => 'Pakan khusus udang untuk sistem intensif.',
                'harga' => 365000,
                'stok' => 45,
                'is_active' => true,
            ],
            [
                'sku' => 'ALAT-JARING-001',
                'nama_produk' => 'Jaring Panen Kolam 5x10',
                'kategori' => 'alat',
                'deskripsi' => 'Jaring panen serbaguna dengan bahan kuat.',
                'harga' => 175000,
                'stok' => 55,
                'is_active' => true,
            ],
            [
                'sku' => 'ALAT-PH-001',
                'nama_produk' => 'Meter pH Air Digital',
                'kategori' => 'alat',
                'deskripsi' => 'Alat ukur pH untuk memantau kualitas air kolam.',
                'harga' => 140000,
                'stok' => 70,
                'is_active' => true,
            ],
            [
                'sku' => 'ALAT-KINCIR-001',
                'nama_produk' => 'Kincir Air Tambak Mini',
                'kategori' => 'alat',
                'deskripsi' => 'Kincir air mini untuk meningkatkan sirkulasi oksigen.',
                'harga' => 1250000,
                'stok' => 18,
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            MarketplaceProduct::updateOrCreate(
                ['sku' => $product['sku']],
                $product
            );
        }
    }
}
