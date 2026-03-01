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
            $table->json('notification_preferences')->default(json_encode([
                'offer_received' => ['database' => true, 'email' => true],
                'offer_accepted' => ['database' => true, 'email' => true],
                'offer_declined' => ['database' => true, 'email' => false],
                'trade_pending_confirmation' => ['database' => true, 'email' => true],
                'trade_completed' => ['database' => true, 'email' => true],
                'challenge_completed' => ['database' => true, 'email' => true],
            ]));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notification_preferences');
        });
    }
};
