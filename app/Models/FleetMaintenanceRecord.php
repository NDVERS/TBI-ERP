<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleetMaintenanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'fleet_id',
        'service_date',
        'service_type',
        'hm_at_service',
        'mechanic_name',
        'description',
        'parts_replaced',
        'cost',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'hm_at_service' => 'decimal:2',
            'cost' => 'decimal:2',
        ];
    }

    public function fleet(): BelongsTo
    {
        return $this->belongsTo(Fleet::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
