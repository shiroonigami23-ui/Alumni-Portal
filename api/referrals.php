<?php
/**
 * Referral board.
 *
 * Alumni / faculty publish "I can refer you at <company>" offers; students and
 * alumni request a referral with a short pitch; the offer owner accepts,
 * marks as referred, or declines. Everyone gets notified along the way.
 *
 * GET  ?action=list[&q=&experience=&mine=1]     open offers (+ my own when mine=1)
 * GET  ?action=my_requests                      requests I have sent
 * GET  ?action=offer_requests&offer_id=ID       requests on my offer (owner/admin)
 * GET  ?action=stats                            counters for the header
 * POST {action:create_offer, company, roles, locations, experience, slots, notes, expires_at}
 * POST {action:update_offer, offer_id, status?, ...fields}
 * POST {action:request, offer_id, message, resume_url?}
 * POST {action:respond, request_id, status: accepted|referred|declined, note?}
 * POST {action:withdraw, request_id}
 */
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../config/DbCompat.php';
include_once '../middleware/Auth.php';
include_once __DIR__ . '/_referral_schema.php';
include_once __DIR__ . '/../helpers/Cache.php';

$db = (new Database())->getConnection();
$auth = new Auth($db);
$userId = (int)$auth->validateRequest();
ensure_referral_schema($db);

const REF_EXPERIENCE = ['any', 'intern', 'fresher', '0-2', '2-5', '5+'];
const REF_MAX_OPEN_OFFERS = 10;
const REF_MAX_PENDING_REQUESTS = 15;

function ref_out(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

function ref_fail(string $message, int $code = 400): void
{
    ref_out(['success' => false, 'message' => $message], $code);
}

function ref_str($v, int $max): string
{
    $v = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string)$v));
    return mb_substr($v, 0, $max);
}

function ref_notify(PDO $db, int $to, int $from, string $content): void
{
    foreach (['referral', 'general'] as $type) {
        try {
            $db->prepare("INSERT INTO notifications (user_id, notification_type, related_user_id, content) VALUES (:u, :t, :f, :c)")
               ->execute(['u' => $to, 't' => $type, 'f' => $from, 'c' => $content]);
            return;
        } catch (Throwable $e) {
            // Some installs restrict notification_type; retry with a generic type.
        }
    }
}

$me = $db->prepare("SELECT u.role, COALESCE(p.full_name, u.email) AS name FROM users u LEFT JOIN profiles p ON p.user_id = u.user_id WHERE u.user_id = :id");
$me->execute(['id' => $userId]);
$meRow = $me->fetch(PDO::FETCH_ASSOC) ?: ['role' => 'student', 'name' => 'Someone'];
$role = (string)$meRow['role'];
$isAdmin = $role === 'admin';
$canOffer = in_array($role, ['alumni', 'faculty', 'admin'], true);
$canRequest = in_array($role, ['student', 'alumni'], true);
$today = date('Y-m-d');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'POST') {
    register_shutdown_function(fn () => http_response_code() < 400 && Cache::bump('referrals'));
}
$body = $method === 'POST' ? (json_decode((string)file_get_contents('php://input'), true) ?: $_POST) : [];
$action = (string)($method === 'POST' ? ($body['action'] ?? '') : ($_GET['action'] ?? 'list'));

$offerSelect = "
    SELECT o.offer_id, o.owner_id, o.company, o.roles, o.locations, o.experience, o.slots, o.notes,
           o.status, o.expires_at, o.created_at,
           COALESCE(p.full_name, 'RJIT alumnus') AS owner_name, p.job_role AS owner_role, p.graduation_year AS owner_batch,
           (SELECT COUNT(*) FROM referral_requests r WHERE r.offer_id = o.offer_id AND r.status IN ('accepted','referred')) AS filled,
           (SELECT COUNT(*) FROM referral_requests r WHERE r.offer_id = o.offer_id AND r.status = 'pending') AS pending,
           (SELECT r.status FROM referral_requests r WHERE r.offer_id = o.offer_id AND r.requester_id = :me) AS my_status
    FROM referral_offers o
    LEFT JOIN profiles p ON p.user_id = o.owner_id
";

