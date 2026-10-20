<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2BaseUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_with_base_ui_components_successfully(): void
    {
        Material::create([
            'name' => 'Semen',
            'unit' => 'Kg',
            'density_kg_m3' => 1000.00,
            'initial_stock_kg' => 33739750.00,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('TBI ERP');
        $response->assertSee('Batching Plant');
        $response->assertSee('Total Material');
        $response->assertSee('Semen');
        $response->assertSee('33.739.750,00');
    }

    public function test_layout_sidebar_shows_admin_nav_items_only_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $operator = User::factory()->create(['role' => 'operator_produksi']);

        // Admin sees admin-only navigation
        $this->actingAs($admin)
            ->get('/')
            ->assertStatus(200)
            ->assertSee('Master Job Mix (IP)')
            ->assertSee('Rekonsiliasi Stok');

        // Operator does not see admin-only secret navigation
        $this->actingAs($operator)
            ->get('/')
            ->assertStatus(200)
            ->assertDontSee('Master Job Mix (IP)')
            ->assertDontSee('Rekonsiliasi Stok')
            ->assertSee('Produksi Harian');
    }

    public function test_layout_shows_correct_role_badge(): void
    {
        $surveyor = User::factory()->create([
            'name' => 'Budi Surveyor',
            'role' => 'surveyor_opname',
        ]);

        $this->actingAs($surveyor)
            ->get('/')
            ->assertStatus(200)
            ->assertSee('Budi Surveyor')
            ->assertSee('Surveyor Lapangan');
    }

    public function test_guest_and_non_admin_cannot_see_density_in_drawer_or_json_payload(): void
    {
        Material::create([
            'name' => 'Pasir Palu Khusus',
            'unit' => 'Kg',
            'density_kg_m3' => 1658.50,
            'initial_stock_kg' => 50000.00,
        ]);

        $operator = User::factory()->create(['role' => 'operator_produksi']);

        // 1. Guest access
        $guestResponse = $this->get('/');
        $guestResponse->assertStatus(200);
        $guestResponse->assertSee('Pasir Palu Khusus');
        $guestResponse->assertSee('Terkunci (Admin Only)');
        $guestResponse->assertDontSee('1.658,50');
        $guestResponse->assertDontSee('1658.5');

        // 2. Non-admin operator access
        $operatorResponse = $this->actingAs($operator)->get('/');
        $operatorResponse->assertStatus(200);
        $operatorResponse->assertSee('Pasir Palu Khusus');
        $operatorResponse->assertSee('Terkunci (Admin Only)');
        $operatorResponse->assertDontSee('1.658,50');
        $operatorResponse->assertDontSee('1658.5');
    }

    public function test_admin_can_see_density_in_table_and_drawer_payload(): void
    {
        Material::create([
            'name' => 'Split Khusus Admin',
            'unit' => 'Kg',
            'density_kg_m3' => 1517.25,
            'initial_stock_kg' => 75000.00,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $adminResponse = $this->actingAs($admin)->get('/');
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Split Khusus Admin');
        $adminResponse->assertSee('1.517,25');
    }

    public function test_hybrid_responsive_table_and_drawer_markup_are_rendered(): void
    {
        Material::create([
            'name' => 'Semen Padang',
            'unit' => 'Kg',
            'density_kg_m3' => 1000.00,
            'initial_stock_kg' => 10000.00,
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('drawerOpen');
        $response->assertSee('openMaterial(');
        $response->assertSee('hidden md:table-cell', false);
        $response->assertSee('Spesifikasi teknis dan stok operasional batching plant');
    }
}
