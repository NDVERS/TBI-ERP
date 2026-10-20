<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VesselShipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'departure_date',
        'arrival_date',
        'purchase_order_id',
        'load_volume',
        'unload_volume',
        'volume_diff',
        'shrinkage_percent',
        'lab_result',
        'document_path',
    ];

    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
            'arrival_date' => 'date',
            'load_volume' => 'decimal:2',
            'unload_volume' => 'decimal:2',
            'volume_diff' => 'decimal:2',
            'shrinkage_percent' => 'decimal:4',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
