<?php

namespace Database\Factories;

use App\Models\PhishingTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhishingTemplateFactory extends Factory
{
    protected $model = PhishingTemplate::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'subject' => fake()->sentence(),
            'html_body' => '<p>'.fake()->paragraph().'</p>',
            'text_body' => fake()->paragraph(),
            'landing_page_type' => 'training',
            'difficulty' => 'medium',
            'active' => true,
        ];
    }
}
