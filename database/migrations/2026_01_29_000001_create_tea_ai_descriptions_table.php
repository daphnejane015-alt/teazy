<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tea_ai_descriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tea_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->text('description');
            $table->string('preference_signature', 64);
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->unique(['tea_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tea_ai_descriptions');
    }
};
