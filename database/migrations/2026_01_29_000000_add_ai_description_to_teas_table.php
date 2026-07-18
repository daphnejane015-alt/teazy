<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teas', function (Blueprint $table) {
            $table->text('ai_description')->nullable()->after('health_benefit');
            $table->timestamp('ai_description_generated_at')->nullable()->after('ai_description');
        });
    }

    public function down(): void
    {
        Schema::table('teas', function (Blueprint $table) {
            $table->dropColumn(['ai_description', 'ai_description_generated_at']);
        });
    }
};
