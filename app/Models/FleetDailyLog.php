<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleetDailyLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'fleet_id',
        'log_date',
        'start_hm',
        'end_hm',
        'total_hm',
        'start_km',
        'end_km',
        'total_km',
        'fuel_liters',
        'engine_oil_liters',
        'hydraulic_oil_liters',
        'driver_operator_name',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'log_date' => 'date',
            'start_hm' => 'decimal:2',
            'end_hm' => 'decimal:2',
            'total_hm' => 'decimal:2',
            'start_km' => 'decimal:2',
            'end_km' => 'decimal:2',
            'total_km' => 'decimal:2',
            'fuel_liters' => 'decimal:2',
            'engine_oil_liters' => 'decimal:2',
            'hydraulic_oil_liters' => 'decimal:2',
        ];
    }

    public function fleet(): BelongsTo
    {
        return $this->belongsTo(Fleet::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
