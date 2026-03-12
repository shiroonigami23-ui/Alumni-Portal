<?php

class FileStorageHelper
{
    public static function sanitizeToken(string $token): string
    {
        $clean = strtolower(trim($token));
        $clean = preg_replace('/[^a-z0-9_-]+/', '_', $clean) ?? '';
        return trim($clean, '_') ?: 'file';
    }

    public static function utcTimestampToken(): string
    {
        $dt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $base = $dt->format('Ymd\THis');
        $micro = (int)$dt->format('u');
        $ms = str_pad((string)intdiv($micro, 1000), 3, '0', STR_PAD_LEFT);
        return $base . $ms . 'Z';
    }

    public static function uniqueFileName(string $prefix, int $userId, string $extension, string $suffix = ''): string
    {
        $prefix = self::sanitizeToken($prefix);
        $suffix = self::sanitizeToken($suffix);
        $ext = strtolower(ltrim(trim($extension), '.'));
        $stamp = self::utcTimestampToken();
        $rand = bin2hex(random_bytes(8));

        $parts = [$prefix, (string)$userId];
        if ($suffix !== '') $parts[] = $suffix;
        $parts[] = $stamp;
        $parts[] = $rand;

        $name = implode('_', $parts);
        return $ext !== '' ? ($name . '.' . $ext) : $name;
    }
}

