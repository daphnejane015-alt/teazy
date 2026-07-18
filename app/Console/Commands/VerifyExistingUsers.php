<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class VerifyExistingUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:verify-existing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark all existing unverified users as email-verified';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = User::whereNull('email_verified_at')->update(['email_verified_at' => now()]);

        $this->info("Marked {$count} existing user(s) as email-verified.");

        return self::SUCCESS;
    }
}
