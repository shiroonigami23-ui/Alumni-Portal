<?php
/**
 * Who's going to an event. Members only (it used to be readable by anyone on the internet).
 * Private accounts you don't follow and blocked people are counted but not named.
 * GET ?event_id=N
 */
require_once __DIR__ . '/_kernel.php';
require_once __DIR__ . '/_privacy.php';
require_once __DIR__ . '/_profile_media.php';
$api = api_endpoint(['access' => 'member', 'methods' => ['GET']]);   // who may call this — enforced before anything below

$eventId = $api->int('event_id', true);
$ev = $api->db->prepare("SELECT event_id FROM events WHERE event_id = :e");
$ev->execute([':e' => $eventId]);
if (!$ev->fetchColumn()) {
    api_fail(404, 'Event not found.');
}

$st = $api->db->prepare("
    SELECT r.user_id, r.rsvp_status::text AS rsvp_status, p.full_name, p.profile_picture_url, p.branch, u.username,
           " . privacy_author_visible_sql('r.user_id') . " AND " . privacy_not_blocked_sql('r.user_id') . " AS visible
    FROM event_rsvps r
    JOIN users u ON u.user_id = r.user_id
    LEFT JOIN profiles p ON p.user_id = r.user_id
    WHERE r.event_id = :e
    ORDER BY r.created_at
");
$st->execute([':e' => $eventId, ':viewer_uid' => $api->uid, ':viewer_role' => $api->role]);

$people = [];
$hidden = 0;
foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if (!filter_var($row['visible'], FILTER_VALIDATE_BOOLEAN)) {
        $hidden++;
        continue;
    }
    $people[] = [
        'user_id' => (int)$row['user_id'],
        'username' => $row['username'],
        'full_name' => $row['full_name'] ?: 'RJIT member',
        'branch' => $row['branch'],
        'rsvp_status' => $row['rsvp_status'],
        'profile_picture_url' => resolve_profile_media_url($api->db, (int)$row['user_id'], $row['profile_picture_url'] ?? '', 'profile_picture_url', 'profile_avatar'),
    ];
}
api_ok($people, null, ['event_id' => $eventId, 'attendee_count' => count($people) + $hidden, 'hidden_count' => $hidden]);
