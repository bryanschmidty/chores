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
        Schema::table('chore_instances', function (Blueprint $table) {
            $table->foreign('chore_template_id')
                ->references('id')
                ->on('chore_templates')
                ->nullOnDelete();
        });

        Schema::table('chore_completion_participants', function (Blueprint $table) {
            $table->foreign('chore_completion_id')
                ->references('id')
                ->on('chore_completions')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chore_instances', function (Blueprint $table) {
            $table->dropForeign(['chore_template_id']);
        });

        Schema::table('chore_completion_participants', function (Blueprint $table) {
            $table->dropForeign(['chore_completion_id']);
        });
    }
};
