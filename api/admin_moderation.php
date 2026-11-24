<?php
/** Retired endpoint — kept so old links get a clear answer (410) instead of a 404. */
require_once __DIR__ . '/_kernel.php';
api_endpoint(['access' => 'public']);
api_retired('admin_user_actions.php', 'It had no role check; bans are admin-only and audited now.');
