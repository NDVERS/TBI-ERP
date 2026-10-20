<?php

namespace Tests\Feature;

use App\Livewire\ExecutiveDashboard;
use App\Livewire\StockReconciliation;
use App\Livewire\VesselShipmentForm;
use App\Models\Material;
use App\Models\StockOpname;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EarlyWarningsAndAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_executive_dashboard_shows_low_stock_warning_when_material_below_threshold(): void
    {
        $admin = User::where('role', 'admin')->first();

        // SBTJM2 has critical low stock (< 5,000 Liter)
        $sbtjm2 = Material::where('name', 'SBTJM2')->first();
        $this->assertNotNull($sbtjm2);
        $this->assertEquals('Liter', $sbtjm2->unit);
        $this->assertLessThan(5000, (float) $sbtjm2->initial_stock_kg);

        $this->actingAs($admin);

        Livewire::test(ExecutiveDashboard::class)
            ->assertSee('Peringatan Dini:')
            ->assertSee('Bawah Stok Kritis')
            ->assertSee('SBTJM2');
    }

    public function test_vessel_shipment_form_shows_warning_for_high_shrinkage(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        // Load 1000 m3, Unload 970 m3 => Shrinkage 30 m3 (3.0% > 2.0%)
        Livewire::test(VesselShipmentForm::class)
            ->set('load_volume', 1000.00)
            ->set('unload_volume', 970.00)
            ->assertSee('PERINGATAN SUSUT TINGGI')
            ->assertSee('3,00%')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('PERINGATAN: Pengiriman kapal tersimpan dengan tingkat susut TINGGI');
    }

    public function test_stock_reconciliation_highlights_high_opname_deviations(): void
    {
        $admin = User::where('role', 'admin')->first();
        $pasirPalu = Material::where('name', 'Pasir Palu')->first();

        // Pasir Palu initial stock: 1,149,839.58 Kg
        // Input opname with 5% deviation: 1,092,347.60 Kg (diff -57,491.98 Kg => ~5.0% deviation > 2.0%)
        StockOpname::create([
            'opname_date' => now()->format('Y-m-d'),
            'material_id' => $pasirPalu->id,
            'method' => 'limas_terpancung',
            'dimensions_json' => ['la' => 100, 'lb' => 200, 't' => 3],
            'calculated_volume_m3' => 658.83,
            'calculated_weight_kg' => 1092347.60,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(StockReconciliation::class)
            ->assertSee('Peringatan Audit:')
            ->assertSee('Deviasi Opname Tinggi')
            ->assertSee('ANOMALI (>2%)');
    }
}
