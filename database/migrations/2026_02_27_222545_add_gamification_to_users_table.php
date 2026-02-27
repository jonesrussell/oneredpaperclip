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
            $table->unsignedInteger('xp')->default(0)->after('is_admin');
            $table->unsignedSmallInteger('level')->default(1)->after('xp');
            $table->unsignedSmallInteger('current_streak')->default(0)->after('level');
            $table->unsignedSmallInteger('longest_streak')->default(0)->after('current_streak');
            $table->timestamp('last_activity_at')->nullable()->after('longest_streak');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['xp', 'level', 'current_streak', 'longest_streak', 'last_activity_at']);
        });
    }
};
