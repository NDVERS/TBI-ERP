<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionDocket extends Model
{
    use HasFactory;

    protected $fillable = [
        'docket_no',
        'production_date',
        'sales_order_id',
        'truck_no',
        'customer_name',
        'address',
        'mutu_code',
        'time_load',
        'volume_m3',
        'daily_cumulative_m3',
        'load_time_diff',
        'silo1_tar',
        'silo1_act',
        'silo2_tar',
        'silo2_act',
        'pasir_palu_tar',
        'pasir_palu_act',
        'pasir_lokal_tar',
        'pasir_lokal_act',
        'split_1_2_tar',
        'split_1_2_act',
        'split_2_3_tar',
        'split_2_3_act',
        'water_tar',
        'water_act',
        'admixture_tar',
        'admixture_act',
    ];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'volume_m3' => 'decimal:2',
            'daily_cumulative_m3' => 'decimal:2',
            'silo1_tar' => 'decimal:2',
            'silo1_act' => 'decimal:2',
            'silo2_tar' => 'decimal:2',
            'silo2_act' => 'decimal:2',
            'pasir_palu_tar' => 'decimal:2',
            'pasir_palu_act' => 'decimal:2',
            'pasir_lokal_tar' => 'decimal:2',
            'pasir_lokal_act' => 'decimal:2',
            'split_1_2_tar' => 'decimal:2',
            'split_1_2_act' => 'decimal:2',
            'split_2_3_tar' => 'decimal:2',
            'split_2_3_act' => 'decimal:2',
            'water_tar' => 'decimal:2',
            'water_act' => 'decimal:2',
            'admixture_tar' => 'decimal:2',
            'admixture_act' => 'decimal:2',
        ];
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }
}
