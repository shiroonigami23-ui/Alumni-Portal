<?php
/**
 * Route table — the single source of truth for public page URLs.
 *
 * Every page keeps the URL it always had (e.g. /feed.php) and also gets a
 * clean alias (/feed). Old, renamed and "guessable" URLs are listed in
 * `redirects` so nothing a user bookmarked or shared ever dead-ends.
 *
 * Keys are paths relative to the app base (no leading slash).
 */
return [
    // URL slug => file under the project root
    'pages' => [
        ''                => 'index.php',
        'index'           => 'index.php',

        // Core app
        'dashboard'       => 'pages/dashboard.php',
        'feed'            => 'pages/feed.php',
        'profile'         => 'pages/profile.php',
        'settings'        => 'pages/settings.php',
        'messages'        => 'pages/messages.php',
        'discovery'       => 'pages/discovery.php',
        'mentorship'      => 'pages/mentorship.php',
        'jobs'            => 'pages/jobs.php',
        'events'          => 'pages/events.php',
        'moderation'      => 'pages/moderation.php',
        'help'            => 'pages/help.php',
        'live'            => 'pages/live.php',
        'atlas'           => 'pages/atlas.php',
        'referrals'       => 'pages/referrals.php',

        // Auth
        'login'           => 'pages/auth/login.php',
        'register'        => 'pages/auth/register.php',
        'forgot-password' => 'pages/auth/forgot-password.php',
        'reset'           => 'pages/auth/reset.php',

        // Legal
        'terms'           => 'pages/legal/terms.php',
        'policy'          => 'pages/legal/policy.php',
        'conduct'         => 'pages/legal/conduct.php',
    ],

    // Old / alternate URL => canonical URL (301). Query strings are preserved.
    'redirects' => [
        'edit-profile'       => 'settings.php#profile',
        'home'               => 'index.php',
        'signin'             => 'login.php',
        'sign-in'            => 'login.php',
        'signup'             => 'register.php',
        'sign-up'            => 'register.php',
        'join'               => 'register.php',
        'forgot'             => 'forgot-password.php',
        'reset-password'     => 'reset.php',
        'directory'          => 'discovery.php',
        'alumni'             => 'discovery.php',
        'network'            => 'discovery.php',
        'search'             => 'discovery.php',
        'mentors'            => 'mentorship.php',
        'careers'            => 'jobs.php',
        'inbox'              => 'messages.php',
        'chat'               => 'messages.php',
        'privacy'            => 'policy.php',
        'privacy-policy'     => 'policy.php',
        'code-of-conduct'    => 'conduct.php',
        'tos'                => 'terms.php',
        'faq'                => 'help.php',
        'support'            => 'help.php',
        'admin'              => 'admin/dashboard.php',
        'insights'           => 'admin/insights.php',
        'map'                => 'atlas.php',
        'alumni-map'         => 'atlas.php',
        'batches'            => 'atlas.php',
        'referral'           => 'referrals.php',
        'refer'              => 'referrals.php',
        'referral-board'     => 'referrals.php',
        // Path that was hardcoded in an old offline page
        'alumni_portal/feed' => 'feed.php',
    ],

    // Directories whose .php files may be executed directly (by URL).
    // Files starting with "_" are internal includes and are never executable.
    'script_dirs' => ['api', 'admin'],
];
