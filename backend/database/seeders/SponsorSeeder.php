<?php

namespace Database\Seeders;

use App\Enums\SponsorType;
use App\Models\Event;
use App\Models\Sponsor;
use Illuminate\Database\Seeder;

class SponsorSeeder extends Seeder
{
    public function run(): void
    {
        $event = Event::where('slug', 'grand-alumni-reunion-2024')->first() ?? Event::first();

        if (! $event) {
            return;
        }

        if ($event->sponsors()->count() > 0) {
            return;
        }

        $sponsors = [
            ['name' => 'TechCorp Ltd.', 'sponsor_type' => SponsorType::Platinum->value, 'amount' => 100000],
            ['name' => 'Global Bank', 'sponsor_type' => SponsorType::Gold->value, 'amount' => 60000],
            ['name' => 'BuildRight Construction', 'sponsor_type' => SponsorType::Silver->value, 'amount' => 30000],
            ['name' => 'FreshFoods', 'sponsor_type' => SponsorType::Bronze->value, 'amount' => 15000],
            ['name' => 'CloudServe', 'sponsor_type' => SponsorType::Gold->value, 'amount' => 55000],
        ];

        foreach ($sponsors as $i => $data) {
            Sponsor::create([
                'event_id'     => $event->id,
                'name'         => $data['name'],
                'website'      => 'https://example.com',
                'amount'       => $data['amount'],
                'sponsor_type' => $data['sponsor_type'],
                'sort_order'   => $i,
                'is_active'    => true,
            ]);
        }
    }
}
