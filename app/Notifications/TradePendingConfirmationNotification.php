<?php

namespace App\Notifications;

use App\Models\Trade;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TradePendingConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Trade $trade,
        public User $confirmedBy
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable instanceof User && $notifiable->wantsNotification('trade_pending_confirmation', 'database')) {
            $channels[] = 'database';
        }

        if ($notifiable instanceof User && $notifiable->wantsNotification('trade_pending_confirmation', 'email')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $challenge = $this->trade->challenge;

        return (new MailMessage)
            ->subject('Trade Awaiting Your Confirmation')
            ->greeting('Hi '.$notifiable->name.'!')
            ->line($this->confirmedBy->name.' has confirmed their side of the trade for "'.$challenge->title.'".')
            ->line('Now it\'s your turn to confirm and complete the trade!')
            ->action('Confirm Trade', url('/challenges/'.$challenge->id.'/trades/'.$this->trade->id))
            ->line('The trade will be completed once both parties confirm.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'trade_id' => $this->trade->id,
            'challenge_id' => $this->trade->challenge_id,
            'challenge_title' => $this->trade->challenge->title,
            'confirmed_by_user_id' => $this->confirmedBy->id,
            'confirmed_by_user_name' => $this->confirmedBy->name,
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return 'trade_pending_confirmation';
    }
}
