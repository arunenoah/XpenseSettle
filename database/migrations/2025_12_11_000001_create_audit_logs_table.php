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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->string('action'); // create_group, add_member, create_expense, mark_paid, login, etc.
            $table->string('entity_type'); // Group, GroupMember, Expense, Payment, User, etc.
            $table->unsignedBigInteger('entity_id')->nullable(); // ID of the affected entity
            $table->text('description'); // Human-readable description
            $table->json('changes')->nullable(); // Old and new values for updates
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('status')->default('success'); // success, failed
            $table->text('error_message')->nullable(); // If status is failed
            $table->timestamps();

            // Indexes for fast querying
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
            $table->index('group_id');
            $table->index('user_id');
            $table->index('action');
            $table->index('entity_type');
            $table->index(['group_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
