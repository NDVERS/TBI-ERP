<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\LabConversion;
use App\Models\MaterialReceive;
use App\Models\SalesOrder;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditObserver
{
    public function created(Model $model): void
    {
        $name = class_basename($model);
        $desc = "Membuat data {$name} baru (ID: {$model->getKey()})";

        if ($model instanceof SalesOrder) {
            $desc = "Membuat Sales Order baru ({$model->so_number}) volume {$model->volume_m3} m³";
        } elseif ($model instanceof MaterialReceive) {
            $desc = "Mencatat penerimaan material truk (RI: {$model->ri_no}) seberat {$model->total_weight_kg} Kg";
        } elseif ($model instanceof StockOpname) {
            $desc = "Menginput hasil stock opname fisik timbunan ({$model->method})";
        } elseif ($model instanceof LabConversion) {
            $desc = "Mencatat konversi laboratorium {$model->conversion_no}: {$model->additive_used_kg} Kg Additive -> {$model->sbtjm2_produced_liter} L SBTJM2";
        } elseif ($model instanceof User) {
            $desc = "Mendaftarkan pengguna baru: {$model->name} ({$model->email}) role: {$model->role}";
        }

        ActivityLog::create([
            'user_id' => Auth::id() ?? (isset($model->created_by) ? $model->created_by : null),
            'action' => 'created',
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'description' => $desc,
            'ip_address' => request()->ip(),
        ]);
    }

    public function updated(Model $model): void
    {
        $name = class_basename($model);
        $desc = "Memperbarui data {$name} (ID: {$model->getKey()})";

        if ($model instanceof User) {
            $desc = "Memperbarui profil/role pengguna: {$model->name} (Role: {$model->role})";
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'updated',
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'description' => $desc,
            'ip_address' => request()->ip(),
        ]);
    }

    public function deleted(Model $model): void
    {
        $name = class_basename($model);
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'deleted',
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'description' => "Menghapus data {$name} (ID: {$model->getKey()})",
            'ip_address' => request()->ip(),
        ]);
    }
}
