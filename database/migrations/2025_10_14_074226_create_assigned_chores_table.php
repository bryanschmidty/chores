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
        Schema::create('assigned_chores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chore_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');
            $table->date('due_date');
            $table->date('week_start_date'); // Monday of the week
            $table->enum('status', ['pending', 'completed', 'overdue'])->default('pending');
            $table->timestamps();

            $table->index(['family_id', 'week_start_date']);
            $table->index(['assigned_to', 'week_start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assigned_chores');
    }
};










