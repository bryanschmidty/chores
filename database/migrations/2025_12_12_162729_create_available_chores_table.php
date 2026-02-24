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
        Schema::create('available_chores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chore_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_id')->constrained()->onDelete('cascade');
            $table->date('available_date');
            $table->timestamps();

            $table->index(['family_id', 'available_date']);
            $table->index(['chore_id', 'available_date']);
            $table->unique(['chore_id', 'family_id', 'available_date'], 'unique_chore_family_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('available_chores');
    }
};
