<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabConversion extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversion_no',
        'conversion_date',
        'additive_used_kg',
        'sbtjm2_produced_liter',
        'notes',
        'material_receive_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'conversion_date' => 'date',
            'additive_used_kg' => 'decimal:2',
            'sbtjm2_produced_liter' => 'decimal:2',
        ];
    }

    public function materialReceive(): BelongsTo
    {
        return $this->belongsTo(MaterialReceive::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
