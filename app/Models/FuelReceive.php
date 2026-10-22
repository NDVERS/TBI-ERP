<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelReceive extends Model
{
    protected $fillable = [
        'receive_date',
        'supplier_name',
        'ri_do_number',
        'vehicle_no',
        'volume_liters',
        'initial_sounding_cm',
        'final_sounding_cm',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'receive_date' => 'date',
            'volume_liters' => 'decimal:2',
            'initial_sounding_cm' => 'decimal:2',
            'final_sounding_cm' => 'decimal:2',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
