<?php

namespace App\Notifications;

use App\Models\Offer;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OfferReceivedNotification extends Notification implements ShouldQueue
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

        if ($notifiable instanceof User && $notifiable->wantsNotification('offer_received', 'database')) {
            $channels[] = 'database';
        }

        if ($notifiable instanceof User && $notifiable->wantsNotification('offer_received', 'email')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $challenge = $this->offer->challenge;
        $offerer = $this->offer->fromUser;
        $offeredItem = $this->offer->offeredItem;

        return (new MailMessage)
            ->subject('New Offer on Your Challenge: '.$challenge->title)
            ->greeting('Hey '.$notifiable->name.'!')
            ->line($offerer->name.' has made an offer on your challenge "'.$challenge->title.'".')
            ->line('They\'re offering: **'.$offeredItem->title.'**')
            ->action('View Offer', url('/challenges/'.$challenge->id))
            ->line('Don\'t keep them waiting - check it out!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'offer_id' => $this->offer->id,
            'challenge_id' => $this->offer->challenge_id,
            'from_user_id' => $this->offer->from_user_id,
            'from_user_name' => $this->offer->fromUser->name,
            'offered_item_title' => $this->offer->offeredItem->title,
            'challenge_title' => $this->offer->challenge->title,
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return 'offer_received';
    }
}
