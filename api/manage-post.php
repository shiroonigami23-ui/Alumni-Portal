<?php
/** Retired endpoint — kept so old links get a clear answer (410) instead of a 404. */
require_once __DIR__ . '/_kernel.php';
api_endpoint(['access' => 'public']);
api_retired('edit_post.php', 'Delete with delete_post.php.');
