<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialReceive extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'receive_date',
        'supplier_id',
        'material_id',
        'vehicle_no',
        'ri_no',
        'time_in',
        'volume_m3',
        'density_used',
        'total_weight_kg',
    ];

    protected function casts(): array
    {
        return [
            'receive_date' => 'date',
            'volume_m3' => 'decimal:2',
            'density_used' => 'decimal:2',
            'total_weight_kg' => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
