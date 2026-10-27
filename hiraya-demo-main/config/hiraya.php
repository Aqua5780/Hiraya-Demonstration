<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Demo Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, one-time passwords and account activation links are shown
    | inside the application so the system can be demonstrated without an
    | SMTP server. Disable this in production.
    |
    */
    'demo_mode' => (bool) env('HIRAYA_DEMO_MODE', false),

    'school' => [
        'name' => env('HIRAYA_SCHOOL_NAME', 'Good Shepherd of Taguig Colleges, Inc.'),
        'short_name' => 'GSTC',
        'address' => env('HIRAYA_SCHOOL_ADDRESS', 'Central Bicutan, Taguig City'),
        'tagline' => env('HIRAYA_SCHOOL_TAGLINE', 'Faith. Wisdom. Triumph.'),
    ],

    'student_email_domain' => env('HIRAYA_STUDENT_EMAIL_DOMAIN', 'gstc.edu.ph'),

    'uploads' => [
        'announcement_max_kb' => 5120,
        'payment_proof_max_kb' => 5120,
        'student_import_max_kb' => 2048,
        'announcement_mimes' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
        'payment_proof_mimes' => ['pdf', 'jpg', 'jpeg', 'png'],
    ],

    'auth' => [
        'max_attempts' => 3,
        'lockout_minutes' => 5,
        'otp_length' => 6,
        'otp_expiry_minutes' => 10,
        'otp_max_attempts' => 5,
        'activation_expiry_days' => 7,
        'password_min_length' => 8,
    ],

    'grades' => [
        'midterm_weight' => 0.50,
        'final_weight' => 0.50,
        'passing_grade' => 75,
    ],

    'payments' => [
        'methods' => [
            'cash' => 'Cash',
            'gcash' => 'GCash',
            'bank_transfer' => 'Bank Transfer',
        ],
    ],

    'demo_accounts' => [
        ['role' => 'Administrator', 'email' => 'admin@hiraya.test', 'password' => 'Password123!'],
        ['role' => 'Registrar', 'email' => 'registrar@hiraya.test', 'password' => 'Password123!'],
        ['role' => 'Faculty', 'email' => 'faculty@hiraya.test', 'password' => 'Password123!'],
        ['role' => 'Student', 'email' => 'student@hiraya.test', 'password' => 'Password123!'],
        ['role' => 'Student (new)', 'email' => 'newstudent@hiraya.test', 'password' => 'Password123!'],
        ['role' => 'Finance', 'email' => 'finance@hiraya.test', 'password' => 'Password123!'],
    ],
];
