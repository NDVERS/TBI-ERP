<?php

namespace Tests\Feature;

use App\Livewire\ExecutiveDashboard;
use App\Livewire\ProductionDocketForm;
use App\Livewire\PurchaseOrderForm;
use App\Livewire\StockReconciliation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QolSessionTwoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_production_docket_date_range_presets_and_density_toggle(): void
    {
        $operator = User::where('role', 'operator_produksi')->first()
            ?? User::factory()->create(['role' => 'operator_produksi']);

        $todayWita = Carbon::today('Asia/Makassar')->toDateString();

        Livewire::actingAs($operator)
            ->test(ProductionDocketForm::class)
            ->call('setDateRange', 'today')
            ->assertSet('filterDateRange', 'today')
            ->assertSet('filterStartDate', $todayWita)
            ->assertSet('filterEndDate', $todayWita)
            ->call('setDateRange', 'all')
            ->assertSet('filterDateRange', 'all')
            ->assertSet('filterStartDate', null)
            ->assertSeeHtml('Tabel: Normal')
            ->assertSeeHtml('Hari Ini')
            ->assertSeeHtml('Minggu Ini');
    }

    public function test_purchase_order_date_range_presets_and_density_toggle(): void
    {
        $admin = User::where('role', 'admin')->first()
            ?? User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(PurchaseOrderForm::class)
            ->call('setDateRange', 'this_month')
            ->assertSet('filterDateRange', 'this_month')
            ->assertSet('filterStartDate', now()->startOfMonth()->toDateString())
            ->assertSet('filterEndDate', now()->endOfMonth()->toDateString())
            ->assertSeeHtml('Tabel: Normal')
            ->assertSeeHtml('Bulan Ini');
    }

    public function test_stock_reconciliation_and_dashboard_quick_month_presets(): void
    {
        $admin = User::where('role', 'admin')->first()
            ?? User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(StockReconciliation::class)
            ->call('setLastMonth')
            ->assertSet('selectedMonth', now()->subMonth()->month)
            ->assertSet('selectedYear', now()->subMonth()->year)
            ->call('setThisMonth')
            ->assertSet('selectedMonth', now()->month)
            ->assertSet('selectedYear', now()->year)
            ->assertSeeHtml('Bulan Ini')
            ->assertSeeHtml('Bulan Lalu')
            ->assertSeeHtml('Tabel: Normal');

        Livewire::actingAs($admin)
            ->test(ExecutiveDashboard::class)
            ->call('setLastMonth')
            ->assertSet('selectedMonth', now()->subMonth()->month)
            ->assertSeeHtml('Bulan Ini')
            ->assertSeeHtml('Bulan Lalu')
            ->assertSeeHtml('+ Terbitkan PO');
    }
}
