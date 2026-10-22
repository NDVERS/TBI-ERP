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
        'delivery_status',
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
        'created_by',
    ];

    public const STATUS_LOADING = 'loading';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_ON_SITE = 'on_site';

    public const STATUS_COMPLETED = 'completed';

    /**
     * @return array<string, array{label: string, badge: string, icon: string, short: string}>
     */
    public static function deliveryStatuses(): array
    {
        return [
            self::STATUS_LOADING => [
                'label' => 'Antri Muat (Plant)',
                'badge' => 'amber',
                'icon' => '🔄',
                'short' => 'Loading',
            ],
            self::STATUS_IN_TRANSIT => [
                'label' => 'Dalam Perjalanan',
                'badge' => 'blue',
                'icon' => '🚚',
                'short' => 'In Transit',
            ],
            self::STATUS_ON_SITE => [
                'label' => 'Di Lokasi Proyek',
                'badge' => 'purple',
                'icon' => '🏗️',
                'short' => 'On Site',
            ],
            self::STATUS_COMPLETED => [
                'label' => 'Selesai Bongkar',
                'badge' => 'emerald',
                'icon' => '✅',
                'short' => 'Selesai',
            ],
        ];
    }

    /**
     * @return array{label: string, badge: string, icon: string, short: string}
     */
    public function getDeliveryStatusInfoAttribute(): array
    {
        $statuses = self::deliveryStatuses();

        return $statuses[$this->delivery_status] ?? [
            'label' => ucfirst($this->delivery_status ?? 'completed'),
            'badge' => 'zinc',
            'icon' => 'ℹ️',
            'short' => ucfirst($this->delivery_status ?? 'completed'),
        ];
    }

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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
