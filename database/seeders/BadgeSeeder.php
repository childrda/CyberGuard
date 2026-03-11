<?php

namespace Database\Seeders;

use App\Models\Badge;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    /**
     * Create example badges for each tenant: First Report, 5 Correct Reports, 30 Day No Click Streak.
     */
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            Badge::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'slug' => 'first-report',
                ],
                [
                    'name' => 'First Report',
                    'description' => 'Reported your first simulated phishing email correctly.',
                    'criteria_type' => 'first_report',
                    'criteria_config' => null,
                    'icon' => 'star',
                    'sort_order' => 10,
                    'active' => true,
                ]
            );

            Badge::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'slug' => '5-correct-reports',
                ],
                [
                    'name' => '5 Correct Reports',
                    'description' => 'Correctly reported 5 simulated phishing emails.',
                    'criteria_type' => 'reports_count',
                    'criteria_config' => ['min_reports' => 5],
                    'icon' => 'shield-check',
                    'sort_order' => 20,
                    'active' => true,
                ]
            );

            Badge::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'slug' => '30-day-no-click-streak',
                ],
                [
                    'name' => '30 Day No Click Streak',
                    'description' => 'Went 30 days without clicking a link in a simulated phishing email.',
                    'criteria_type' => 'no_click_streak_days',
                    'criteria_config' => ['days' => 30],
                    'icon' => 'calendar-check',
                    'sort_order' => 30,
                    'active' => true,
                ]
            );
        }
    }
}
