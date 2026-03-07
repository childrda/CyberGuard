<?php

namespace Database\Seeders;

use App\Models\LandingPage;
use Illuminate\Database\Seeder;

class LandingPageSeeder extends Seeder
{
    public function run(): void
    {
        LandingPage::firstOrCreate(
            ['slug' => 'default-training'],
            [
                'name' => 'Default training',
                'type' => 'training',
                'html_content' => '<h1>This was a simulated phishing exercise</h1><p>You clicked a link in a training email. No real data was collected.</p><p>Learn to spot phishing: check sender address, hover links before clicking, and report suspicious messages using Report Phish.</p>',
                'active' => true,
            ]
        );
    }
}
