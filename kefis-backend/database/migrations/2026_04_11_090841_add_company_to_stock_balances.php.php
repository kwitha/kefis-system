<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       

    Schema::table('stock_balances', function (Blueprint $table) {

        // 1. Drop foreign keys first
        $table->dropForeign(['branch_id']);
        $table->dropForeign(['product_id']);

        // 2. Drop the unique index
        $table->dropUnique('stock_balances_branch_product_unique');

        // 3. Add new column
        $table->foreignId('company_id')->nullable()->constrained('product_companies')->nullOnDelete();

        // 4. Re-add foreign keys
        $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
        $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();

        //Add new unique constaint
        $table->unique(['branch_id','product_id','company_id'],'stock_branch_product_company_unique');
    });
}

    public function down(): void
    {
        Schema::table('stock_balances', function (Blueprint $table) {
            $table->dropUnique('stock_branch_product_company_unique');
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
            $table->unique(['branch_id', 'product_id'], 'stock_balances_branch_product_unique');
        });
    }
};