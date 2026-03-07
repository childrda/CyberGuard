<?php

namespace App\Providers;

use App\Models\PhishingCampaign;
use App\Models\PhishingTemplate;
use App\Models\ReportedMessage;
use App\Policies\PhishingCampaignPolicy;
use App\Policies\PhishingTemplatePolicy;
use App\Policies\ReportedMessagePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        PhishingCampaign::class => PhishingCampaignPolicy::class,
        PhishingTemplate::class => PhishingTemplatePolicy::class,
        ReportedMessage::class => ReportedMessagePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
