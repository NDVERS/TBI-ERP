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
        'loading_jetty',
        'unloading_jetty',
        'est_departure_date',
        'est_arrival_date',
        'actual_departure_date',
        'actual_arrival_date',
        'status',
        'material_id',
        'material_type_input',
        'specification',
        'qty_ordered',
        'unit',
        'unit_price',
        'subtotal',
        'ppn_amount',
        'grand_total',
    ];

    protected function casts(): array
    {
        return [
            'po_date' => 'date',
            'est_departure_date' => 'date',
            'est_arrival_date' => 'date',
            'actual_departure_date' => 'date',
            'actual_arrival_date' => 'date',
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

    public function materialReceives(): HasMany
    {
        return $this->hasMany(MaterialReceive::class);
    }
}
