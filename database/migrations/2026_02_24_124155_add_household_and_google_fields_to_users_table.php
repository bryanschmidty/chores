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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('household_id')
                ->nullable()
                ->after('id')
                ->constrained('households')
                ->nullOnDelete();
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('google_email')->nullable()->after('google_id');
            $table->string('google_avatar_url')->nullable()->after('google_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('household_id');
            $table->dropColumn(['google_id', 'google_email', 'google_avatar_url']);
        });
    }
};
