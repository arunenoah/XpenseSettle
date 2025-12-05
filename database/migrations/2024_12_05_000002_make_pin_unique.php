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
        // First, delete any users without a PIN (if any exist)
        DB::table('users')->whereNull('pin')->delete();
        
        Schema::table('users', function (Blueprint $table) {
            $table->string('pin', 6)->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['pin']);
            $table->string('pin', 6)->nullable()->change();
        });
    }
};
