<?php

namespace App\Notifications;

use App\Models\Offer;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OfferDeclinedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Offer $offer
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable instanceof User && $notifiable->wantsNotification('offer_declined', 'database')) {
            $channels[] = 'database';
        }

        if ($notifiable instanceof User && $notifiable->wantsNotification('offer_declined', 'email')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $challenge = $this->offer->challenge;

        return (new MailMessage)
            ->subject('Update on Your Offer')
            ->greeting('Hi '.$notifiable->name)
            ->line('Your offer on "'.$challenge->title.'" wasn\'t accepted this time.')
            ->line('Don\'t give up - there are plenty of other challenges waiting for your offers!')
            ->action('Explore Challenges', url('/challenges'))
            ->line('Keep trading!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'offer_id' => $this->offer->id,
            'challenge_id' => $this->offer->challenge_id,
            'challenge_title' => $this->offer->challenge->title,
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return 'offer_declined';
    }
}
