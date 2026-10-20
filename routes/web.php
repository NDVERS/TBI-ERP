<?php

use App\Http\Controllers\AuthController;
use App\Livewire\ActivityLogViewer;
use App\Livewire\ExecutiveDashboard;
use App\Livewire\MaterialReceiveForm;
use App\Livewire\SalesOrderForm;
use App\Livewire\StockOpnameForm;
use App\Livewire\StockReconciliation;
use App\Livewire\UserManagement;
use App\Livewire\VesselShipmentForm;
use App\Models\Customer;
use App\Models\JobMix;
use App\Models\Material;
use App\Models\Supplier;
use Illuminate\Support\Facades\Route;

// Public & Landing
Route::get('/', function () {
    $materials = Material::all();
    $supplierCount = Supplier::count();
    $customerCount = Customer::count();
    $jobMixCount = JobMix::count();

    return view('welcome', compact('materials', 'supplierCount', 'customerCount', 'jobMixCount'));
})->name('home');

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Operational Modules Protected by RBAC
Route::middleware('auth')->group(function () {
    // Executive Dashboard (Admin & Manajemen)
    Route::get('/dashboard', ExecutiveDashboard::class)
        ->middleware('role:admin,manajemen')
        ->name('dashboard');

    // Sales Orders (Admin, Kasir Logistik)
    Route::get('/sales-orders', SalesOrderForm::class)
        ->middleware('role:admin,kasir_logistik')
        ->name('sales-orders.index');

    // Stock Opname Lapangan di Tablet (Surveyor Opname, Admin)
    Route::get('/stock-opnames', StockOpnameForm::class)
        ->middleware('role:admin,surveyor_opname')
        ->name('stock-opnames.index');

    // Penerimaan Truk Inbound (Admin, Petugas Gudang)
    Route::get('/material-receives', MaterialReceiveForm::class)
        ->middleware('role:admin,petugas_gudang')
        ->name('material-receives.index');

    // Pengiriman Kapal & Tongkang (Admin, Petugas Gudang)
    Route::get('/vessel-shipments', VesselShipmentForm::class)
        ->middleware('role:admin,petugas_gudang')
        ->name('vessel-shipments.index');

    // Rekonsiliasi Stok Material (Khusus Admin)
    Route::get('/reconciliation', StockReconciliation::class)
        ->middleware('role:admin')
        ->name('reconciliation.index');

    // Manajemen Pengguna (Khusus Admin)
    Route::get('/users', UserManagement::class)
        ->middleware('role:admin')
        ->name('users.index');

    // Log Aktivitas & Audit Trail (Khusus Admin)
    Route::get('/audit-logs', ActivityLogViewer::class)
        ->middleware('role:admin')
        ->name('audit-logs.index');
});
