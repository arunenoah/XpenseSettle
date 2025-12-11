<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if table exists before modifying
        if (Schema::hasTable('expense_splits')) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('expense_splits', 'contact_id')) {
                Schema::table('expense_splits', function (Blueprint $table) {
                    $table->foreignId('contact_id')->nullable()->constrained('contacts')->onDelete('cascade')->after('user_id');
                });
            }

            // Make user_id nullable
            try {
                DB::statement('ALTER TABLE expense_splits MODIFY user_id BIGINT UNSIGNED NULL');
            } catch (\Exception $e) {
                // Already nullable or error occurred
            }

            // Drop foreign keys that depend on the unique constraint first
            try {
                DB::statement('ALTER TABLE expense_splits DROP FOREIGN KEY expense_splits_expense_id_foreign');
            } catch (\Exception $e) {
                // Foreign key doesn't exist or already dropped
            }

            try {
                DB::statement('ALTER TABLE expense_splits DROP FOREIGN KEY expense_splits_user_id_foreign');
            } catch (\Exception $e) {
                // Foreign key doesn't exist or already dropped
            }

            // Drop the old unique constraint
            try {
                DB::statement('ALTER TABLE expense_splits DROP INDEX expense_splits_expense_id_user_id_unique');
            } catch (\Exception $e) {
                // Index doesn't exist
            }

            // Add back the foreign keys
            Schema::table('expense_splits', function (Blueprint $table) {
                $table->foreign('expense_id')->references('id')->on('expenses')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });

            // Add the new unique constraint
            try {
                DB::statement('ALTER TABLE expense_splits ADD UNIQUE KEY expense_splits_expense_id_user_id_contact_id_unique (expense_id, user_id, contact_id)');
            } catch (\Exception $e) {
                // Constraint already exists
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop the table, just reverse the changes
        if (Schema::hasTable('expense_splits')) {
            try {
                DB::statement('ALTER TABLE expense_splits DROP FOREIGN KEY expense_splits_expense_id_foreign');
            } catch (\Exception $e) {
                // Already dropped
            }

            try {
                DB::statement('ALTER TABLE expense_splits DROP FOREIGN KEY expense_splits_user_id_foreign');
            } catch (\Exception $e) {
                // Already dropped
            }

            try {
                DB::statement('ALTER TABLE expense_splits DROP INDEX expense_splits_expense_id_user_id_contact_id_unique');
            } catch (\Exception $e) {
                // Index doesn't exist
            }

            // Re-add original constraints
            Schema::table('expense_splits', function (Blueprint $table) {
                $table->foreign('expense_id')->references('id')->on('expenses')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['expense_id', 'user_id']);
            });
        }
    }
};
