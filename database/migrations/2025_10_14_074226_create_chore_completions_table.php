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
        Schema::create('chore_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chore_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('completed_at');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('verification_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->integer('completion_percentage')->default(100); // 0-100
            $table->string('before_photo')->nullable();
            $table->string('after_photo')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chore_completions');
    }
};
