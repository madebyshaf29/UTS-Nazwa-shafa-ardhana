<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_code',
        'user_id',
        'subtotal',
        'ongkir',
        'total',
        'status_pembayaran',
        'status_pesanan',
        'payment_reference',
        'shipping_payload',
    ];

    protected $casts = [
        'shipping_payload' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(MarketplaceOrderItem::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id_user');
    }

    public function payments()
    {
        return $this->hasMany(MarketplacePayment::class, 'order_id');
    }
}
