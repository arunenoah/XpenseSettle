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
        if (Schema::hasTable('group_members')) {
            Schema::table('group_members', function (Blueprint $table) {
                // Make user_id nullable if it isn't already
                if (!Schema::hasColumn('group_members', 'contact_id')) {
                    $table->foreignId('contact_id')->nullable()->constrained('contacts')->onDelete('cascade')->after('user_id');
                }
                if (!Schema::hasColumn('group_members', 'family_count')) {
                    $table->integer('family_count')->default(0)->after('role');
                }
            });

            // Drop old unique constraint and add new one
            Schema::table('group_members', function (Blueprint $table) {
                // Drop old constraint if it exists
                try {
                    $table->dropUnique(['group_id', 'user_id']);
                } catch (\Exception $e) {
                    // Constraint doesn't exist, continue
                }
            });

            Schema::table('group_members', function (Blueprint $table) {
                // Add new unique constraint
                $table->unique(['group_id', 'user_id', 'contact_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop the table, just reverse the changes
        if (Schema::hasTable('group_members')) {
            Schema::table('group_members', function (Blueprint $table) {
                try {
                    $table->dropUnique(['group_id', 'user_id', 'contact_id']);
                } catch (\Exception $e) {
                    // Constraint doesn't exist
                }

                $table->unique(['group_id', 'user_id']);
            });

            // Note: We're NOT dropping contact_id and family_count columns
            // to preserve any data that may have been added
        }
    }
};
