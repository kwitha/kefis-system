<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── purchases ────────────────────────────────────────────────
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('product_company_id')
                  ->nullable()
                  ->after('product_id')
                  ->constrained('product_companies')
                  ->onDelete('set null');
        });

        // ── sales ────────────────────────────────────────────────────
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('product_company_id')
                  ->nullable()
                  ->after('product_id')
                  ->constrained('product_companies')
                  ->onDelete('set null');
        });

        // ── transfers ────────────────────────────────────────────────
        Schema::table('transfers', function (Blueprint $table) {
            $table->foreignId('product_company_id')
                  ->nullable()
                  ->after('product_id')
                  ->constrained('product_companies')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_company_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_company_id');
        });

        Schema::table('transfers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_company_id');
        });
    }
};