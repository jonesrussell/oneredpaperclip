<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailTestCommand extends Command
{
    protected $signature = 'mail:test {email : The address to send the test email to} {--force : Run even when not in production (for testing)}';

    protected $description = 'Send a test email (production only) to verify mail configuration';

    public function handle(): int
    {
        if (! app()->environment('production') && ! $this->option('force')) {
            $this->error('This command only runs in production. Deploy and run it on the production server.');

            return self::FAILURE;
        }

        $email = $this->argument('email');

        try {
            Mail::raw(
                'This is a test email from '.config('app.name').'. If you received this, mail is working.',
                function ($message) use ($email): void {
                    $message->to($email)
                        ->subject('['.config('app.name').'] Mail test');
                }
            );
            $this->info("Test email sent to {$email}. Check the inbox (and spam).");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to send test email: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
