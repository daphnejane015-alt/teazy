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
        Schema::create('recommendation_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tea_id')->constrained()->onDelete('cascade');
            
            // Context information
            $table->string('weather_category')->nullable(); // hot, cold, rainy, etc.
            $table->decimal('temperature', 5, 2)->nullable();
            $table->decimal('humidity', 5, 2)->nullable();
            $table->string('day_of_week')->nullable();
            $table->string('time_of_day')->nullable();
            $table->string('season')->nullable();
            
            // User preferences at time of interaction
            $table->string('user_flavor_preference')->nullable();
            $table->string('user_caffeine_preference')->nullable();
            $table->string('user_health_goal')->nullable();
            $table->string('user_weather_preference')->nullable();
            
            // Interaction details
            $table->string('action'); // viewed, rated, liked, disliked, ignored, recommended
            $table->tinyInteger('rating')->nullable(); // 1-5 rating if applicable
            $table->string('algorithm_used')->nullable(); // rule-based, behavior-learning, collaborative, ml
            $table->decimal('confidence_score', 3, 2)->nullable(); // 0.00-1.00
            $table->decimal('prediction_score', 3, 2)->nullable(); // 0.00-1.00
            
            // Metadata
            $table->json('features')->nullable(); // ML features used
            $table->json('feature_importance')->nullable(); // Feature importance for ML models
            $table->string('model_version')->nullable(); // ML model version
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index(['tea_id', 'action']);
            $table->index(['weather_category', 'action']);
            $table->index(['algorithm_used', 'created_at']);
            $table->index(['created_at']); // for time-based queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommendation_interactions');
    }
};
