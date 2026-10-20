<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'po_date',
        'supplier_id',
        'vessel_name',
        'material_id',
        'specification',
        'qty_ordered',
        'unit_price',
        'subtotal',
        'ppn_amount',
        'grand_total',
    ];

    protected function casts(): array
    {
        return [
            'po_date' => 'date',
            'qty_ordered' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'ppn_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function vesselShipments(): HasMany
    {
        return $this->hasMany(VesselShipment::class);
    }
}
