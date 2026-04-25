<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'nama_produk',
        'kategori',
        'komoditas_id',
        'deskripsi',
        'gambar_produk',
        'harga',
        'stok',
        'is_active',
        'lokasi',
        'estimasi_pengiriman',
        'spesifikasi',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function komoditas()
    {
        return $this->belongsTo(Komoditas::class, 'komoditas_id');
    }

    public function reviews()
    {
        return $this->hasMany(MarketplaceProductReview::class, 'product_id');
    }

    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?: 0;
    }

    public function getTotalReviewsAttribute()
    {
        return $this->reviews()->count();
    }
}
