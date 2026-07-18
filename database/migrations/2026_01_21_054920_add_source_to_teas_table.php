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
        if (! Schema::hasTable('teas')) {
            return;
        }

        if (! Schema::hasColumn('teas', 'source')) {
            Schema::table('teas', function (Blueprint $table) {
                $table->enum('source', ['scraped', 'manual'])->default('manual')->after('image');
            });
        }

        if (! Schema::hasColumn('teas', 'source_url')) {
            Schema::table('teas', function (Blueprint $table) {
                $table->string('source_url', 500)->nullable()->after('source');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teas', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
