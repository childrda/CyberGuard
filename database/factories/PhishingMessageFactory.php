<?php

namespace Database\Factories;

use App\Models\PhishingCampaign;
use App\Models\PhishingMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhishingMessageFactory extends Factory
{
    protected $model = PhishingMessage::class;

    public function definition(): array
    {
        return [
            'campaign_id' => PhishingCampaign::factory(),
            'recipient_email' => fake()->safeEmail(),
            'tracking_token' => fake()->unique()->regexify('[a-zA-Z0-9]{64}'),
            'status' => 'queued',
        ];
    }
}
