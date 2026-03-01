<?php

namespace App\Notifications;

use App\Models\Offer;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OfferAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Offer $offer,
        public Trade $trade
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable instanceof User && $notifiable->wantsNotification('offer_accepted', 'database')) {
            $channels[] = 'database';
        }

        if ($notifiable instanceof User && $notifiable->wantsNotification('offer_accepted', 'email')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $challenge = $this->offer->challenge;

        return (new MailMessage)
            ->subject('Your Offer Was Accepted!')
            ->greeting('Great news, '.$notifiable->name.'!')
            ->line('Your offer on "'.($challenge?->title ?? 'Unknown').'" has been accepted!')
            ->line('The trade is now pending confirmation. Confirm your side to complete it!')
            ->action('Confirm Trade', url('/challenges/'.$challenge->id.'/trades/'.$this->trade->id))
            ->line('Complete the trade to help advance the challenge!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'offer_id' => $this->offer->id,
            'trade_id' => $this->trade->id,
            'challenge_id' => $this->offer->challenge_id,
            'challenge_title' => $this->offer->challenge?->title ?? 'Unknown',
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return 'offer_accepted';
    }
}
