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
        Schema::create('buildings_amenities', function (Blueprint $table) {
            $table->foreignId('building_id')->constrained();
            $table->foreignId('amenity_id')->constrained();
            $table->timestamps();

            $table->primary(['building_id', 'amenity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buildings_amenities');
    }
};
