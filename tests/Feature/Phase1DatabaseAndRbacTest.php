<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class Phase1DatabaseAndRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_populates_master_data_correctly(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Verify Users for all roles (including demo)
        $this->assertDatabaseCount('users', 6);
        $this->assertDatabaseHas('users', ['email' => 'admin@tbi.com', 'role' => 'admin']);
        $this->assertDatabaseHas('users', ['email' => 'demo@tbi.com', 'role' => 'demo']);
        $this->assertDatabaseHas('users', ['email' => 'kasir@tbi.com', 'role' => 'kasir_logistik']);
        $this->assertDatabaseHas('users', ['email' => 'gudang@tbi.com', 'role' => 'petugas_gudang']);
        $this->assertDatabaseHas('users', ['email' => 'operator@tbi.com', 'role' => 'operator_produksi']);
        $this->assertDatabaseHas('users', ['email' => 'surveyor@tbi.com', 'role' => 'surveyor_opname']);

        // Verify 9 Materials (Konsolidasi Pasir Palu Ex-Lariang & Tibo, Additive Kg & SBTJM2 Liter)
        $this->assertDatabaseCount('materials', 9);
        $this->assertDatabaseHas('materials', ['name' => 'Semen', 'unit' => 'Kg']);
        $this->assertDatabaseHas('materials', ['name' => 'Pasir Palu', 'unit' => 'Kg']);
        $this->assertDatabaseHas('materials', ['name' => 'Batu Besar 2/3', 'unit' => 'Kg']);
        $this->assertDatabaseHas('materials', ['name' => 'Additive', 'unit' => 'Kg']);
        $this->assertDatabaseHas('materials', ['name' => 'SBTJM2', 'unit' => 'Liter']);
        $this->assertDatabaseHas('materials', ['name' => 'Dexlite', 'unit' => 'Liter']);
        $this->assertDatabaseMissing('materials', ['name' => 'Pasir Lariang']);
        $this->assertDatabaseMissing('materials', ['name' => 'Pasir Tibo']);

        // Verify 21 Normalized & Deduplicated Suppliers (including TBI Plant)
        $this->assertDatabaseCount('suppliers', 21);
        $this->assertDatabaseHas('suppliers', ['name' => 'WAN SHANG( CONTAINER DARI CHINA)']);
        $this->assertDatabaseHas('suppliers', ['name' => 'PT. BALQIS REZA REVOLO']);
        $this->assertDatabaseHas('suppliers', ['name' => 'TBI Plant']);

        // Verify Customers
        $this->assertGreaterThanOrEqual(20, Customer::count());
        $this->assertDatabaseHas('customers', ['name' => 'PT Recta Construction']);

        // Verify Master Job Mixes (24 baseline + 18 operational standards = 42)
        $this->assertDatabaseCount('job_mixes', 42);
        $this->assertDatabaseHas('job_mixes', [
            'mutu_code' => 'K300 / C',
            'waste_factor' => '1.0500',
        ]);
        $this->assertDatabaseHas('job_mixes', [
            'mutu_code' => 'K100 / C',
        ]);
    }

    public function test_role_middleware_authorizes_accessible_roles(): void
    {
        Route::get('/test-admin-only', function () {
            return response()->json(['status' => 'success']);
        })->middleware(['web', 'role:admin']);

        Route::get('/test-kasir-only', function () {
            return response()->json(['status' => 'success']);
        })->middleware(['web', 'role:kasir_logistik']);

        $admin = User::factory()->create(['role' => 'admin']);
        $kasir = User::factory()->create(['role' => 'kasir_logistik']);
        $operator = User::factory()->create(['role' => 'operator_produksi']);

        // Admin can access admin-only and kasir-only routes (superuser)
        $this->actingAs($admin)->get('/test-admin-only')->assertStatus(200);
        $this->actingAs($admin)->get('/test-kasir-only')->assertStatus(200);

        // Kasir can access kasir-only route
        $this->actingAs($kasir)->get('/test-kasir-only')->assertStatus(200);

        // Operator cannot access kasir-only route
        $this->actingAs($operator)->get('/test-kasir-only')->assertStatus(403);

        // Kasir cannot access admin-only route
        $this->actingAs($kasir)->get('/test-admin-only')->assertStatus(403);
    }

    public function test_user_model_role_helper_methods(): void
    {
        $admin = new User(['role' => 'admin']);
        $operator = new User(['role' => 'operator_produksi']);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($operator->isAdmin());

        $this->assertTrue($operator->hasRole('operator_produksi'));
        $this->assertTrue($operator->hasRole(['operator_produksi', 'petugas_gudang']));
        $this->assertFalse($operator->hasRole(['admin', 'kasir_logistik']));
    }
}
