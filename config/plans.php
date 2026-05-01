<?php

declare(strict_types=1);

return [
    'free' => [
        'name' => 'Free',
        'price' => 0,
        'limits' => [
            'posts_per_month' => 5,
            'followers' => 50,
            'api_access' => false,
        ],
    ],
    'pro' => [
        'name' => 'Pro',
        'price_monthly' => 900, // cents
        'stripe_price_id' => env('STRIPE_PRICE_PRO_MONTHLY'),
        'limits' => [
            'posts_per_month' => null, // unlimited
            'followers' => null,
            'api_access' => true,
        ],
    ],
    'pro_annual' => [
        'name' => 'Pro Annual',
        'price_yearly' => 9000, // cents
        'stripe_price_id' => env('STRIPE_PRICE_PRO_ANNUAL'),
        'limits' => [
            'posts_per_month' => null,
            'followers' => null,
            'api_access' => true,
        ],
    ],
];
