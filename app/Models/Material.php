<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit',
        'density_kg_m3',
        'initial_stock_kg',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'density_kg_m3' => 'decimal:2',
            'initial_stock_kg' => 'decimal:2',
        ];
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function materialReceives(): HasMany
    {
        return $this->hasMany(MaterialReceive::class);
    }

    public function stockOpnames(): HasMany
    {
        return $this->hasMany(StockOpname::class);
    }
}
