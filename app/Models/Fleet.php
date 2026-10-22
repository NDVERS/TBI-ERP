<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fleet extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_code',
        'name',
        'type',
        'license_plate',
        'model_specification',
        'status',
        'current_hm',
        'current_km',
        'last_service_hm',
        'last_service_date',
        'service_interval_hm',
        'service_interval_days',
    ];

    protected function casts(): array
    {
        return [
            'current_hm' => 'decimal:2',
            'current_km' => 'decimal:2',
            'last_service_hm' => 'decimal:2',
            'last_service_date' => 'date',
            'service_interval_hm' => 'decimal:2',
            'service_interval_days' => 'integer',
        ];
    }

    public function dailyLogs(): HasMany
    {
        return $this->hasMany(FleetDailyLog::class);
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(FleetMaintenanceRecord::class);
    }

    public function getNextServiceHmAttribute(): float
    {
        return (float) $this->last_service_hm + (float) $this->service_interval_hm;
    }

    public function getRemainingHmAttribute(): float
    {
        $target = $this->next_service_hm;
        $rem = $target - (float) $this->current_hm;

        return max(0, round($rem, 2));
    }

    public function getHmProgressPercentageAttribute(): float
    {
        $interval = (float) $this->service_interval_hm ?: 500.0;
        $used = (float) $this->current_hm - (float) $this->last_service_hm;
        $pct = ($used / $interval) * 100.0;

        return min(100.0, max(0.0, round($pct, 1)));
    }

    public function getRemainingDaysAttribute(): ?int
    {
        if (! $this->service_interval_days || ! $this->last_service_date) {
            return null;
        }

        $dueDate = Carbon::parse($this->last_service_date)->addDays($this->service_interval_days);
        $diff = now()->diffInDays($dueDate, false);

        return (int) $diff;
    }

    public function getIsServiceDueAttribute(): bool
    {
        if ($this->remaining_hm <= 0) {
            return true;
        }

        if ($this->remaining_days !== null && $this->remaining_days <= 0) {
            return true;
        }

        return false;
    }

    public function getServiceStatusLabelAttribute(): array
    {
        if ($this->is_service_due) {
            return [
                'status' => 'Wajib Servis',
                'variant' => 'red',
                'badge' => '🔴 Wajib Servis',
                'is_urgent' => true,
            ];
        }

        if ($this->remaining_hm <= 50 || ($this->remaining_days !== null && $this->remaining_days <= 5)) {
            return [
                'status' => 'Mendekati Servis',
                'variant' => 'amber',
                'badge' => '🟡 Mendekati Servis',
                'is_urgent' => false,
            ];
        }

        return [
            'status' => 'Normal / Siap Operasi',
            'variant' => 'emerald',
            'badge' => '🟢 Siap Operasi',
            'is_urgent' => false,
        ];
    }

    public function getTrafficLightColorAttribute(): string
    {
        if ($this->is_service_due) {
            return 'red';
        }
        if ($this->remaining_hm <= 20 || ($this->remaining_days !== null && $this->remaining_days <= 3)) {
            return 'orange';
        }
        if ($this->remaining_hm <= 50 || ($this->remaining_days !== null && $this->remaining_days <= 7)) {
            return 'amber';
        }

        return 'emerald';
    }

    public function getCountdownPillAttribute(): array
    {
        if ($this->is_service_due) {
            return [
                'text' => '🚨 OVERDUE — WAJIB SERVIS',
                'bg' => 'bg-red-500 text-white animate-pulse',
                'bar_color' => 'bg-red-500',
                'dot' => 'bg-red-500',
            ];
        }
        if ($this->remaining_hm <= 20) {
            return [
                'text' => '🟠 Sisa '.number_format($this->remaining_hm, 1).' Jam (Segera Servis)',
                'bg' => 'bg-orange-100 text-orange-800 border border-orange-300',
                'bar_color' => 'bg-orange-500',
                'dot' => 'bg-orange-500',
            ];
        }
        if ($this->remaining_hm <= 50) {
            return [
                'text' => '🟡 Sisa '.number_format($this->remaining_hm, 1).' Jam (Jadwalkan Servis)',
                'bg' => 'bg-amber-100 text-amber-800 border border-amber-300',
                'bar_color' => 'bg-amber-500',
                'dot' => 'bg-amber-500',
            ];
        }

        return [
            'text' => '🟢 Sisa '.number_format($this->remaining_hm, 1).' Jam (Kondisi Prima)',
            'bg' => 'bg-emerald-50 text-emerald-800 border border-emerald-200',
            'bar_color' => 'bg-emerald-500',
            'dot' => 'bg-emerald-500',
        ];
    }
}
