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
}
