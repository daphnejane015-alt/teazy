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
        Schema::create('weather', function (Blueprint $table) {
            $table->id();
            $table->string('city');
            $table->string('country');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->date('date');
            $table->decimal('temperature', 5, 2); // Celsius
            $table->decimal('feels_like', 5, 2); // Celsius
            $table->integer('humidity'); // Percentage
            $table->decimal('wind_speed', 5, 2); // km/h
            $table->integer('pressure'); // hPa
            $table->string('main_condition'); // e.g., "Clear", "Clouds", "Rain"
            $table->string('description'); // e.g., "clear sky", "few clouds"
            $table->string('icon_code'); // Weather icon code from API
            $table->json('forecast_data'); // Full forecast data as JSON
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['city', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather');
    }
};
