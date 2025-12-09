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
        // Add plan fields to users table
        Schema::table('users', function (Blueprint $table) {
            $table->enum('plan', ['free', 'lifetime'])->default('free')->after('email');
            $table->timestamp('plan_expires_at')->nullable()->after('plan');
        });

        // Add plan fields to groups table
        Schema::table('groups', function (Blueprint $table) {
            $table->enum('plan', ['free', 'trip_pass'])->default('free')->after('currency');
            $table->timestamp('plan_expires_at')->nullable()->after('plan');
            $table->integer('ocr_scans_used')->default(0)->after('plan_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['plan', 'plan_expires_at']);
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['plan', 'plan_expires_at', 'ocr_scans_used']);
        });
    }
};
