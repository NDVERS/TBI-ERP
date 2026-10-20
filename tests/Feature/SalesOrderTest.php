<?php

namespace Tests\Feature;

use App\Livewire\SalesOrderForm;
use App\Models\Customer;
use App\Models\JobMix;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_sales_order_dropdown_only_contains_allowed_mutu_standards(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        $test = Livewire::test(SalesOrderForm::class);

        // Memverifikasi sampel mutu K-Series dan FC/FS ada di view
        $test->assertSee('Seri K (K-Series)')
            ->assertSee('Seri FC & FS (Megapascal & Flexural)', false)
            ->assertSee('K100 / C')
            ->assertSee('K100 / R')
            ->assertSee('K350 / C')
            ->assertSee('K500 / C')
            ->assertSee('K500 / R')
            ->assertSee('FC10')
            ->assertSee('FC20')
            ->assertSee('FC30')
            ->assertSee('FS45');

        // Memverifikasi opsi non-standar (seperti B0 dan FC35NCPC) TIDAK muncul di dropdown SO
        $jobMixes = $test->viewData('jobMixes');
        $this->assertNotEmpty($jobMixes);
        $this->assertFalse($jobMixes->contains('mutu_code', 'B0'));
        $this->assertFalse($jobMixes->contains('mutu_code', 'FC35NCPC'));
    }

    public function test_sales_order_saves_valid_mutu_from_allowed_list(): void
    {
        $kasir = User::where('role', 'kasir_logistik')->first();
        $customer = Customer::first();
        $this->actingAs($kasir);

        $allowedSamples = ['K100 / C', 'K500 / R', 'FC20', 'FS45'];

        foreach ($allowedSamples as $index => $mutuCode) {
            $jobMix = JobMix::where('mutu_code', $mutuCode)->first();
            $this->assertNotNull($jobMix, "JobMix {$mutuCode} harus ada di database.");

            $vol = 5.0 + $index;
            $dist = 10.0 + $index;

            Livewire::test(SalesOrderForm::class)
                ->set('order_date', now()->toDateString())
                ->set('customer_id', $customer->id)
                ->set('location', "Proyek Uji {$mutuCode}")
                ->set('job_mix_id', $jobMix->id)
                ->set('volume_m3', $vol)
                ->set('distance_km', $dist)
                ->call('save')
                ->assertHasNoErrors()
                ->assertSee("Proyek Uji {$mutuCode}");

            $so = SalesOrder::where('job_mix_id', $jobMix->id)->latest('id')->first();
            $this->assertNotNull($so);
            $this->assertEquals($vol, (float) $so->volume_m3);
            $this->assertEquals($dist, (float) $so->distance_km);
            $this->assertGreaterThan(0, (float) $so->est_semen_kg);
            $this->assertGreaterThan(0, (float) $so->est_solar_liter);
        }
    }

    public function test_sales_order_fails_validation_for_non_standard_or_removed_mutu(): void
    {
        $kasir = User::where('role', 'kasir_logistik')->first();
        $customer = Customer::first();
        $this->actingAs($kasir);

        // Mutu B0 ada di tabel job_mixes, tetapi TIDAK diizinkan untuk Sales Order baru
        $b0JobMix = JobMix::where('mutu_code', 'B0')->first();
        $this->assertNotNull($b0JobMix);

        Livewire::test(SalesOrderForm::class)
            ->set('order_date', now()->toDateString())
            ->set('customer_id', $customer->id)
            ->set('location', 'Proyek Non-Standar B0')
            ->set('job_mix_id', $b0JobMix->id)
            ->set('volume_m3', 5.0)
            ->set('distance_km', 10.0)
            ->call('save')
            ->assertHasErrors(['job_mix_id']);

        // Mutu FC35NCPC juga harus ditolak
        $fc35JobMix = JobMix::where('mutu_code', 'FC35NCPC')->first();
        $this->assertNotNull($fc35JobMix);

        Livewire::test(SalesOrderForm::class)
            ->set('order_date', now()->toDateString())
            ->set('customer_id', $customer->id)
            ->set('location', 'Proyek Non-Standar FC35')
            ->set('job_mix_id', $fc35JobMix->id)
            ->set('volume_m3', 5.0)
            ->set('distance_km', 10.0)
            ->call('save')
            ->assertHasErrors(['job_mix_id']);

        // ID acak yang tidak ada
        Livewire::test(SalesOrderForm::class)
            ->set('order_date', now()->toDateString())
            ->set('customer_id', $customer->id)
            ->set('location', 'Proyek Invalid ID')
            ->set('job_mix_id', 999999)
            ->set('volume_m3', 5.0)
            ->set('distance_km', 10.0)
            ->call('save')
            ->assertHasErrors(['job_mix_id']);
    }
}
