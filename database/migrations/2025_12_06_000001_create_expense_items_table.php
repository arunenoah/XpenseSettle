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
        Schema::create('expense_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained('expenses')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->timestamps();

            // Composite unique to prevent duplicate items for same expense
            $table->unique(['expense_id', 'name', 'total_price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_items');
    }
};
