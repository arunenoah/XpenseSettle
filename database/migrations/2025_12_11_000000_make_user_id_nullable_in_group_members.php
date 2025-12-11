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
        // Check if table exists
        if (Schema::hasTable('group_members')) {
            // Drop the foreign key constraint for user_id first
            try {
                DB::statement('ALTER TABLE group_members DROP FOREIGN KEY group_members_user_id_foreign');
            } catch (\Exception $e) {
                // Foreign key might not exist or might have a different name
            }

            // Drop the old unique constraint if it exists
            try {
                DB::statement('ALTER TABLE group_members DROP INDEX group_members_group_id_user_id_unique');
            } catch (\Exception $e) {
                // Index might not exist
            }

            // Make user_id nullable
            Schema::table('group_members', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            });

            // Re-add the foreign key with proper handling for nulls
            Schema::table('group_members', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });

            // Add back unique constraint that allows nulls
            // In MySQL, NULL values are not considered equal, so multiple NULLs are allowed in unique index
            try {
                DB::statement('ALTER TABLE group_members ADD UNIQUE KEY group_members_group_id_user_id_unique (group_id, user_id)');
            } catch (\Exception $e) {
                // Index might already exist
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('group_members')) {
            // Drop foreign key
            try {
                DB::statement('ALTER TABLE group_members DROP FOREIGN KEY group_members_user_id_foreign');
            } catch (\Exception $e) {
                // Already dropped
            }

            // Drop unique constraint
            try {
                DB::statement('ALTER TABLE group_members DROP INDEX group_members_group_id_user_id_unique');
            } catch (\Exception $e) {
                // Already dropped
            }

            // Make user_id NOT NULL again
            Schema::table('group_members', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
            });

            // Re-add foreign key
            Schema::table('group_members', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });

            // Re-add unique constraint
            Schema::table('group_members', function (Blueprint $table) {
                $table->unique(['group_id', 'user_id']);
            });
        }
    }
};
