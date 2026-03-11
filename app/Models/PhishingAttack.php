<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PhishingAttack extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'category',
        'subject',
        'from_name',
        'from_email',
        'reply_to',
        'html_body',
        'text_body',
        'difficulty_rating',
        'times_sent',
        'times_clicked',
        'landing_page_type',
        'training_page_id',
        'tags',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function trainingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class, 'training_page_id');
    }

    public function assets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AttackAsset::class, 'attack_id');
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(PhishingCampaign::class, 'campaign_attack');
    }

    public function messages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PhishingMessage::class, 'attack_id');
    }

    public function difficultyLabel(): string
    {
        return match (true) {
            $this->difficulty_rating <= 1 => 'Obvious',
            $this->difficulty_rating == 2 => 'Easy to spot',
            $this->difficulty_rating == 3 => 'Moderate',
            $this->difficulty_rating == 4 => 'Convincing',
            default => 'Very realistic',
        };
    }
}
