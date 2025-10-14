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
            $table->foreignId('family_id')->after('id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('role', ['super-admin', 'admin', 'member'])->after('email')->default('member');
            $table->json('notification_preferences')->after('role')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['family_id']);
            $table->dropColumn(['family_id', 'role', 'notification_preferences']);
        });
    }
};
