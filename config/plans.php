<?php

/*
|--------------------------------------------------------------------------
| Subscription plans
|--------------------------------------------------------------------------
|
| Limits are enforced server-side by App\Services\UsageLimitService.
| `null` means unlimited. Prices are in USD minor units and are display
| values only: the payment provider is the source of truth for charges.
|
*/

return [
    'trial_days' => (int) env('PLAN_TRIAL_DAYS', 14),
    'trial_plan' => env('PLAN_TRIAL_PLAN', 'pro'),

    'plans' => [
        'free' => [
            'name' => 'Free',
            'monthly_minor' => 0,
            'yearly_minor' => 0,
            'description' => 'Try the full approval workflow on one project.',
            'limits' => [
                'active_projects' => 1,
                'change_requests_per_month' => 3,
                'seats' => 1,
            ],
            'features' => [
                'reminders' => false,
                'proof_packs' => false,
                'advanced_templates' => false,
                'remove_branding' => false,
                'analytics' => false,
                'team' => false,
            ],
            'highlights' => ['1 active project', '3 change requests / month', 'Branded client approval page', 'Basic audit trail'],
        ],
        'solo' => [
            'name' => 'Solo',
            'monthly_minor' => 1200,
            'yearly_minor' => 12000,
            'description' => 'For freelancers protecting their own time.',
            'limits' => [
                'active_projects' => 10,
                'change_requests_per_month' => null,
                'seats' => 1,
            ],
            'features' => [
                'reminders' => true,
                'proof_packs' => true,
                'advanced_templates' => false,
                'remove_branding' => false,
                'analytics' => false,
                'team' => false,
            ],
            'highlights' => ['10 active projects', 'Unlimited change requests', 'Automatic reminders', 'PDF Proof Packs'],
        ],
        'pro' => [
            'name' => 'Pro',
            'monthly_minor' => 2400,
            'yearly_minor' => 24000,
            'description' => 'For busy consultants and studios.',
            'limits' => [
                'active_projects' => null,
                'change_requests_per_month' => null,
                'seats' => 1,
            ],
            'features' => [
                'reminders' => true,
                'proof_packs' => true,
                'advanced_templates' => true,
                'remove_branding' => true,
                'analytics' => true,
                'team' => false,
            ],
            'highlights' => ['Unlimited projects', 'Advanced templates', 'Payment rules', 'Remove platform branding', 'Reports & analytics'],
        ],
        'agency' => [
            'name' => 'Agency',
            'monthly_minor' => 5900,
            'yearly_minor' => 59000,
            'description' => 'For teams that need a shared process.',
            'limits' => [
                'active_projects' => null,
                'change_requests_per_month' => null,
                'seats' => 5,
            ],
            'features' => [
                'reminders' => true,
                'proof_packs' => true,
                'advanced_templates' => true,
                'remove_branding' => true,
                'analytics' => true,
                'team' => true,
            ],
            'highlights' => ['5 seats included ($8 per extra seat)', 'Shared workspace', 'Role controls', 'Advanced branding & reporting', 'Priority support'],
        ],
    ],

    'extra_seat_monthly_minor' => 800,
];
