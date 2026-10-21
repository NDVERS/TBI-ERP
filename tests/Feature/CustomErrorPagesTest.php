<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_403_forbidden_renders_custom_view(): void
    {
        $demoUser = User::where('role', 'demo')->firstOrFail();

        $response = $this->actingAs($demoUser)->get('/reconciliation');
        $response->assertStatus(403);
        $response->assertSee('HTTP 403 · FORBIDDEN');
        $response->assertSee('Akses Modul Terbatas');
        $response->assertSee('Ke Dashboard Eksekutif');
    }

    public function test_404_not_found_renders_custom_view(): void
    {
        $response = $this->get('/halaman-yang-tidak-ada-xyz');
        $response->assertStatus(404);
        $response->assertSee('HTTP 404 · NOT FOUND');
        $response->assertSee('Halaman Tidak Ditemukan');
        $response->assertSee('Ke Halaman Utama');
    }
}
