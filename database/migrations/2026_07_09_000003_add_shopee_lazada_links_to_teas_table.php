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

        if (! Schema::hasColumn('teas', 'shop_link')) {
            Schema::table('teas', function (Blueprint $table) {
                $table->string('shop_link', 500)->nullable()->after('source');
            });
        }

        if (! Schema::hasColumn('teas', 'shopee_link')) {
            Schema::table('teas', function (Blueprint $table) {
                $table->string('shopee_link', 500)->nullable();
            });
        }

        if (! Schema::hasColumn('teas', 'lazada_link')) {
            Schema::table('teas', function (Blueprint $table) {
                $table->string('lazada_link', 500)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teas', function (Blueprint $table) {
            $table->dropColumn(['shopee_link', 'lazada_link']);
        });
    }
};
