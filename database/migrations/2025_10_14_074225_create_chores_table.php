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
        Schema::create('chores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained('chore_templates')->onDelete('set null');
            $table->foreignId('assigned_to')->constrained('users')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('points')->default(10);
            $table->enum('recurrence_type', ['none', 'daily', 'weekly', 'monthly', 'custom'])->default('none');
            $table->integer('recurrence_interval')->nullable(); // for custom intervals
            $table->date('next_due_date');
            $table->boolean('requires_verification')->default(false);
            $table->enum('status', ['pending', 'completed', 'overdue'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chores');
    }
};
