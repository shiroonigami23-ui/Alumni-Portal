<?php
/**
 * @handles (Twitter-style usernames), separate from the display name.
 *
 *  - users.username is unique case-insensitively (unique index on lower(username)) — the database itself
 *    guarantees no duplicates, even under concurrent sign-ups.
 *  - Every account gets one automatically: a BEFORE INSERT trigger assigns @user<ID>; once the profile has a
 *    real name the trigger on profiles upgrades it to a name-based handle (aarav_sharma, aarav_sharma2…).
 *    Handles are built from the name, never the email/roll number, so they don't leak contact details.
 *  - Emails are normalised to lower-case by trigger, with a unique index on lower(email): "A@x" and "a@x"
 *    can never become two accounts.
 *  - Changing your handle: 3–20 chars [a-z0-9_], starts with a letter, not reserved, 14-day cooldown.
 *    Old handles keep redirecting (and stay reserved for their previous owner) for 30 days.
 */

const USERNAME_MIN = 3;
const USERNAME_MAX = 20;
const USERNAME_COOLDOWN_DAYS = 14;
const USERNAME_HOLD_DAYS = 30;
const USERNAME_RESERVED = [
    'admin', 'administrator', 'root', 'system', 'support', 'help', 'helpdesk', 'staff', 'moderator', 'mod', 'mods',
    'rjit', 'rjitofficial', 'official', 'portal', 'alumni', 'student', 'students', 'faculty', 'teacher', 'principal',
    'director', 'hod', 'dean', 'registrar', 'placement', 'tpo', 'bsf', 'security', 'api', 'www', 'mail', 'email',
    'login', 'logout', 'register', 'signup', 'signin', 'settings', 'profile', 'feed', 'messages', 'jobs', 'events',
    'live', 'library', 'atlas', 'referrals', 'discovery', 'mentorship', 'dashboard', 'moderation', 'insights',
    'me', 'you', 'user', 'users', 'null', 'undefined', 'none', 'anonymous', 'everyone', 'here', 'all', 'claude',
    'test', 'demo', 'owner', 'team', 'about', 'terms', 'policy', 'privacy', 'conduct', 'search', 'explore', 'home',
];

