<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LubricantTransfer extends Model
{
    protected $fillable = [
        'transfer_date',
        'transfer_type',
        'oil_type',
        'liters',
        'source_plant',
        'destination_plant',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
            'liters' => 'decimal:2',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
