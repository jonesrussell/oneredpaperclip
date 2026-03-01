<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('notifications', 'notifications_legacy');

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        DB::table('notifications_legacy')->orderBy('id')->chunk(100, function ($legacyNotifications) {
            $inserts = [];
            foreach ($legacyNotifications as $legacy) {
                $inserts[] = [
                    'id' => Str::uuid()->toString(),
                    'type' => $this->mapNotificationType($legacy->type),
                    'notifiable_type' => User::class,
                    'notifiable_id' => $legacy->user_id,
                    'data' => $legacy->data,
                    'read_at' => $legacy->read_at,
                    'created_at' => $legacy->created_at,
                    'updated_at' => $legacy->updated_at,
                ];
            }
            if (! empty($inserts)) {
                DB::table('notifications')->insert($inserts);
            }
        });

        Schema::dropIfExists('notifications_legacy');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('read_at');
        });
    }

    /**
     * Map old notification types to new class names.
     */
    private function mapNotificationType(string $oldType): string
    {
        return match ($oldType) {
            'offer_accepted' => 'App\\Notifications\\OfferAcceptedNotification',
            'offer_declined' => 'App\\Notifications\\OfferDeclinedNotification',
            'offer_received' => 'App\\Notifications\\OfferReceivedNotification',
            default => 'App\\Notifications\\'.Str::studly($oldType).'Notification',
        };
    }
};
