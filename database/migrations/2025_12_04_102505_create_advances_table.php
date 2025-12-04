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
        Schema::create('advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignId('sent_to_user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount_per_person', 10, 2);
            $table->date('date');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('advance_senders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advance_id')->constrained('advances')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advance_senders');
        Schema::dropIfExists('advances');
    }
};
