<?php

namespace Tests\Feature;

use App\Livewire\MasterJobMixIndex;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MasterJobMixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_guest_is_redirected_from_master_job_mix_page(): void
    {
        $response = $this->get('/master-job-mix');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_master_job_mix_route_successfully(): void
    {
        $admin = User::where('role', 'admin')->first();
        $response = $this->actingAs($admin)->get('/master-job-mix');
        $response->assertStatus(200);

        // Akun demo diblokir 403 dari Master Job Mix (Manajemen & Audit)
        $demo = User::where('role', 'demo')->first();
        $this->actingAs($demo)->get('/master-job-mix')->assertStatus(403);
    }

    public function test_master_job_mix_renders_all_40_standard_operational_recipes_accurately(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        $test = Livewire::test(MasterJobMixIndex::class);

        // Assert header dan badge total mutu
        $test->assertSee('Master Resep Adukan Beton (Job Mix)')
            ->assertSee('Total Mutu: 40')
            ->assertSee('K-Series: 34')
            ->assertSee('FC/FS: 6');

        // Pastikan kolom-kolom tabel tertera
        $test->assertSee('Kode Mutu')
            ->assertSee('Kategori')
            ->assertSee('Semen (Kg)')
            ->assertSee('Pasir Palu (Kg)')
            ->assertSee('Pasir Lokal (Kg)')
            ->assertSee('Split 1/2 (Kg)')
            ->assertSee('Split 2/3 (Kg)')
            ->assertSee('SBTJM2 (Liter)')
            ->assertSee('Air (L/Kg)');

        // Sampel mutu K-Series awal, tengah, dan atas
        $test->assertSee('K100 / C')
            ->assertSee('K100 / R')
            ->assertSee('K250 / C')
            ->assertSee('K350 / R')
            ->assertSee('K500 / C')
            ->assertSee('K500 / R');

        // Sampel mutu FC dan FS
        $test->assertSee('FC10')
            ->assertSee('FC20')
            ->assertSee('FC30')
            ->assertSee('FS45');

        // Memastikan seluruh 40 mutu standar di-pass ke viewData
        $jobMixes = $test->viewData('jobMixes');
        $this->assertCount(40, $jobMixes);
        $this->assertFalse($jobMixes->contains('mutu_code', 'B0'));
        $this->assertFalse($jobMixes->contains('mutu_code', 'FC35NCPC'));
    }

    public function test_master_job_mix_search_and_category_filtering(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        // 1. Uji Search: mencari 'FS45'
        Livewire::test(MasterJobMixIndex::class)
            ->set('search', 'FS45')
            ->assertSee('FS45')
            ->assertDontSee('K100 / C')
            ->assertDontSee('K500 / R');

        // 2. Uji Filter Kategori: 'k_series'
        $kTest = Livewire::test(MasterJobMixIndex::class)
            ->set('selectedCategory', 'k_series');

        $kList = $kTest->viewData('jobMixes');
        $this->assertCount(34, $kList);
        $this->assertTrue($kList->every(fn ($jm) => str_starts_with($jm->mutu_code, 'K')));

        // 3. Uji Filter Kategori: 'fc_fs'
        $fcTest = Livewire::test(MasterJobMixIndex::class)
            ->set('selectedCategory', 'fc_fs');

        $fcList = $fcTest->viewData('jobMixes');
        $this->assertCount(6, $fcList);
        $this->assertTrue($fcList->every(fn ($jm) => ! str_starts_with($jm->mutu_code, 'K')));
    }
}
