<?php

return [
    'version' => env('PACEKEEPER_APP_VERSION', 'v15'),
    'onboarding_version' => 1,
    'admin_email' => env('PACEKEEPER_ADMIN_EMAIL'),
    'feedback_admin_password' => env('FEEDBACK_ADMIN_PASSWORD', env('TEMPLATE_ADMIN_PASSWORD')),
];
