<?php

namespace App\Notifications;

use App\Models\Challenge;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChallengeCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Challenge $challenge
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable instanceof User && $notifiable->wantsNotification('challenge_completed', 'database')) {
            $channels[] = 'database';
        }

        if ($notifiable instanceof User && $notifiable->wantsNotification('challenge_completed', 'email')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isOwner = $notifiable->id === $this->challenge->user_id;

        if ($isOwner) {
            return (new MailMessage)
                ->subject('You Did It! Challenge Completed!')
                ->greeting('Amazing, '.$notifiable->name.'!')
                ->line('You\'ve completed your challenge "'.$this->challenge->title.'"!')
                ->line('You successfully traded up to: **'.($this->challenge->goalItem?->title ?? 'Unknown').'**')
                ->line('Total trades: '.($this->challenge->trades_count ?? 0))
                ->action('View Your Achievement', url('/challenges/'.$this->challenge->id))
                ->line('Congratulations on this incredible journey!');
        }

        return (new MailMessage)
            ->subject('Challenge Completed: '.$this->challenge->title)
            ->greeting('Hi '.$notifiable->name.'!')
            ->line('A challenge you\'ve been following has been completed!')
            ->line('"'.$this->challenge->title.'" by '.($this->challenge->user?->name ?? 'Someone').' reached its goal!')
            ->line('Final item: **'.($this->challenge->goalItem?->title ?? 'Unknown').'**')
            ->action('See the Journey', url('/challenges/'.$this->challenge->id))
            ->line('What an inspiring trade-up story!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'challenge_id' => $this->challenge->id,
            'challenge_title' => $this->challenge->title,
            'owner_name' => $this->challenge->user?->name ?? 'Unknown',
            'goal_item_title' => $this->challenge->goalItem?->title ?? 'Unknown',
            'trades_count' => $this->challenge->trades_count,
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return 'challenge_completed';
    }
}
