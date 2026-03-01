<?php

namespace App\Notifications;

use App\Models\Trade;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TradeCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Trade $trade
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable instanceof User && $notifiable->wantsNotification('trade_completed', 'database')) {
            $channels[] = 'database';
        }

        if ($notifiable instanceof User && $notifiable->wantsNotification('trade_completed', 'email')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $challenge = $this->trade->challenge;
        $offeredItem = $this->trade->offeredItem;

        return (new MailMessage)
            ->subject('Trade Completed!')
            ->greeting('Congratulations, '.$notifiable->name.'!')
            ->line('The trade for "'.($challenge?->title ?? 'Unknown').'" is now complete!')
            ->line('The challenge has advanced to: **'.($offeredItem?->title ?? 'Unknown').'**')
            ->action('View Challenge Progress', url('/challenges/'.($challenge?->id ?? '')))
            ->line('Thanks for being part of this trade-up journey!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'trade_id' => $this->trade->id,
            'challenge_id' => $this->trade->challenge_id,
            'challenge_title' => $this->trade->challenge?->title ?? 'Unknown',
            'new_item_title' => $this->trade->offeredItem?->title ?? 'Unknown',
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return 'trade_completed';
    }
}
