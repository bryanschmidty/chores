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
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('points')->default(10);
            $table->enum('frequency', ['daily', 'twice_weekly', 'weekly', 'twice_monthly', 'monthly', 'adhoc'])->default('weekly');
            $table->boolean('review_required')->default(false);
            $table->enum('photos_required', ['none', 'before_and_after', 'only_after'])->default('none');
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
