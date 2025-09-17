<?php
/**
 * Google OAuth Configuration
 * Move sensitive information to environment variables in production
 */

// Google OAuth Configuration
return [
    'client_id' => getenv('GOOGLE_CLIENT_ID') ?: '360629707474-ukdddf6ls2o4umfairg27i40k1erae3b.apps.googleusercontent.com',
    'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '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',
    'redirect_uri' => getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost/google-callback.php',

    // OAuth Settings
    'scopes' => ['email', 'profile'],
    'access_type' => 'offline',
    'prompt' => 'select_account',
    'include_granted_scopes' => true,

    // Security Settings
    'state_length' => 32, // Length of CSRF state token

    // Session Settings
    'session_keys' => [
        'oauth_state' => 'oauth_state',
        'oauth_provider' => 'oauth_provider',
        'user_id' => 'user_id',
        'username' => 'username',
        'email' => 'email',
        'user_type' => 'user_type',
        'profile_picture' => 'profile_picture',
        'login_method' => 'login_method'
    ]
];
