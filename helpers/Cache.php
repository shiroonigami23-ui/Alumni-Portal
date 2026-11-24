<?php
/**
 * Fast cache with automatic backend selection:
 *   1. Redis   (phpredis extension + REDIS_HOST, e.g. the docker-compose "redis" service / ElastiCache)
 *   2. APCu    (shared memory inside the PHP server — great on a single server / XAMPP)
 *   3. Files   (storage/cache/app — always works, still far faster than repeated SQL)
 *
 *   Cache::remember('atlas:' . md5($filters), 120, fn () => expensiveQuery());
 *   Cache::forget('referrals:stats');
 *   Cache::bump('feed');           // invalidate a whole group via a version counter
 *   schema_once('community_v1', fn () => ...);   // run schema checks once per deploy
 *
 * Disable with CACHE_DRIVER=none (debugging). Never throws: a cache problem
 * degrades to "no cache", never to an error page.
 */
final class Cache
{
    private static ?string $driver = null;
    private static $redis = null;
    private static array $local = [];

    public static function driver(): string
    {
        if (self::$driver !== null) {
            return self::$driver;
        }
        $want = strtolower((string)(getenv('CACHE_DRIVER') ?: 'auto'));
        if ($want === 'none') {
            return self::$driver = 'none';
        }
        if (in_array($want, ['auto', 'redis'], true) && extension_loaded('redis') && (getenv('REDIS_HOST') || $want === 'redis')) {
            try {
                $r = new Redis();
                $r->connect((string)(getenv('REDIS_HOST') ?: '127.0.0.1'), (int)(getenv('REDIS_PORT') ?: 6379), 0.25);
                if ($pass = getenv('REDIS_PASSWORD')) {
                    $r->auth($pass);
                }
                $r->setOption(Redis::OPT_PREFIX, (getenv('CACHE_PREFIX') ?: 'rjit') . ':');
                self::$redis = $r;
                return self::$driver = 'redis';
            } catch (Throwable $e) {
                error_log('Cache: Redis unavailable, falling back (' . $e->getMessage() . ')');
            }
        }
        if (in_array($want, ['auto', 'apcu'], true) && function_exists('apcu_enabled') && apcu_enabled()) {
            return self::$driver = 'apcu';
        }
        return self::$driver = 'file';
    }

    private static function fileFor(string $key): string
    {
        $dir = dirname(__DIR__) . '/storage/cache/app';
        if (!is_dir($dir)) {
            @mkdir($dir, 0770, true);
        }
        return $dir . '/' . hash('sha1', $key) . '.cache';
    }

    public static function get(string $key, $default = null)
    {
        if (array_key_exists($key, self::$local)) {
            return self::$local[$key];
        }
        try {
            switch (self::driver()) {
                case 'redis':
                    $v = self::$redis->get($key);
                    return $v === false ? $default : (self::$local[$key] = unserialize($v, ['allowed_classes' => false]));
                case 'apcu':
                    $v = apcu_fetch($key, $ok);
                    return $ok ? (self::$local[$key] = $v) : $default;
                case 'file':
                    $f = self::fileFor($key);
                    $raw = @file_get_contents($f);
                    if ($raw === false) {
                        return $default;
                    }
                    [$exp, $val] = unserialize($raw, ['allowed_classes' => false]);
                    if ($exp && $exp < time()) {
                        @unlink($f);
                        return $default;
                    }
                    return self::$local[$key] = $val;
            }
        } catch (Throwable $e) {
            error_log('Cache get: ' . $e->getMessage());
        }
        return $default;
    }

    public static function set(string $key, $value, int $ttl = 60): void
    {
        self::$local[$key] = $value;
        try {
            switch (self::driver()) {
                case 'redis':
                    $ttl > 0 ? self::$redis->setex($key, $ttl, serialize($value)) : self::$redis->set($key, serialize($value));
                    break;
                case 'apcu':
                    apcu_store($key, $value, max(0, $ttl));
                    break;
                case 'file':
                    $f = self::fileFor($key);
                    $tmp = $f . '.' . getmypid();
                    if (@file_put_contents($tmp, serialize([$ttl > 0 ? time() + $ttl : 0, $value])) !== false) {
                        @rename($tmp, $f);
                    }
                    break;
            }
        } catch (Throwable $e) {
            error_log('Cache set: ' . $e->getMessage());
        }
    }

    public static function forget(string $key): void
    {
        unset(self::$local[$key]);
        try {
            switch (self::driver()) {
                case 'redis':
                    self::$redis->del($key);
                    break;
                case 'apcu':
                    apcu_delete($key);
                    break;
                case 'file':
                    @unlink(self::fileFor($key));
                    break;
            }
        } catch (Throwable $e) {
        }
    }

    /** Return the cached value or compute, store and return it. */
    public static function remember(string $key, int $ttl, callable $compute)
    {
        $miss = "\0__miss__";
        $v = self::get($key, $miss);
        if ($v !== $miss) {
            return $v;
        }
        $v = $compute();
        self::set($key, $v, $ttl);
        return $v;
    }

    /** Version counter for group invalidation: key("feed") → "feed:v3". */
    public static function key(string $group, string $suffix = ''): string
    {
        return $group . ':v' . (int)self::get("ver:$group", 1) . ($suffix !== '' ? ':' . $suffix : '');
    }

    public static function bump(string $group): void
    {
        self::set("ver:$group", (int)self::get("ver:$group", 1) + 1, 0);
    }
}

/**
 * Run idempotent schema checks once per deploy instead of on every request.
 * The marker includes the schema file's mtime, so editing a schema helper
 * automatically re-runs it.
 */
function schema_once(string $name, callable $ensure, ?string $file = null): void
{
    static $ran = [];
    $key = 'schema:' . $name . ':' . ($file ? (string)@filemtime($file) : '0') . ':' . (getenv('DB_NAME') ?: 'db');
    if (isset($ran[$key]) || Cache::get($key)) {
        $ran[$key] = true;
        return;
    }
    $ensure();
    $ran[$key] = true;
    Cache::set($key, 1, 86400);
}
