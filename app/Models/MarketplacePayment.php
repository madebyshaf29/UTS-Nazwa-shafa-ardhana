<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplacePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'provider',
        'transaction_id',
        'order_code',
        'payment_type',
        'transaction_status',
        'fraud_status',
        'status_code',
        'gross_amount',
        'signature_key',
        'raw_payload',
        'paid_at',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'paid_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(MarketplaceOrder::class, 'order_id');
    }
}
