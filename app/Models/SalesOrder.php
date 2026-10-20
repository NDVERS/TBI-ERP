<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'so_number',
        'order_date',
        'customer_id',
        'location',
        'job_mix_id',
        'volume_m3',
        'distance_km',
        'est_semen_kg',
        'est_pasir_palu_kg',
        'est_pasir_lokal_kg',
        'est_split_1_2_kg',
        'est_split_2_3_kg',
        'est_additive_liter',
        'est_solar_liter',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'volume_m3' => 'decimal:2',
            'distance_km' => 'decimal:2',
            'est_semen_kg' => 'decimal:2',
            'est_pasir_palu_kg' => 'decimal:2',
            'est_pasir_lokal_kg' => 'decimal:2',
            'est_split_1_2_kg' => 'decimal:2',
            'est_split_2_3_kg' => 'decimal:2',
            'est_additive_liter' => 'decimal:2',
            'est_solar_liter' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function jobMix(): BelongsTo
    {
        return $this->belongsTo(JobMix::class);
    }

    public function productionDockets(): HasMany
    {
        return $this->hasMany(ProductionDocket::class);
    }

    public function fleetTripLogs(): HasMany
    {
        return $this->hasMany(FleetTripLog::class);
    }
}
