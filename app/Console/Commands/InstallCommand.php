<?php

namespace App\Console\Commands;

use App\Models\Rate;
use App\Models\User;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'app:install {--email=admin@giftcardbot.test} {--password=password}';

    protected $description = 'Create admin user and seed default rates';

    public function handle(): int
    {
        $email = $this->option('email');
        $password = $this->option('password');

        if (User::where('email', $email)->exists()) {
            $this->warn("Admin user '{$email}' already exists. Skipping.");

            return self::SUCCESS;
        }

        User::create([
            'name' => 'Admin',
            'email' => $email,
            'password' => $password,
        ]);

        $this->info("Admin user created: {$email} / {$password}");

        if (Rate::count() === 0) {
            collect([
                ['card_name' => 'Amazon', 'usd_ngn' => 0.85, 'gbp_ngn' => 1.08, 'eur_ngn' => 0.94],
                ['card_name' => 'Apple', 'usd_ngn' => 0.80, 'gbp_ngn' => 1.01, 'eur_ngn' => 0.89],
                ['card_name' => 'Google Play', 'usd_ngn' => 0.78, 'gbp_ngn' => 0.99, 'eur_ngn' => 0.87],
                ['card_name' => 'Steam', 'usd_ngn' => 0.82, 'gbp_ngn' => 1.04, 'eur_ngn' => 0.91],
                ['card_name' => 'eBay', 'usd_ngn' => 0.80, 'gbp_ngn' => 1.01, 'eur_ngn' => 0.89],
            ])->each(fn ($r) => Rate::create($r));

            $this->info('Default rates seeded.');
        }

        return self::SUCCESS;
    }
}
