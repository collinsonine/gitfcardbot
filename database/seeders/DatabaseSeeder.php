<?php

namespace Database\Seeders;

use App\Models\Rate;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@giftcardbot.test',
            'password' => 'password',
        ]);

        collect([
            ['card_name' => 'Amazon', 'usd_ngn' => 0.85, 'gbp_ngn' => 1.08, 'eur_ngn' => 0.94],
            ['card_name' => 'Apple', 'usd_ngn' => 0.80, 'gbp_ngn' => 1.01, 'eur_ngn' => 0.89],
            ['card_name' => 'Google Play', 'usd_ngn' => 0.78, 'gbp_ngn' => 0.99, 'eur_ngn' => 0.87],
            ['card_name' => 'Steam', 'usd_ngn' => 0.82, 'gbp_ngn' => 1.04, 'eur_ngn' => 0.91],
            ['card_name' => 'eBay', 'usd_ngn' => 0.80, 'gbp_ngn' => 1.01, 'eur_ngn' => 0.89],
        ])->each(fn ($rate) => Rate::create($rate));

        collect([
            ['key' => 'company_name', 'value' => 'GiftCardBot'],
            ['key' => 'page_title', 'value' => 'GiftCardBot'],
            ['key' => 'logo_path', 'value' => null],
        ])->each(fn ($s) => Setting::create($s));
    }
}