try {
    switch ($action) {
        case 'list': {
            $where = ["o.status <> 'removed'"];
            $params = ['me' => $userId];
            if (!empty($_GET['mine'])) {
                $where[] = 'o.owner_id = :owner';
                $params['owner'] = $userId;
            } else {
                $where[] = "o.status = 'open'";
                $where[] = '(o.expires_at IS NULL OR o.expires_at >= :today)';
                $params['today'] = $today;
            }
            $q = ref_str($_GET['q'] ?? '', 80);
            if ($q !== '') {
                $where[] = "(LOWER(o.company) LIKE LOWER(:q) OR LOWER(o.roles) LIKE LOWER(:q2) OR LOWER(COALESCE(o.locations,'')) LIKE LOWER(:q3))";
                $params['q'] = $params['q2'] = $params['q3'] = '%' . $q . '%';
            }
            $exp = (string)($_GET['experience'] ?? '');
            if ($exp !== '' && in_array($exp, REF_EXPERIENCE, true)) {
                $where[] = "(o.experience = :exp OR o.experience = 'any')";
                $params['exp'] = $exp;
            }
            $stmt = $db->prepare($offerSelect . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY o.created_at DESC LIMIT 200');
            $stmt->execute($params);
            $offers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($offers as &$o) {
                $o['offer_id'] = (int)$o['offer_id'];
                $o['owner_id'] = (int)$o['owner_id'];
                $o['slots'] = (int)$o['slots'];
                $o['filled'] = (int)$o['filled'];
                $o['pending'] = (int)$o['pending'];
                $o['is_mine'] = $o['owner_id'] === $userId;
            }
            unset($o);
            ref_out(['success' => true, 'data' => $offers, 'permissions' => ['can_offer' => $canOffer, 'can_request' => $canRequest, 'is_admin' => $isAdmin]]);
        }

        case 'stats': {
            [$open, $companies, $referred] = Cache::remember(Cache::key('referrals', 'stats'), 60, fn () => [
                (int)$db->query("SELECT COUNT(*) FROM referral_offers WHERE status = 'open'")->fetchColumn(),
                (int)$db->query("SELECT COUNT(DISTINCT LOWER(company)) FROM referral_offers WHERE status = 'open'")->fetchColumn(),
                (int)$db->query("SELECT COUNT(*) FROM referral_requests WHERE status = 'referred'")->fetchColumn(),
            ]);
            $mine = $db->prepare("SELECT COUNT(*) FROM referral_requests WHERE requester_id = :u AND status IN ('pending','accepted')");
            $mine->execute(['u' => $userId]);
            $inbox = $db->prepare("SELECT COUNT(*) FROM referral_requests r JOIN referral_offers o ON o.offer_id = r.offer_id WHERE o.owner_id = :u AND r.status = 'pending'");
            $inbox->execute(['u' => $userId]);
            ref_out(['success' => true, 'data' => [
                'open_offers' => $open, 'companies' => $companies, 'referred' => $referred,
                'my_active_requests' => (int)$mine->fetchColumn(), 'awaiting_my_review' => (int)$inbox->fetchColumn(),
            ]]);
        }

        case 'my_requests': {
            $stmt = $db->prepare("
                SELECT r.request_id, r.offer_id, r.message, r.resume_url, r.status, r.owner_note, r.created_at, r.updated_at,
                       o.company, o.roles, o.owner_id, COALESCE(p.full_name, 'RJIT alumnus') AS owner_name
                FROM referral_requests r
                JOIN referral_offers o ON o.offer_id = r.offer_id
                LEFT JOIN profiles p ON p.user_id = o.owner_id
                WHERE r.requester_id = :u
                ORDER BY r.updated_at DESC LIMIT 100
            ");
            $stmt->execute(['u' => $userId]);
            ref_out(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }

        case 'offer_requests': {
            $offerId = (int)($_GET['offer_id'] ?? 0);
            $o = $db->prepare("SELECT owner_id FROM referral_offers WHERE offer_id = :id");
            $o->execute(['id' => $offerId]);
            $owner = $o->fetchColumn();
            if ($owner === false) {
                ref_fail('That referral offer no longer exists.', 404);
            }
            if ((int)$owner !== $userId && !$isAdmin) {
                ref_fail('Only the person offering this referral can see its requests.', 403);
            }
            $stmt = $db->prepare("
                SELECT r.request_id, r.requester_id, r.message, r.resume_url, r.status, r.owner_note, r.created_at,
                       COALESCE(p.full_name, u.email) AS requester_name, u.role AS requester_role,
                       p.branch, p.graduation_year, p.current_company, p.linkedin_url, p.github_url
                FROM referral_requests r
                JOIN users u ON u.user_id = r.requester_id
                LEFT JOIN profiles p ON p.user_id = r.requester_id
                WHERE r.offer_id = :id
                ORDER BY CASE r.status WHEN 'pending' THEN 0 WHEN 'accepted' THEN 1 ELSE 2 END, r.created_at ASC
            ");
            $stmt->execute(['id' => $offerId]);
            ref_out(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }

        case 'create_offer': {
            if (!$canOffer) {
                ref_fail('Only alumni and faculty can offer referrals. Students can request them!', 403);
            }
            $count = $db->prepare("SELECT COUNT(*) FROM referral_offers WHERE owner_id = :u AND status = 'open'");
            $count->execute(['u' => $userId]);
            if ((int)$count->fetchColumn() >= REF_MAX_OPEN_OFFERS) {
                ref_fail('You already have ' . REF_MAX_OPEN_OFFERS . ' open offers. Close one to add another.');
            }
            $company = ref_str($body['company'] ?? '', 160);
            $roles = ref_str($body['roles'] ?? '', 400);
            if (mb_strlen($company) < 2 || mb_strlen($roles) < 2) {
                ref_fail('Please add the company and the roles you can refer for.');
            }
            $exp = in_array($body['experience'] ?? 'any', REF_EXPERIENCE, true) ? $body['experience'] : 'any';
            $slots = max(1, min(50, (int)($body['slots'] ?? 3)));
            $expires = (string)($body['expires_at'] ?? '');
            $expires = preg_match('/^\d{4}-\d{2}-\d{2}$/', $expires) && $expires >= $today ? $expires : null;
            $stmt = $db->prepare("
                INSERT INTO referral_offers (owner_id, company, roles, locations, experience, slots, notes, expires_at)
                VALUES (:o, :c, :r, :l, :e, :s, :n, :x)
            ");
            $stmt->execute([
                'o' => $userId, 'c' => $company, 'r' => $roles, 'l' => ref_str($body['locations'] ?? '', 255) ?: null,
                'e' => $exp, 's' => $slots, 'n' => ref_str($body['notes'] ?? '', 2000) ?: null, 'x' => $expires,
            ]);
            $auth->logAction($userId, 'REFERRAL_OFFER_CREATE', "Offer at $company");
            ref_out(['success' => true, 'message' => "Your referral offer for $company is live 🎉"], 201);
        }

        case 'update_offer': {
            $offerId = (int)($body['offer_id'] ?? 0);
            $o = $db->prepare("SELECT owner_id, status FROM referral_offers WHERE offer_id = :id");
            $o->execute(['id' => $offerId]);
            $offer = $o->fetch(PDO::FETCH_ASSOC);
            if (!$offer || $offer['status'] === 'removed') {
                ref_fail('That referral offer no longer exists.', 404);
            }
            $isOwner = (int)$offer['owner_id'] === $userId;
            if (!$isOwner && !$isAdmin) {
                ref_fail('You can only edit your own offers.', 403);
            }
            $sets = [];
            $params = ['id' => $offerId];
            if (isset($body['status'])) {
                $allowed = $isAdmin ? ['open', 'paused', 'closed', 'removed'] : ['open', 'paused', 'closed'];
                if (!in_array($body['status'], $allowed, true)) {
                    ref_fail('Unknown status.');
                }
                $sets[] = 'status = :status';
                $params['status'] = $body['status'];
            }
            foreach (['company' => 160, 'roles' => 400, 'locations' => 255, 'notes' => 2000] as $field => $max) {
                if (array_key_exists($field, $body)) {
                    $sets[] = "$field = :$field";
                    $params[$field] = ref_str($body[$field], $max);
                }
            }
            if (isset($body['slots'])) {
                $sets[] = 'slots = :slots';
                $params['slots'] = max(1, min(50, (int)$body['slots']));
            }
            if (!$sets) {
                ref_fail('Nothing to update.');
            }
            $sets[] = 'updated_at = CURRENT_TIMESTAMP';
            $db->prepare('UPDATE referral_offers SET ' . implode(', ', $sets) . ' WHERE offer_id = :id')->execute($params);
            if ($isAdmin && !$isOwner && ($body['status'] ?? '') === 'removed') {
                ref_notify($db, (int)$offer['owner_id'], $userId, 'An admin removed one of your referral offers for breaking the community guidelines.');
                $auth->logAction($userId, 'REFERRAL_OFFER_REMOVE', "Removed offer #$offerId");
            }
            ref_out(['success' => true, 'message' => 'Offer updated.']);
        }

        case 'request': {
            if (!$canRequest) {
                ref_fail('Referral requests are for students and alumni.', 403);
            }
            $offerId = (int)($body['offer_id'] ?? 0);
            $message = ref_str($body['message'] ?? '', 1200);
            $resume = ref_str($body['resume_url'] ?? '', 500);
            if (mb_strlen($message) < 30) {
                ref_fail('Tell them a little about yourself — at least 30 characters helps them refer you.');
            }
            if ($resume !== '' && !preg_match('#^https://[^\s<>"]+$#i', $resume)) {
                ref_fail('Résumé / portfolio link must start with https://');
            }
            $o = $db->prepare("SELECT owner_id, company, status, expires_at FROM referral_offers WHERE offer_id = :id");
            $o->execute(['id' => $offerId]);
            $offer = $o->fetch(PDO::FETCH_ASSOC);
            if (!$offer || $offer['status'] !== 'open' || ($offer['expires_at'] && $offer['expires_at'] < $today)) {
                ref_fail('This offer is no longer accepting requests.', 409);
            }
            if ((int)$offer['owner_id'] === $userId) {
                ref_fail("You can't request your own referral 🙂");
            }
            $pending = $db->prepare("SELECT COUNT(*) FROM referral_requests WHERE requester_id = :u AND status = 'pending'");
            $pending->execute(['u' => $userId]);
            if ((int)$pending->fetchColumn() >= REF_MAX_PENDING_REQUESTS) {
                ref_fail('You have ' . REF_MAX_PENDING_REQUESTS . ' requests waiting. Withdraw one or wait for replies first.');
            }
            $dupe = $db->prepare("SELECT request_id, status FROM referral_requests WHERE offer_id = :o AND requester_id = :u");
            $dupe->execute(['o' => $offerId, 'u' => $userId]);
            $existing = $dupe->fetch(PDO::FETCH_ASSOC);
            if ($existing && $existing['status'] !== 'withdrawn') {
                ref_fail('You already requested this referral.', 409);
            }
            if ($existing) {
                $db->prepare("UPDATE referral_requests SET message = :m, resume_url = :r, status = 'pending', owner_note = NULL, updated_at = CURRENT_TIMESTAMP WHERE request_id = :id")
                   ->execute(['m' => $message, 'r' => $resume ?: null, 'id' => $existing['request_id']]);
            } else {
                $db->prepare("INSERT INTO referral_requests (offer_id, requester_id, message, resume_url) VALUES (:o, :u, :m, :r)")
                   ->execute(['o' => $offerId, 'u' => $userId, 'm' => $message, 'r' => $resume ?: null]);
            }
            ref_notify($db, (int)$offer['owner_id'], $userId, $meRow['name'] . ' requested a referral at ' . $offer['company'] . '. Open the Referral board to review.');
            ref_out(['success' => true, 'message' => 'Request sent! You\'ll be notified when they respond.'], 201);
        }

        case 'respond': {
            $requestId = (int)($body['request_id'] ?? 0);
            $status = (string)($body['status'] ?? '');
            $note = ref_str($body['note'] ?? '', 500);
            $r = $db->prepare("
                SELECT r.requester_id, r.status, o.owner_id, o.company
                FROM referral_requests r JOIN referral_offers o ON o.offer_id = r.offer_id
                WHERE r.request_id = :id
            ");
            $r->execute(['id' => $requestId]);
            $req = $r->fetch(PDO::FETCH_ASSOC);
            if (!$req) {
                ref_fail('That request no longer exists.', 404);
            }
            if ((int)$req['owner_id'] !== $userId && !$isAdmin) {
                ref_fail('Only the person offering the referral can respond.', 403);
            }
            $transitions = ['pending' => ['accepted', 'declined', 'referred'], 'accepted' => ['referred', 'declined']];
            if (!in_array($status, $transitions[$req['status']] ?? [], true)) {
                ref_fail("This request is already {$req['status']}.", 409);
            }
            $db->prepare("UPDATE referral_requests SET status = :s, owner_note = :n, updated_at = CURRENT_TIMESTAMP WHERE request_id = :id")
               ->execute(['s' => $status, 'n' => $note ?: null, 'id' => $requestId]);
            $msg = [
                'accepted' => "accepted your referral request at {$req['company']} — they'll be in touch.",
                'referred' => "referred you at {$req['company']}! Watch your inbox for the company's email. 🎉",
                'declined' => "couldn't refer you at {$req['company']} this time. Keep going — try another offer!",
            ][$status];
            ref_notify($db, (int)$req['requester_id'], $userId, $meRow['name'] . ' ' . $msg . ($note ? " Note: $note" : ''));
            ref_out(['success' => true, 'message' => 'Updated — they have been notified.']);
        }

        case 'withdraw': {
            $requestId = (int)($body['request_id'] ?? 0);
            $stmt = $db->prepare("UPDATE referral_requests SET status = 'withdrawn', updated_at = CURRENT_TIMESTAMP WHERE request_id = :id AND requester_id = :u AND status IN ('pending','accepted')");
            $stmt->execute(['id' => $requestId, 'u' => $userId]);
            if ($stmt->rowCount() === 0) {
                ref_fail('Nothing to withdraw.', 404);
            }
            ref_out(['success' => true, 'message' => 'Request withdrawn.']);
        }

        default:
            ref_fail('Unknown action.', 404);
    }
} catch (Throwable $e) {
    error_log('referrals: ' . $e->getMessage());
    ref_fail('Something went wrong on our side. Please try again.', 500);
}
