<?php

namespace Tests\Feature;

use App\Livewire\ExecutiveDashboard;
use App\Models\Customer;
use App\Models\JobMix;
use App\Models\Material;
use App\Models\MaterialReceive;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExecutiveDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_dashboard_route_is_restricted_by_rbac(): void
    {
        $admin = User::where('role', 'admin')->first();
        $surveyor = User::where('role', 'surveyor_opname')->first();
        $operator = User::where('role', 'operator_produksi')->first();

        // Guest redirected to login
        $this->get('/dashboard')->assertRedirect('/login');

        // Admin allowed
        $this->actingAs($admin)->get('/dashboard')->assertOk();

        // Surveyor & Operator forbidden (403)
        $this->actingAs($surveyor)->get('/dashboard')->assertForbidden();
        $this->actingAs($operator)->get('/dashboard')->assertForbidden();
    }

    public function test_executive_dashboard_renders_kpis_and_tables_accurately(): void
    {
        $admin = User::where('role', 'admin')->first();
        $customer = Customer::first();
        $jobMix = JobMix::where('mutu_code', 'K300 / C')->first();
        $supplier = Supplier::first();
        $pasirPalu = Material::where('name', 'Pasir Palu')->first();

        // 1. Create a Sales Order for current month
        SalesOrder::create([
            'so_number' => 'SO/01/TBI/26/10/0001',
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $customer->id,
            'location' => 'Palu Grand Mall',
            'job_mix_id' => $jobMix->id,
            'volume_m3' => 24.50,
            'distance_km' => 15.00,
            'est_semen_kg' => 8232.00,
            'est_solar_liter' => 12.50,
        ]);

        // 2. Create Material Receive for current month
        MaterialReceive::create([
            'receive_date' => now()->format('Y-m-d'),
            'supplier_id' => $supplier->id,
            'material_id' => $pasirPalu->id,
            'vehicle_no' => 'DN 5555 XY',
            'ri_no' => 'RI-DASH-01',
            'time_in' => '09:00',
            'volume_m3' => 50.00,
            'density_used' => 1658.00,
            'total_weight_kg' => 82900.00, // 82.9 Ton
        ]);

        $this->actingAs($admin);

        Livewire::test(ExecutiveDashboard::class)
            ->assertSee('Dashboard Eksekutif Real-Time')
            // Verify KPI Cards
            ->assertSee('24,50') // Sales volume
            ->assertSee('82,90') // Material Inbound Ton
            ->assertSee('Palu Grand Mall')
            ->assertSee('K300 / C')
            // Top Mutu section
            ->assertSee('Top Mutu Beton Dipesan')
            // Quick Stock Summary section
            ->assertSee('Ringkasan Status Stok & Deviasi Material Utama');
    }
}
