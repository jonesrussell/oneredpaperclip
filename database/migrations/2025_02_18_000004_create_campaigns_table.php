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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status'); // draft, active, completed, paused
            $table->string('visibility'); // public, unlisted
            $table->string('title')->nullable();
            $table->text('story')->nullable();
            $table->foreignId('current_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignId('goal_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
