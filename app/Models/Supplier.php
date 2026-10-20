<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'material_type',
        'lead_time_days',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'lead_time_days' => 'integer',
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
}
