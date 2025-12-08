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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // group_created, user_added, expense_created, advance_paid, payment_made, settlement_confirmed
            $table->string('title'); // e.g., "Group created", "Advance paid to Karthik"
            $table->text('description')->nullable(); // Details about the activity
            $table->decimal('amount', 10, 2)->nullable(); // For expense/advance/payment activities
            $table->string('category')->nullable(); // For filtering (expense, advance, payment, etc.)
            $table->json('related_users')->nullable(); // Users involved in the activity
            $table->unsignedBigInteger('related_id')->nullable(); // ID of related model (expense_id, advance_id, etc.)
            $table->string('related_type')->nullable(); // Type of related model
            $table->timestamps();

            $table->index('group_id');
            $table->index('user_id');
            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
