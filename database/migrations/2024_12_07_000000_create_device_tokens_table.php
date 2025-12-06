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
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('token')->unique(); // FCM device token
            $table->string('device_name')->nullable(); // Device name (e.g., "Samsung S25 Ultra")
            $table->string('device_type')->default('android'); // android, ios, web
            $table->string('app_version')->nullable(); // App version
            $table->boolean('active')->default(true); // Is token still valid
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            // Foreign key
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Indexes
            $table->index(['user_id', 'active']);
            $table->index('device_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
