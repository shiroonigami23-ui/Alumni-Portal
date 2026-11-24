<?php
/**
 * Live sessions without a media server:
 *   - provider "jitsi":   a fresh Jitsi Meet room (great for AMAs, talks, office hours)
 *   - provider "youtube": embed a YouTube Live / video URL the host is streaming to
 * One live session per host. Sessions auto-expire after 8 hours.
 */
require_once __DIR__ . '/../config/DbCompat.php';
require_once __DIR__ . '/../helpers/Cache.php';

class Stream
{
    private PDO $conn;
    private string $table = 'live_streams';
    public const MAX_HOURS = 8;

    public function __construct($db)
    {
        $this->conn = $db;
        $this->ensureSchema();
    }

    private function ensureSchema(): void
    {
        $key = 'schema:live_streams:' . @filemtime(__FILE__) . ':' . (getenv('DB_NAME') ?: 'db');
        if (Cache::get($key)) {
            return;
        }
        $this->conn->exec("CREATE TABLE IF NOT EXISTS live_streams (
            stream_id BIGSERIAL PRIMARY KEY,
            user_id BIGINT NOT NULL UNIQUE REFERENCES users(user_id) ON DELETE CASCADE,
            stream_key VARCHAR(120) NOT NULL UNIQUE,
            title VARCHAR(255) NULL, description TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'offline', started_at TIMESTAMP NULL,
            created_at TIMESTAMP NOT NULL DEFAULT NOW())");
        foreach (["provider VARCHAR(20) NOT NULL DEFAULT 'jitsi'", "embed_url TEXT NULL", "watch_url TEXT NULL", "ended_at TIMESTAMP NULL"] as $col) {
            $this->conn->exec("ALTER TABLE live_streams ADD COLUMN IF NOT EXISTS $col");
        }
        Cache::set($key, 1, 86400);
    }

    /** Normalise a YouTube link to [embedUrl, watchUrl] or null. */
    public static function youtube(string $url): ?array
    {
        if (!preg_match('#^https://(www\.|m\.)?(youtube\.com|youtu\.be)/#i', $url)) {
            return null;
        }
        if (preg_match('#(?:v=|youtu\.be/|/live/|/embed/|/shorts/)([A-Za-z0-9_-]{11})#', $url, $m)) {
            return ['https://www.youtube-nocookie.com/embed/' . $m[1] . '?autoplay=1&rel=0', 'https://www.youtube.com/watch?v=' . $m[1]];
        }
        return null;
    }

    /** Start (or restart) the host's live session. Returns the session or [error]. */
    public function start(int $userId, string $title, string $description, string $provider = 'jitsi', string $url = ''): array
    {
        $title = trim(mb_substr(strip_tags($title), 0, 120));
        $description = trim(mb_substr(strip_tags($description), 0, 600));
        if (mb_strlen($title) < 3) {
            return ['error' => 'Give your live session a title.'];
        }
        if ($provider === 'youtube') {
            $yt = self::youtube(trim($url));
            if (!$yt) {
                return ['error' => 'Paste a YouTube Live or video link (https://youtube.com/…).'];
            }
            [$embed, $watch] = $yt;
        } else {
            $provider = 'jitsi';
            $room = 'RJIT-Live-' . strtoupper(bin2hex(random_bytes(5)));
            $embed = 'https://meet.jit.si/' . $room;
            $watch = $embed;
        }
        $params = ['u' => $userId, 't' => $title, 'd' => $description, 'p' => $provider, 'e' => $embed, 'w' => $watch];
        $upd = $this->conn->prepare("UPDATE {$this->table} SET status = 'live', title = :t, description = :d, provider = :p,
                                     embed_url = :e, watch_url = :w, started_at = NOW(), ended_at = NULL WHERE user_id = :u");
        $upd->execute($params);
        if ($upd->rowCount() === 0) {
            $params['k'] = 'live_' . $userId . '_' . bin2hex(random_bytes(8));
            $this->conn->prepare("INSERT INTO {$this->table} (user_id, stream_key, title, description, provider, embed_url, watch_url, status, started_at)
                                  VALUES (:u, :k, :t, :d, :p, :e, :w, 'live', NOW())")->execute($params);
        }
        Cache::bump('live');
        return $this->forUser($userId) ?? ['error' => 'Could not start the session.'];
    }

    public function end(int $userId): bool
    {
        $s = $this->conn->prepare("UPDATE {$this->table} SET status = 'offline', ended_at = NOW() WHERE user_id = :u AND status = 'live'");
        $s->execute(['u' => $userId]);
        Cache::bump('live');
        return $s->rowCount() > 0;
    }

    private const FIELDS = "s.stream_id, s.user_id, s.title, s.description, s.provider, s.embed_url, s.watch_url, s.started_at,
                            COALESCE(p.full_name, 'RJIT member') AS host_name, u.role AS host_role, p.current_company AS host_company";

    public function forUser(int $userId): ?array
    {
        $s = $this->conn->prepare("SELECT " . self::FIELDS . ", s.status FROM {$this->table} s JOIN users u ON u.user_id = s.user_id
                                   LEFT JOIN profiles p ON p.user_id = s.user_id WHERE s.user_id = :u");
        $s->execute(['u' => $userId]);
        return $s->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Live sessions (never exposes stream keys). Old sessions are closed automatically. */
    public function active(): array
    {
        return Cache::remember(Cache::key('live', 'active'), 15, function () {
            $this->conn->exec("UPDATE {$this->table} SET status = 'offline', ended_at = NOW()
                               WHERE status = 'live' AND started_at < NOW() - INTERVAL '" . self::MAX_HOURS . " hours'");
            return $this->conn->query("SELECT " . self::FIELDS . " FROM {$this->table} s JOIN users u ON u.user_id = s.user_id
                                       LEFT JOIN profiles p ON p.user_id = s.user_id
                                       WHERE s.status = 'live' ORDER BY s.started_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
        });
    }
}