function ensure_username_schema(PDO $db): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    $run = function () use ($db) {
        $reserved = "ARRAY['" . implode("','", USERNAME_RESERVED) . "']";
        $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS username VARCHAR(30) NULL");
        $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS username_changed_at TIMESTAMP NULL");
        $db->exec("CREATE TABLE IF NOT EXISTS username_history (
            history_id BIGSERIAL PRIMARY KEY,
            user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            username VARCHAR(30) NOT NULL,
            changed_at TIMESTAMP NOT NULL DEFAULT NOW()
        )");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_username_history_name ON username_history (lower(username), changed_at DESC)");

        // Emails: normalise + case-insensitive uniqueness (only if existing data allows it).
        $db->exec("
            CREATE OR REPLACE FUNCTION users_normalise_email() RETURNS trigger AS \$\$
            BEGIN
                NEW.email := lower(btrim(NEW.email));
                RETURN NEW;
            END \$\$ LANGUAGE plpgsql;
        ");
        $db->exec("DROP TRIGGER IF EXISTS trg_users_normalise_email ON users");
        $db->exec("CREATE TRIGGER trg_users_normalise_email BEFORE INSERT OR UPDATE OF email ON users
                   FOR EACH ROW EXECUTE FUNCTION users_normalise_email()");
        $dupEmails = (int)$db->query("SELECT COUNT(*) FROM (SELECT lower(email) FROM users GROUP BY lower(email) HAVING COUNT(*) > 1) d")->fetchColumn();
        if ($dupEmails === 0) {
            $db->exec("UPDATE users SET email = lower(btrim(email)) WHERE email <> lower(btrim(email))");
            $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS uq_users_email_lower ON users (lower(email))");
        } else {
            error_log("usernames: $dupEmails e-mail addresses differ only by case — merge them, then the lower(email) unique index is created automatically");
        }

        // Handle generator used by both triggers.
        $db->exec("
            CREATE OR REPLACE FUNCTION portal_make_username(p_base text, p_user_id bigint) RETURNS text AS \$\$
            DECLARE
                base text;
                cand text;
                n int := 1;
            BEGIN
                base := lower(coalesce(p_base, ''));
                base := translate(base, 'àáâãäåāèéêëēìíîïīòóôõöōùúûüūñçßýÿ', 'aaaaaaaeeeeeiiiiioooooouuuuuncsyy');
                base := regexp_replace(base, '[^a-z0-9]+', '_', 'g');
                base := btrim(regexp_replace(base, '_+', '_', 'g'), '_');
                IF base !~ '^[a-z]' THEN base := 'rjit_' || base; END IF;
                base := btrim(left(base, 16), '_');
                IF length(base) < 3 OR base = 'rjit' THEN base := 'member'; END IF;
                cand := base;
                WHILE cand = ANY($reserved)
                   OR EXISTS (SELECT 1 FROM users WHERE lower(username) = cand AND user_id <> p_user_id)
                   OR EXISTS (SELECT 1 FROM username_history h WHERE lower(h.username) = cand AND h.user_id <> p_user_id
                              AND h.changed_at > NOW() - INTERVAL '" . USERNAME_HOLD_DAYS . " days') LOOP
                    n := n + 1;
                    cand := base || CASE WHEN n < 100 THEN n::text ELSE (floor(random() * 90000) + 10000)::int::text END;
                END LOOP;
                RETURN cand;
            END \$\$ LANGUAGE plpgsql;
        ");
        $db->exec("
            CREATE OR REPLACE FUNCTION users_assign_username() RETURNS trigger AS \$\$
            BEGIN
                IF NEW.username IS NULL OR btrim(NEW.username) = '' THEN
                    NEW.username := 'user' || NEW.user_id::text;
                END IF;
                RETURN NEW;
            END \$\$ LANGUAGE plpgsql;
        ");
        $db->exec("DROP TRIGGER IF EXISTS trg_users_assign_username ON users");
        $db->exec("CREATE TRIGGER trg_users_assign_username BEFORE INSERT ON users FOR EACH ROW EXECUTE FUNCTION users_assign_username()");
        // When a profile gets its real name, upgrade an automatic @user<ID> handle to a name-based one.
        $db->exec("
            CREATE OR REPLACE FUNCTION profiles_upgrade_username() RETURNS trigger AS \$\$
            BEGIN
                IF NEW.full_name IS NOT NULL AND btrim(NEW.full_name) <> '' AND NEW.full_name !~* '^(user|alumni user|rjit member)( [0-9]+)?$' THEN
                    UPDATE users SET username = portal_make_username(NEW.full_name, NEW.user_id)
                    WHERE user_id = NEW.user_id AND username_changed_at IS NULL AND (username IS NULL OR username = 'user' || user_id::text);
                END IF;
                RETURN NEW;
            END \$\$ LANGUAGE plpgsql;
        ");
        $db->exec("DROP TRIGGER IF EXISTS trg_profiles_upgrade_username ON profiles");
        $db->exec("CREATE TRIGGER trg_profiles_upgrade_username AFTER INSERT OR UPDATE OF full_name ON profiles
                   FOR EACH ROW EXECUTE FUNCTION profiles_upgrade_username()");

        // Backfill existing accounts, one at a time so each sees the handles already taken.
        $rows = $db->query("SELECT u.user_id, p.full_name FROM users u LEFT JOIN profiles p ON p.user_id = u.user_id
                            WHERE u.username IS NULL OR btrim(u.username) = '' ORDER BY u.user_id")->fetchAll(PDO::FETCH_ASSOC);
        $upd = $db->prepare("UPDATE users SET username = portal_make_username(:n, :u) WHERE user_id = :u2");
        foreach ($rows as $r) {
            $name = trim((string)($r['full_name'] ?? ''));
            $upd->execute([':n' => $name !== '' ? $name : 'member', ':u' => (int)$r['user_id'], ':u2' => (int)$r['user_id']]);
        }
        $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS uq_users_username_lower ON users (lower(username))");
    };
    if (!function_exists('schema_once')) {
        @include_once __DIR__ . '/../helpers/Cache.php';
    }
    if (function_exists('schema_once')) {
        schema_once('username_schema', $run, __FILE__);
    } else {
        $run();
    }
}

/** Normalise user input: strips a leading @, lower-cases, trims. */
function username_normalise(string $raw): string
{
    return strtolower(ltrim(trim($raw), '@'));
}

/** null when valid, otherwise a friendly reason. */
function username_problem(string $u, string $role = ''): ?string
{
    if (strlen($u) < USERNAME_MIN || strlen($u) > USERNAME_MAX) {
        return 'Handles are ' . USERNAME_MIN . '–' . USERNAME_MAX . ' characters.';
    }
    if (!preg_match('/^[a-z][a-z0-9_]*$/', $u)) {
        return 'Use letters, numbers and underscores, starting with a letter.';
    }
    if (strpos($u, '__') !== false || substr($u, -1) === '_') {
        return 'No double underscores, and don\'t end with one.';
    }
    if (in_array($u, USERNAME_RESERVED, true)) {
        return 'That handle is reserved.';
    }
    if ($role !== 'admin' && preg_match('/(admin|moderator|official|support|rjit_?team|staff)/', $u)) {
        return 'Handles can\'t look like staff accounts.';
    }
    if (preg_match('/^user\d+$/', $u)) {
        return 'Pick something more personal than user + number.';
    }
    return null;
}

/** Is it free for $userId? (Taken by someone else, or recently released by someone else = not free.) */
function username_available(PDO $db, string $u, int $userId): bool
{
    $s = $db->prepare("SELECT 1 FROM users WHERE lower(username) = :u AND user_id <> :me
                       UNION ALL
                       SELECT 1 FROM username_history WHERE lower(username) = :u2 AND user_id <> :me2
                         AND changed_at > NOW() - (CAST(:days AS INTEGER) * INTERVAL '1 day')
                       LIMIT 1");
    $s->execute([':u' => $u, ':me' => $userId, ':u2' => $u, ':me2' => $userId, ':days' => USERNAME_HOLD_DAYS]);
    return !$s->fetchColumn();
}

/** Free alternatives for a taken/invalid handle. */
function username_suggestions(PDO $db, string $base, int $userId, int $count = 4): array
{
    $base = preg_replace('/[^a-z0-9_]/', '', strtolower($base)) ?: 'member';
    if (!preg_match('/^[a-z]/', $base)) {
        $base = 'rjit_' . $base;
    }
    $base = rtrim(substr($base, 0, 15), '_');
    $out = [];
    foreach ([$base . '_rjit', $base . date('y'), $base . '_' . mt_rand(10, 99), $base . mt_rand(100, 999), $base . '_' . mt_rand(1000, 9999)] as $c) {
        if (count($out) >= $count) {
            break;
        }
        $c = substr($c, 0, USERNAME_MAX);
        if (username_problem($c) === null && username_available($db, $c, $userId)) {
            $out[] = $c;
        }
    }
    return $out;
}

/** Resolve a handle (current, or one released in the last 30 days) to a user id. */
function username_resolve(PDO $db, string $raw): ?array
{
    $u = username_normalise($raw);
    if ($u === '' || !preg_match('/^[a-z0-9_]{1,30}$/', $u)) {
        return null;
    }
    $s = $db->prepare("SELECT user_id, username FROM users WHERE lower(username) = :u LIMIT 1");
    $s->execute([':u' => $u]);
    if ($row = $s->fetch(PDO::FETCH_ASSOC)) {
        return ['user_id' => (int)$row['user_id'], 'username' => $row['username'], 'redirected' => false];
    }
    $h = $db->prepare("SELECT h.user_id, u.username FROM username_history h JOIN users u ON u.user_id = h.user_id
                       WHERE lower(h.username) = :u AND h.changed_at > NOW() - (CAST(:days AS INTEGER) * INTERVAL '1 day')
                       ORDER BY h.changed_at DESC LIMIT 1");
    $h->execute([':u' => $u, ':days' => USERNAME_HOLD_DAYS]);
    if ($row = $h->fetch(PDO::FETCH_ASSOC)) {
        return ['user_id' => (int)$row['user_id'], 'username' => $row['username'], 'redirected' => true];
    }
    return null;
}
