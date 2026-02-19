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
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('position');
            $table->foreignId('offered_item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('received_item_id')->constrained('items')->cascadeOnDelete();
            $table->string('status'); // pending_confirmation, completed, disputed
            $table->timestamp('confirmed_by_offerer_at')->nullable();
            $table->timestamp('confirmed_by_owner_at')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'position']);
            $table->index('campaign_id');
            $table->index('offer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
