<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = Carbon::now();

        DB::table('users')->updateOrInsert(
            ['email' => 'demo@tbi.com'],
            [
                'name' => 'User Demo TBI',
                'email' => 'demo@tbi.com',
                'password' => Hash::make('password'),
                'role' => 'demo',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->where('email', 'demo@tbi.com')->delete();
    }
};
