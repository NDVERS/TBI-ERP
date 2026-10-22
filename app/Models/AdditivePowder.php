<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdditivePowder extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'recipe_quantity_kg',
        'standard_percentage',
        'stock_kg',
        'unit',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'recipe_quantity_kg' => 'decimal:2',
            'standard_percentage' => 'decimal:2',
            'stock_kg' => 'decimal:2',
        ];
    }

    public function isLowStock(): bool
    {
        return (float) $this->stock_kg < (float) $this->recipe_quantity_kg;
    }

    public function isCriticalStock(): bool
    {
        return (float) $this->stock_kg <= 0;
    }

    /**
     * @return array{label: string, variant: string, icon: string, is_low: bool}
     */
    public function getStockStatusAttribute(): array
    {
        $stock = (float) $this->stock_kg;
        $recipe = (float) $this->recipe_quantity_kg;

        if ($stock <= 0) {
            return [
                'label' => 'Habis',
                'variant' => 'red',
                'icon' => '⛔',
                'is_low' => true,
            ];
        }

        if ($stock < $recipe) {
            return [
                'label' => '< 1 Batch',
                'variant' => 'amber',
                'icon' => '⚠️',
                'is_low' => true,
            ];
        }

        if ($stock < ($recipe * 1.5)) {
            return [
                'label' => 'Mendekati Minimum',
                'variant' => 'amber',
                'icon' => '⚡',
                'is_low' => true,
            ];
        }

        return [
            'label' => 'Aman',
            'variant' => 'emerald',
            'icon' => '✓',
            'is_low' => false,
        ];
    }
}
