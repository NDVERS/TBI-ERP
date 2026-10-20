<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobMix extends Model
{
    use HasFactory;

    protected $fillable = [
        'mutu_code',
        'semen_kg',
        'pasir_palu_kg',
        'pasir_lokal_kg',
        'split_1_2_kg',
        'split_2_3_kg',
        'additive_liter',
        'waste_factor',
    ];

    protected function casts(): array
    {
        return [
            'semen_kg' => 'decimal:2',
            'pasir_palu_kg' => 'decimal:2',
            'pasir_lokal_kg' => 'decimal:2',
            'split_1_2_kg' => 'decimal:2',
            'split_2_3_kg' => 'decimal:2',
            'additive_liter' => 'decimal:2',
            'waste_factor' => 'decimal:4',
        ];
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    /**
     * Estimasi kebutuhan air per 1 m3 adukan ready-mix (w/c ratio approx 0.48, berkisar 160-190 L).
     */
    public function getWaterLiterAttribute(): float
    {
        if (isset($this->attributes['water_liter']) && (float) $this->attributes['water_liter'] > 0) {
            return (float) $this->attributes['water_liter'];
        }

        $semen = (float) $this->semen_kg;
        if ($semen > 0) {
            return round(min(200.0, max(140.0, $semen * 0.48)), 2);
        }

        return 180.00;
    }

    /**
     * Kategori mutu beton (Seri K / Seri FC & FS).
     */
    public function getCategoryNameAttribute(): string
    {
        return str_starts_with($this->mutu_code, 'K')
            ? 'Seri K'
            : 'Seri FC-FS';
    }
}
