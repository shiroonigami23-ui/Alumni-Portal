<?php

class StudentLifecycleHelper
{
    public static function parseJoiningYearFromRjitEmail(string $email): ?int
    {
        $email = strtolower(trim($email));
        if (!str_ends_with($email, '@rjit.ac.in')) {
            return null;
        }

        $local = explode('@', $email)[0] ?? '';
        // Example: 0902cs231028 -> branch=cs, joinYear=23
        if (!preg_match('/^[0-9]{4}[a-z]{2,4}([0-9]{2})[0-9]{2,}$/', $local, $m)) {
            return null;
        }

        $yy = (int)$m[1];
        return 2000 + $yy;
    }

    public static function expectedGraduationYearForEmail(string $email): ?int
    {
        $join = self::parseJoiningYearFromRjitEmail($email);
        if (!$join) return null;
        return $join + 4; // default UG lifecycle
    }

    public static function graduationCutoffDate(int $graduationYear): DateTimeImmutable
    {
        // Promote at start of July of graduation year.
        return new DateTimeImmutable(sprintf('%04d-07-01 00:00:00', $graduationYear));
    }

    public static function isEligibleForAlumniRoleByEmail(string $email, ?DateTimeImmutable $asOf = null): bool
    {
        $gradYear = self::expectedGraduationYearForEmail($email);
        if (!$gradYear) {
            // Non-pattern/already custom emails are allowed for alumni.
            return true;
        }
        $now = $asOf ?: new DateTimeImmutable('now');
        return $now >= self::graduationCutoffDate($gradYear);
    }

    public static function promoteEligibleStudents(PDO $db, ?DateTimeImmutable $asOf = null): int
    {
        $now = $asOf ?: new DateTimeImmutable('now');
        $stmt = $db->query("SELECT user_id, email FROM users WHERE role = 'student' AND status = 'active'");
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        if (!$rows) return 0;

        $updated = 0;
        $upUser = $db->prepare("UPDATE users SET role = 'alumni', updated_at = CURRENT_TIMESTAMP WHERE user_id = :uid AND role = 'student'");
        $upProfile = $db->prepare("UPDATE profiles SET graduation_year = :gy, updated_at = CURRENT_TIMESTAMP WHERE user_id = :uid");

        foreach ($rows as $row) {
            $email = (string)($row['email'] ?? '');
            $gradYear = self::expectedGraduationYearForEmail($email);
            if (!$gradYear) continue;
            if ($now < self::graduationCutoffDate($gradYear)) continue;

            $uid = (int)$row['user_id'];
            $upUser->execute(['uid' => $uid]);
            if ($upUser->rowCount() > 0) {
                $upProfile->execute(['uid' => $uid, 'gy' => $gradYear]);
                $updated++;
            }
        }

        return $updated;
    }
}

