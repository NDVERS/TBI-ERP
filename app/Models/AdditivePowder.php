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
}
