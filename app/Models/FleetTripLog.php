<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleetTripLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'location_km',
        'trip_distance_km',
        'duration_hours',
        'total_fuel_liter',
        'est_tire_cost',
        'est_sparepart_cost',
        'grand_total',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'location_km' => 'decimal:2',
            'trip_distance_km' => 'decimal:2',
            'duration_hours' => 'decimal:2',
            'total_fuel_liter' => 'decimal:2',
            'est_tire_cost' => 'decimal:2',
            'est_sparepart_cost' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }
}
