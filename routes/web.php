<?php

use App\Http\Controllers\AuthController;
use App\Livewire\ActivityLogViewer;
use App\Livewire\ExecutiveDashboard;
use App\Livewire\MasterJobMixIndex;
use App\Livewire\MaterialReceiveForm;
use App\Livewire\ProductionDocketForm;
use App\Livewire\PurchaseOrderForm;
use App\Livewire\SalesOrderForm;
use App\Livewire\StockOpnameForm;
use App\Livewire\StockReconciliation;
use App\Livewire\UserManagement;
use App\Livewire\VesselShipmentForm;
use App\Models\Customer;
use App\Models\JobMix;
use App\Models\Material;
use App\Models\Supplier;
use App\Services\StockReconciliationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Public & Landing
Route::get('/', function (StockReconciliationService $reconciliationService) {
    $materials = Material::all();
    $supplierCount = Supplier::count();
    $customerCount = Customer::count();
    $jobMixCount = JobMix::count();

    $isAdmin = Auth::check() && Auth::user()->role === 'admin';

    $safeMaterials = $materials->map(function ($m) use ($isAdmin, $reconciliationService) {
        $recon = $reconciliationService->reconcileMaterial($m);
        $theoreticalStock = (float) $recon['stok_teoritis'];
        $m->theoretical_stock_kg = $theoreticalStock;

        return [
            'id' => $m->id,
            'name' => $m->name,
            'unit' => $m->unit,
            'theoretical_stock_kg' => $theoreticalStock,
            'theoretical_stock_formatted' => number_format($theoreticalStock, 2, ',', '.'),
            'density_formatted' => $isAdmin && $m->density_kg_m3 ? number_format((float) $m->density_kg_m3, 2, ',', '.') : null,
            'is_density_locked' => ! $isAdmin,
            'notes' => $m->notes ?? '-',
            'has_stock' => $theoreticalStock > 0,
        ];
    });

    return view('welcome', compact('materials', 'safeMaterials', 'supplierCount', 'customerCount', 'jobMixCount', 'isAdmin'));
})->name('home');

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Operational Modules Protected by RBAC
Route::middleware('auth')->group(function () {
    // Executive Dashboard (Admin, Manajemen, Demo)
    Route::get('/dashboard', ExecutiveDashboard::class)
        ->middleware('role:admin,manajemen,demo')
        ->name('dashboard');

    // Sales Orders (Admin, Kasir Logistik, Demo)
    Route::get('/sales-orders', SalesOrderForm::class)
        ->middleware('role:admin,kasir_logistik,demo')
        ->name('sales-orders.index');

    // Purchase Orders (Admin, Kasir Logistik, Demo)
    Route::get('/purchase-orders', PurchaseOrderForm::class)
        ->middleware('role:admin,kasir_logistik,demo')
        ->name('purchase-orders.index');

    // Stock Opname Lapangan di Tablet (Surveyor Opname, Admin, Demo)
    Route::get('/stock-opnames', StockOpnameForm::class)
        ->middleware('role:admin,surveyor_opname,demo')
        ->name('stock-opnames.index');

    // Penerimaan Truk Inbound (Admin, Petugas Gudang, Demo)
    Route::get('/material-receives', MaterialReceiveForm::class)
        ->middleware('role:admin,petugas_gudang,demo')
        ->name('material-receives.index');

    // Pengiriman Kapal & Tongkang (Admin, Petugas Gudang, Demo)
    Route::get('/vessel-shipments', VesselShipmentForm::class)
        ->middleware('role:admin,petugas_gudang,demo')
        ->name('vessel-shipments.index');

    // Produksi Harian (Admin, Operator Produksi, Demo)
    Route::get('/production-dockets', ProductionDocketForm::class)
        ->middleware('role:admin,operator_produksi,demo')
        ->name('production-dockets.index');

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

    // Master Job Mix (Resep Adukan Beton - Khusus Admin)
    Route::get('/master-job-mix', MasterJobMixIndex::class)
        ->middleware('role:admin')
        ->name('master-job-mix');
});
