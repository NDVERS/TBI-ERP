<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('production_dockets', function (Blueprint $table) {
            $table->string('delivery_status', 30)->default('completed')->after('volume_m3')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_dockets', function (Blueprint $table) {
            $table->dropColumn('delivery_status');
        });
    }
};
