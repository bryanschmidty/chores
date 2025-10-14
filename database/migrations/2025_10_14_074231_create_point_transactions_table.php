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
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_id')->constrained()->onDelete('cascade');
            $table->integer('amount'); // positive for earned, negative for spent
            $table->enum('type', ['earned', 'spent', 'bonus', 'contributed', 'refunded']);
            $table->string('related_model_type')->nullable(); // ChoreCompletion, Redemption, etc.
            $table->unsignedBigInteger('related_model_id')->nullable();
            $table->string('description');
            $table->timestamps();
            
            $table->index(['related_model_type', 'related_model_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
    }
};
