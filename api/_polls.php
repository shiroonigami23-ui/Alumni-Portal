<?php
/**
 * Poll helpers shared by the feed, poll APIs and the post scheduler.
 * A poll belongs to one post; one vote per user; optional expiry.
 */

/** Create a poll for a post. Returns [poll_id, error]. Caller handles auth. */
function poll_create(PDO $db, int $postId, string $question, array $options, ?int $durationHours = null): array
{
    $question = trim(mb_substr(strip_tags($question), 0, 280));
    $clean = [];
    foreach ($options as $o) {
        $o = trim(mb_substr(strip_tags((string)$o), 0, 120));
        if ($o !== '' && !in_array(mb_strtolower($o), array_map('mb_strtolower', $clean), true)) {
            $clean[] = $o;
        }
    }
    if (mb_strlen($question) < 3) {
        return [0, 'Write a poll question (at least 3 characters).'];
    }
    if (count($clean) < 2 || count($clean) > 6) {
        return [0, 'A poll needs 2 to 6 different options.'];
    }
    $exists = $db->prepare("SELECT poll_id FROM polls WHERE post_id = :p LIMIT 1");
    $exists->execute(['p' => $postId]);
    if ($exists->fetchColumn()) {
        return [0, 'This post already has a poll.'];
    }
    $hours = ($durationHours !== null && $durationHours > 0) ? min(24 * 30, $durationHours) : null;
    $ownTx = !$db->inTransaction();
    if ($ownTx) {
        $db->beginTransaction();
    }
    try {
        $stmt = $db->prepare("INSERT INTO polls (post_id, question, expires_at, is_active)
                              VALUES (:p, :q, CASE WHEN CAST(:h AS INT) IS NULL THEN NULL ELSE NOW() + (CAST(:h2 AS INT) * INTERVAL '1 hour') END, TRUE)
                              RETURNING poll_id");
        $stmt->execute(['p' => $postId, 'q' => $question, 'h' => $hours, 'h2' => $hours]);
        $pollId = (int)$stmt->fetchColumn();
        $opt = $db->prepare("INSERT INTO poll_options (poll_id, option_text, option_order) VALUES (:p, :t, :o)");
        foreach ($clean as $i => $text) {
            $opt->execute(['p' => $pollId, 't' => $text, 'o' => $i + 1]);
        }
        $db->prepare("UPDATE posts SET poll_id = :poll, post_type = CASE WHEN post_type = 'text' THEN 'poll' ELSE post_type END WHERE post_id = :p")
           ->execute(['poll' => $pollId, 'p' => $postId]);
        if ($ownTx) {
            $db->commit();
        }
        return [$pollId, null];
    } catch (Throwable $e) {
        if ($ownTx && $db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
}

/** Polls for many posts at once: [post_id => poll]. */
function polls_for_posts(PDO $db, array $postIds, int $viewerId): array
{
    $postIds = array_values(array_unique(array_filter(array_map('intval', $postIds))));
    if (!$postIds) {
        return [];
    }
    $in = implode(',', $postIds); // ints only
    try {
        $polls = $db->query("SELECT poll_id, post_id, question, expires_at,
                                    (is_active AND (expires_at IS NULL OR expires_at > NOW())) AS is_open
                             FROM polls WHERE post_id IN ($in)")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
    if (!$polls) {
        return [];
    }
    $pollIds = implode(',', array_map(fn ($p) => (int)$p['poll_id'], $polls));
    $opts = $db->query("SELECT option_id, poll_id, option_text, vote_count FROM poll_options WHERE poll_id IN ($pollIds) ORDER BY option_order, option_id")->fetchAll(PDO::FETCH_ASSOC);
    $mine = $db->prepare("SELECT poll_id, option_id FROM poll_votes WHERE user_id = :u AND poll_id IN ($pollIds)");
    $mine->execute(['u' => $viewerId]);
    $myVotes = [];
    foreach ($mine->fetchAll(PDO::FETCH_ASSOC) as $v) {
        $myVotes[(int)$v['poll_id']] = (int)$v['option_id'];
    }
    $byPoll = [];
    foreach ($opts as $o) {
        $byPoll[(int)$o['poll_id']][] = ['option_id' => (int)$o['option_id'], 'text' => $o['option_text'], 'votes' => (int)$o['vote_count']];
    }
    $out = [];
    foreach ($polls as $p) {
        $pid = (int)$p['poll_id'];
        $options = $byPoll[$pid] ?? [];
        $out[(int)$p['post_id']] = [
            'poll_id' => $pid,
            'question' => $p['question'],
            'options' => $options,
            'total_votes' => array_sum(array_column($options, 'votes')),
            'my_vote' => $myVotes[$pid] ?? null,
            'expires_at' => $p['expires_at'],
            'is_open' => filter_var($p['is_open'], FILTER_VALIDATE_BOOLEAN),
        ];
    }
    return $out;
}
