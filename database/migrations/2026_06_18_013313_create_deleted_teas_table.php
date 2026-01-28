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
        Schema::create('deleted_teas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('normalized_name')->index();
            $table->string('source')->default('scraped');
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->json('original_data')->nullable();
            $table->timestamps();
            
            // Index for fast lookup
            $table->index(['normalized_name', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deleted_teas');
    }
};
