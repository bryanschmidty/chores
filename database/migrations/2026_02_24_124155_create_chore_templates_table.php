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
        Schema::create('chore_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('default_assignee_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('points');
            $table->string('recurrence_type');
            $table->unsignedSmallInteger('recurrence_interval')->nullable();
            $table->json('recurrence_weekdays')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_completed_at')->nullable();
            $table->timestamps();

            $table->index(['household_id', 'is_active']);
            $table->index('recurrence_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chore_templates');
    }
};
