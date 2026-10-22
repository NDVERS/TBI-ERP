<?php

namespace Tests\Feature;

use App\Livewire\FleetManagement;
use App\Models\Fleet;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FleetManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_fleet_management_page_can_be_accessed_by_authorized_roles(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        $response = $this->get('/fleet-management');
        $response->assertStatus(200);
        $response->assertSee('Armada & Alat Berat');
    }

    public function test_master_data_has_11_tm_units_wheel_loader_and_others(): void
    {
        $this->assertEquals(11, Fleet::where('type', 'truck_mixer')->count());

        $loader = Fleet::where('type', 'wheel_loader')->first();
        $this->assertNotNull($loader);
        $this->assertEquals('Wheel Loader', $loader->unit_code);
        $this->assertStringContainsString('Bucket 1,8', $loader->model_specification);
        $this->assertEquals(30, $loader->service_interval_days);

        $others = Fleet::where('type', 'others')->first();
        $this->assertNotNull($others);
        $this->assertEquals('Lain-lain', $others->unit_code);
    }

    public function test_daily_log_auto_calculates_hours_and_updates_fleet_current_hm(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        $tm1 = Fleet::where('unit_code', 'TM 01')->first();
        $initialHm = (float) $tm1->current_hm;
        $initialRemaining = $tm1->remaining_hm;

        $newHm = $initialHm + 8.5; // Kerja 8.5 jam

        Livewire::test(FleetManagement::class)
            ->call('selectFleet', $tm1->id)
            ->set('log_date', now()->format('Y-m-d'))
            ->set('end_hm', $newHm)
            ->set('fuel_liters', 45.0)
            ->set('driver_operator_name', 'Pak Slamet')
            ->call('saveDailyLog')
            ->assertHasNoErrors()
            ->assertSee('berhasil disimpan');

        $tm1->refresh();
        $this->assertEquals($newHm, (float) $tm1->current_hm);
        $this->assertEquals(round($initialRemaining - 8.5, 2), $tm1->remaining_hm);

        $this->assertDatabaseHas('fleet_daily_logs', [
            'fleet_id' => $tm1->id,
            'total_hm' => 8.50,
            'fuel_liters' => 45.00,
            'driver_operator_name' => 'Pak Slamet',
        ]);
    }

    public function test_daily_log_validates_new_hm_cannot_be_less_than_last_hm(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        $tm1 = Fleet::where('unit_code', 'TM 01')->first();
        $initialHm = (float) $tm1->current_hm;

        Livewire::test(FleetManagement::class)
            ->call('selectFleet', $tm1->id)
            ->set('log_date', now()->format('Y-m-d'))
            ->set('end_hm', $initialHm - 10.0) // Invalid
            ->call('saveDailyLog')
            ->assertHasErrors(['end_hm']);
    }

    public function test_recording_maintenance_resets_service_interval(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        $tm1 = Fleet::where('unit_code', 'TM 01')->first();
        $serviceHm = (float) $tm1->current_hm;

        Livewire::test(FleetManagement::class)
            ->call('openServiceModal', $tm1->id)
            ->set('service_date', now()->format('Y-m-d'))
            ->set('hm_at_service', $serviceHm)
            ->set('mechanic_name', 'Mas Joko')
            ->set('service_description', 'Ganti oli mesin 15W40 & filter')
            ->call('saveMaintenanceRecord')
            ->assertHasNoErrors()
            ->assertSee('berhasil dicatat');

        $tm1->refresh();
        $this->assertEquals($serviceHm, (float) $tm1->last_service_hm);
        $this->assertEquals(500.0, (float) $tm1->remaining_hm);

        $this->assertDatabaseHas('fleet_maintenance_records', [
            'fleet_id' => $tm1->id,
            'hm_at_service' => $serviceHm,
            'mechanic_name' => 'Mas Joko',
        ]);
    }

    public function test_wheel_loader_evaluates_dual_threshold_maintenance(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        $loader = Fleet::where('type', 'wheel_loader')->first();
        $this->assertNotNull($loader);

        // Uji render pada tab wheel_loader
        Livewire::test(FleetManagement::class)
            ->call('setTab', 'wheel_loader')
            ->assertSee('Bucket 1,8')
            ->assertSee('Dual-Threshold Maintenance Alert')
            ->assertSee('500 Jam ATAU 1 Bulan');
    }
}
