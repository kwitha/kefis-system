<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductCompaniesTable extends Migration
{
    public function up(): void
    {
        // Only create if it doesn't exist
        if (!Schema::hasTable('product_companies')) {
            Schema::create('product_companies', function (Blueprint $table) {
                $table->id();

                // FK to products table
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->decimal('buying_price',10,2)->default(0);
                $table->decimal('selling_price',10,2)->default(0);
                $table->integer('stock')->default(0);
                 $table->integer('minimum_stock')->default(0);
                $table->boolean('is_active')->default(true);

                $table->timestamps();
                $table->unique(['product_id','company_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_companies');
    }
}