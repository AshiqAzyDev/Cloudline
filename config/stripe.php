<?php

return [

    'key' => env('STRIPE_KEY'),

    'secret' => env('STRIPE_SECRET'),

    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    'ui_mode' => env('STRIPE_UI_MODE', 'embedded_page'),

];
