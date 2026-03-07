<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhishingTemplate extends Model
{
    use HasFactory;
    use \App\Models\Concerns\BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'subject',
        'html_body',
        'text_body',
        'sender_name',
        'sender_email',
        'reply_to',
        'landing_page_type',
        'training_page_id',
        'difficulty',
        'tags',
        'active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function trainingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class, 'training_page_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(PhishingCampaign::class, 'template_id');
    }
}
