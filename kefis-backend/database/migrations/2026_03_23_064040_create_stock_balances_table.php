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
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 10, 2)->default(0);
            $table->timestamps();
           
            $table->unique(['branch_id', 'product_id'], 'stock_balances_branch_product_unique'); // <-- named index fixes it
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_balances');
    }
};