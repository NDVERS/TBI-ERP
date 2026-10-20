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
}
