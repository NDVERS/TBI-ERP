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
        Schema::create('additive_powders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->decimal('recipe_quantity_kg', 10, 2)->default(0);
            $table->decimal('standard_percentage', 5, 2)->default(0);
            $table->decimal('stock_kg', 10, 2)->default(0);
            $table->string('unit', 20)->default('Kg');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('lab_conversions', function (Blueprint $table) {
            $table->decimal('snf1_kg', 10, 2)->default(0)->after('additive_used_kg');
            $table->decimal('snf2_kg', 10, 2)->default(0)->after('snf1_kg');
            $table->decimal('sodium_glukonate_kg', 10, 2)->default(0)->after('snf2_kg');
            $table->decimal('maltodextrine_kg', 10, 2)->default(0)->after('sodium_glukonate_kg');
            $table->decimal('water_kg', 10, 2)->default(0)->after('maltodextrine_kg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lab_conversions', function (Blueprint $table) {
            $table->dropColumn(['snf1_kg', 'snf2_kg', 'sodium_glukonate_kg', 'maltodextrine_kg', 'water_kg']);
        });

        Schema::dropIfExists('additive_powders');
    }
};
