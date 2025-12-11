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
        if (Schema::hasTable('group_members')) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('group_members', 'contact_id')) {
                Schema::table('group_members', function (Blueprint $table) {
                    $table->foreignId('contact_id')->nullable()->constrained('contacts')->onDelete('cascade')->after('user_id');
                });
            }

            if (!Schema::hasColumn('group_members', 'family_count')) {
                Schema::table('group_members', function (Blueprint $table) {
                    $table->integer('family_count')->default(0)->after('role');
                });
            }

            // Now handle the unique constraint change
            // We need to drop the old constraint and add the new one
            // Drop foreign keys that depend on the unique constraint first
            try {
                DB::statement('ALTER TABLE group_members DROP FOREIGN KEY group_members_user_id_foreign');
            } catch (\Exception $e) {
                // Foreign key doesn't exist or already dropped
            }

            try {
                DB::statement('ALTER TABLE group_members DROP FOREIGN KEY group_members_group_id_foreign');
            } catch (\Exception $e) {
                // Foreign key doesn't exist or already dropped
            }

            // Now drop the old unique constraint
            try {
                DB::statement('ALTER TABLE group_members DROP INDEX group_members_group_id_user_id_unique');
            } catch (\Exception $e) {
                // Index doesn't exist
            }

            // Add back the foreign keys
            Schema::table('group_members', function (Blueprint $table) {
                $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });

            // Add the new unique constraint
            try {
                DB::statement('ALTER TABLE group_members ADD UNIQUE KEY group_members_group_id_user_id_contact_id_unique (group_id, user_id, contact_id)');
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
        if (Schema::hasTable('group_members')) {
            try {
                DB::statement('ALTER TABLE group_members DROP FOREIGN KEY group_members_user_id_foreign');
            } catch (\Exception $e) {
                // Already dropped
            }

            try {
                DB::statement('ALTER TABLE group_members DROP FOREIGN KEY group_members_group_id_foreign');
            } catch (\Exception $e) {
                // Already dropped
            }

            try {
                DB::statement('ALTER TABLE group_members DROP INDEX group_members_group_id_user_id_contact_id_unique');
            } catch (\Exception $e) {
                // Index doesn't exist
            }

            // Re-add original constraints
            Schema::table('group_members', function (Blueprint $table) {
                $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['group_id', 'user_id']);
            });
        }
    }
};
