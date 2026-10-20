<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpname extends Model
{
    use HasFactory;

    protected $fillable = [
        'opname_date',
        'material_id',
        'method',
        'dimensions_json',
        'calculated_volume_m3',
        'calculated_weight_kg',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'opname_date' => 'date',
            'dimensions_json' => 'array',
            'calculated_volume_m3' => 'decimal:4',
            'calculated_weight_kg' => 'decimal:2',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
