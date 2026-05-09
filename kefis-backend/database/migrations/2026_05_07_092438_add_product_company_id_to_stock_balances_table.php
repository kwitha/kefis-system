<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_balances', function (Blueprint $table) {
            // Add the correct column if it doesn't exist
            $table->unsignedBigInteger('product_company_id')->nullable()->after('product_id');
            $table->foreign('product_company_id')->references('id')->on('product_companies')->nullOnDelete();

            // Copy existing company_id data into product_company_id
            // (only if company_id was being used as product_companies.id)
        });

        // Migrate existing data: copy company_id -> product_company_id
        DB::statement('UPDATE stock_balances SET product_company_id = company_id WHERE company_id IS NOT NULL');
    }

    public function down(): void
    {
        Schema::table('stock_balances', function (Blueprint $table) {
            $table->dropForeign(['product_company_id']);
            $table->dropColumn('product_company_id');
        });
    }
};