<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename table
        Schema::rename('campaigns', 'challenges');

        // Add soft deletes
        Schema::table('challenges', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Rename foreign key columns
        Schema::table('offers', function (Blueprint $table) {
            $table->renameColumn('campaign_id', 'challenge_id');
            $table->renameColumn('for_campaign_item_id', 'for_challenge_item_id');
        });

        Schema::table('trades', function (Blueprint $table) {
            $table->renameColumn('campaign_id', 'challenge_id');
        });

        // Update polymorphic types
        DB::table('items')
            ->where('itemable_type', 'App\\Models\\Campaign')
            ->update(['itemable_type' => 'App\\Models\\Challenge']);

        DB::table('comments')
            ->where('commentable_type', 'App\\Models\\Campaign')
            ->update(['commentable_type' => 'App\\Models\\Challenge']);
    }

    public function down(): void
    {
        // Revert polymorphic types
        DB::table('items')
            ->where('itemable_type', 'App\\Models\\Challenge')
            ->update(['itemable_type' => 'App\\Models\\Campaign']);

        DB::table('comments')
            ->where('commentable_type', 'App\\Models\\Challenge')
            ->update(['commentable_type' => 'App\\Models\\Campaign']);

        // Revert foreign key columns
        Schema::table('trades', function (Blueprint $table) {
            $table->renameColumn('challenge_id', 'campaign_id');
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->renameColumn('challenge_id', 'campaign_id');
            $table->renameColumn('for_challenge_item_id', 'for_campaign_item_id');
        });

        // Remove soft deletes
        Schema::table('challenges', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Rename table back
        Schema::rename('challenges', 'campaigns');
    }
};
