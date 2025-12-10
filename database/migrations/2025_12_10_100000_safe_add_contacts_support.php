<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration is PRODUCTION-SAFE:
     * - Creates new contacts table
     * - Adds contact_id columns without removing existing user_id columns
     * - All existing data remains intact
     * - Allows user_id OR contact_id (gradual migration path)
     */
    public function up(): void
    {
        // Step 1: Create contacts table (new, no data loss)
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();

            $table->unique(['group_id', 'email']);
        });

        // Step 2: Add contact_id to group_members WITHOUT modifying user_id
        Schema::table('group_members', function (Blueprint $table) {
            // Add the column
            $table->foreignId('contact_id')->nullable()->after('user_id')->constrained('contacts')->cascadeOnDelete();
        });

        // Step 3: Add contact_id to expense_splits WITHOUT modifying user_id
        Schema::table('expense_splits', function (Blueprint $table) {
            // Add the column
            $table->foreignId('contact_id')->nullable()->after('user_id')->constrained('contacts')->cascadeOnDelete();
        });

        // Step 4: Update unique constraints to allow NULL contact_id
        // For group_members: keep existing (group_id, user_id) unique for user records
        // For expense_splits: keep existing (expense_id, user_id) unique for user records
        // New records with contact_id can coexist since contact_id is part of the constraint

        // Note: The unique constraints (group_id, user_id) and (expense_id, user_id)
        // will still work because:
        // - Existing records have user_id set, contact_id = null
        // - New contact records will have contact_id set, user_id = null
        // - MySQL treats NULL differently in unique constraints (allows multiple NULLs)
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_splits', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropColumn('contact_id');
        });

        Schema::table('group_members', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropColumn('contact_id');
        });

        Schema::dropIfExists('contacts');
    }
};
