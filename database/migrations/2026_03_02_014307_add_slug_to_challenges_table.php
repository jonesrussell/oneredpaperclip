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
        Schema::table('challenges', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('title');
        });

        // Backfill existing challenges with slugs
        $challenges = \App\Models\Challenge::withTrashed()->whereNull('slug')->get();
        foreach ($challenges as $challenge) {
            $baseSlug = \Illuminate\Support\Str::slug($challenge->title ?? 'challenge');
            if (empty($baseSlug)) {
                $baseSlug = 'challenge';
            }
            $slug = $baseSlug;
            $suffix = 2;
            while (\App\Models\Challenge::withTrashed()->where('slug', $slug)->where('id', '!=', $challenge->id)->exists()) {
                $slug = $baseSlug.'-'.$suffix;
                $suffix++;
            }
            $challenge->slug = $slug;
            $challenge->saveQuietly();
        }

        // Now make it non-nullable
        Schema::table('challenges', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
