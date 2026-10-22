<?php

namespace Tests\Feature;

use App\Livewire\DexliteConsumption;
use App\Models\Fleet;
use App\Models\FleetDailyLog;
use App\Models\FuelReceive;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DexliteConsumptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_dexlite_consumption_page_can_be_accessed_by_authorized_roles(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        $response = $this->get('/dexlite-consumption');
        $response->assertOk();
        $response->assertSee('Buku Konsumsi Dexlite');
        $response->assertSee('Matriks Harian');
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/dexlite-consumption');
        $response->assertRedirect('/login');
    }

    public function test_dexlite_summary_kpi_matches_physical_and_ledger_numbers(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        Livewire::test(DexliteConsumption::class)
            ->set('selectedMonth', '2026-10')
            ->assertSee('1.234,85') // Initial stock
            ->assertSee('16.439,88') // Total receives
            ->assertSee('16.530,00') // Total usage
            ->assertSee('1.144,73') // Sounding & ending stock
            ->assertSee('SESUAI 100%');
    }

    public function test_dexlite_matrix_renders_authentic_unit_columns_and_totals(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        Livewire::test(DexliteConsumption::class)
            ->set('selectedMonth', '2026-10')
            ->assertSee('TM 01')
            ->assertSee('TM 12')
            ->assertSee('Loader')
            ->assertSee('TM Rental')
            ->assertSee('CP (Pump)')
            ->assertSee('12.957'); // TM Subtotal
    }

    public function test_efficiency_indices_evaluate_properly(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        Livewire::test(DexliteConsumption::class)
            ->set('selectedMonth', '2026-10')
            ->set('referenceConcreteVolume', 747.3)
            ->call('setTab', 'indices')
            ->assertSee('1. Loader')
            ->assertSee('0,88') // Loader index
            ->assertSee('17,34') // TM index
            ->assertSee('22,12') // All index
            ->assertSee('Perbaiki');
    }

    public function test_user_can_save_new_fuel_receive(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        Livewire::test(DexliteConsumption::class)
            ->call('openReceiveModal')
            ->set('receive_date', '2026-10-15')
            ->set('supplier_name', 'PT Pertamina Patra Niaga')
            ->set('ri_do_number', 'DO-TEST-001')
            ->set('vehicle_no', 'KT 8999 ZZ')
            ->set('volume_liters', '2000.00')
            ->call('saveFuelReceive')
            ->assertHasNoErrors()
            ->assertSee('berhasil disimpan');

        $rec = FuelReceive::where('ri_do_number', 'DO-TEST-001')->first();
        $this->assertNotNull($rec);
        $this->assertEquals('2026-10-15', $rec->receive_date->format('Y-m-d'));
        $this->assertEquals(2000.0, (float) $rec->volume_liters);
    }

    public function test_user_can_save_daily_unit_fuel_log(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        $tm1 = Fleet::where('unit_code', 'TM 01')->first();

        Livewire::test(DexliteConsumption::class)
            ->call('openUsageModal')
            ->set('usage_date', '2026-10-16')
            ->set('usage_fleet_id', $tm1->id)
            ->set('usage_fuel_liters', '175.5')
            ->call('saveDailyFuelLog')
            ->assertHasNoErrors()
            ->assertSee('berhasil dicatat');

        $log = FleetDailyLog::where('fleet_id', $tm1->id)->whereDate('log_date', '2026-10-16')->first();
        $this->assertNotNull($log);
        $this->assertEquals(175.5, (float) $log->fuel_liters);
    }

    public function test_dexlite_smart_alert_and_outlier_detection_qol(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        $component = Livewire::test(DexliteConsumption::class)
            ->set('selectedMonth', '2026-10');

        // Test Smart Stock Alert
        $this->assertTrue($component->get('isLowStock'));
        $this->assertGreaterThan(0, $component->get('daysRemaining'));
        $component->assertSee('SMART ALERT: STOK TANGKI SOLAR MENDEKATI BATAS MINIMUM');

        // Test Anomaly / Outlier Detection
        $anomalies = $component->get('anomalousFills');
        $this->assertIsArray($anomalies);
    }

    public function test_dexlite_export_excel_returns_valid_streamed_csv(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        Livewire::test(DexliteConsumption::class)
            ->set('selectedMonth', '2026-10')
            ->call('exportExcel')
            ->assertFileDownloaded('LAPORAN_PEMAKAIAN_SOLAR_DTM_PLANT_TBI_2026-10.csv');
    }

    public function test_dexlite_whatsapp_daily_summary_text_generation(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        $component = Livewire::test(DexliteConsumption::class)
            ->set('selectedMonth', '2026-10')
            ->set('shareDate', '2026-10-03');

        $text = $component->get('whatsappDailyText');
        $this->assertStringContainsString('REKAP HARIAN KONSUMSI DEXLITE', $text);
        $this->assertStringContainsString('PLANT RINGROAD 3', $text);
        $this->assertStringContainsString('Pemakaian TM (11 Unit)', $text);
    }
}
