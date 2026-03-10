<?php

namespace Database\Factories;

use App\Models\PhishingCampaign;
use App\Models\PhishingTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhishingCampaignFactory extends Factory
{
    protected $model = PhishingCampaign::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'template_id' => PhishingTemplate::factory(),
            'status' => 'draft',
        ];
    }
}
