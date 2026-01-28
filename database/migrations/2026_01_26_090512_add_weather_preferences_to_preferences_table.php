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
        Schema::table('preferences', function (Blueprint $table) {
            $table->string('city')->nullable()->after('health_goal');
            $table->string('country')->nullable()->after('city');
            $table->decimal('latitude', 10, 8)->nullable()->after('country');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->boolean('weather_based_recommendations')->default(false)->after('longitude');
            $table->string('weather_preference')->nullable()->after('weather_based_recommendations'); // hot, cold, rainy, etc.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preferences', function (Blueprint $table) {
            $table->dropColumn(['city', 'country', 'latitude', 'longitude', 'weather_based_recommendations', 'weather_preference']);
        });
    }
};
