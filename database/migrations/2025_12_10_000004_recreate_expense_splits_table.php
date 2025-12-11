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
        // Check if table exists before modifying
        if (Schema::hasTable('expense_splits')) {
            Schema::table('expense_splits', function (Blueprint $table) {
                // Add contact_id if it doesn't exist
                if (!Schema::hasColumn('expense_splits', 'contact_id')) {
                    $table->foreignId('contact_id')->nullable()->constrained('contacts')->onDelete('cascade')->after('user_id');
                }
                // Make user_id nullable if it isn't already
                if (Schema::hasColumn('expense_splits', 'user_id')) {
                    // Alter to make user_id nullable
                    try {
                        DB::statement('ALTER TABLE expense_splits MODIFY user_id BIGINT UNSIGNED NULL');
                    } catch (\Exception $e) {
                        // Already nullable or MySQL version doesn't support this
                    }
                }
            });

            // Drop old unique constraint and add new one
            Schema::table('expense_splits', function (Blueprint $table) {
                try {
                    $table->dropUnique(['expense_id', 'user_id']);
                } catch (\Exception $e) {
                    // Constraint doesn't exist, continue
                }
            });

            Schema::table('expense_splits', function (Blueprint $table) {
                // Add new unique constraint
                $table->unique(['expense_id', 'user_id', 'contact_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop the table, just reverse the changes
        if (Schema::hasTable('expense_splits')) {
            Schema::table('expense_splits', function (Blueprint $table) {
                try {
                    $table->dropUnique(['expense_id', 'user_id', 'contact_id']);
                } catch (\Exception $e) {
                    // Constraint doesn't exist
                }

                $table->unique(['expense_id', 'user_id']);
            });

            // Note: We're NOT dropping contact_id column to preserve any data that may have been added
        }
    }
};
