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
        Schema::table('users', function (Blueprint $table) {
            // Change pin and admin_pin to support bcrypt hash (60 characters)
            $table->string('pin', 255)->change();
            $table->string('admin_pin', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert to original length
            $table->string('pin', 6)->change();
            $table->string('admin_pin', 6)->nullable()->change();
        });
    }
};
