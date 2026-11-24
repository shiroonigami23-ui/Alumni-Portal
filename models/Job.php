<?php
/**
 * Job postings. Descriptions live in the DB content store (dbjson:token) so
 * they survive app-server changes; legacy storage/jobs/*.txt files still load.
 */
require_once __DIR__ . '/../api/_content_store.php';

class Job
{
    private $conn;
    private $table = "jobs";

    public const TYPES = ['full-time', 'part-time', 'internship', 'contract', 'remote', 'freelance'];

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /** Validate + normalise input. Returns [array $clean, ?string $error]. */
    public static function clean($data): array
    {
        $d = is_object($data) ? (array)$data : (array)$data;
        $str = fn ($k, $max) => trim(mb_substr(strip_tags((string)($d[$k] ?? '')), 0, $max));
        $c = [
            'company_name' => $str('company_name', 200) ?: $str('company', 200),
            'job_title' => $str('job_title', 200) ?: $str('title', 200),
            'description' => trim(mb_substr((string)($d['description'] ?? ''), 0, 8000)),
            'location' => $str('location', 200) ?: 'Remote',
            'salary_range' => $str('salary_range', 100) ?: 'Not disclosed',
            'application_url' => trim((string)($d['application_url'] ?? '')),
            'job_type' => in_array($d['job_type'] ?? '', self::TYPES, true) ? $d['job_type'] : 'full-time',
        ];
        if (mb_strlen($c['company_name']) < 2) {
            return [$c, 'Please enter the company name.'];
        }
        if (mb_strlen($c['job_title']) < 2) {
            return [$c, 'Please enter the job title.'];
        }
        if (mb_strlen($c['description']) < 10) {
            return [$c, 'Please add a short description (at least 10 characters).'];
        }
        if ($c['application_url'] !== '' && !preg_match('#^https?://[^\s<>"]+$#i', $c['application_url'])) {
            return [$c, 'Application link must start with https://'];
        }
        return [$c, null];
    }

    /** Create a job. Returns the new job_id (0 on failure). */
    public function create($data, $poster_id): int
    {
        [$c] = self::clean($data);
        $pointer = store_content_payload($this->conn, (int)$poster_id, ['content' => $c['description'], 'attachments' => []], 'job');
        $sql = "INSERT INTO {$this->table}
                (poster_id, company_name, job_title, description_file_path, location, salary_range, application_url, job_type)
                VALUES (:pid, :company, :title, :path, :loc, :salary, :url, :type)";
        if (db_is_pgsql($this->conn)) {
            $sql .= " RETURNING job_id";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'pid' => $poster_id, 'company' => $c['company_name'], 'title' => $c['job_title'], 'path' => $pointer,
            'loc' => $c['location'], 'salary' => $c['salary_range'], 'url' => $c['application_url'], 'type' => $c['job_type'],
        ]);
        return (int)(db_is_pgsql($this->conn) ? $stmt->fetchColumn() : $this->conn->lastInsertId());
    }

    public function update(int $jobId, $data): void
    {
        [$c] = self::clean($data);
        $row = $this->conn->prepare("SELECT description_file_path FROM {$this->table} WHERE job_id = :id");
        $row->execute(['id' => $jobId]);
        $old = (string)$row->fetchColumn();
        $pointer = extract_content_token($old)
            ? update_content_payload($this->conn, $old, ['content' => $c['description'], 'attachments' => []], null, 'job')
            : store_content_payload($this->conn, null, ['content' => $c['description'], 'attachments' => []], 'job');
        $this->conn->prepare("UPDATE {$this->table} SET company_name = :c, job_title = :t, description_file_path = :p, location = :l,
                              salary_range = :s, application_url = :u, job_type = :ty, updated_at = CURRENT_TIMESTAMP WHERE job_id = :id")
            ->execute(['c' => $c['company_name'], 't' => $c['job_title'], 'p' => $pointer, 'l' => $c['location'], 's' => $c['salary_range'],
                'u' => $c['application_url'], 'ty' => $c['job_type'], 'id' => $jobId]);
    }

    public function getAll(?int $viewerId = null, bool $includeClosed = false)
    {
        $statusFilter = $includeClosed ? '' : "WHERE COALESCE(j.status, 'open') = 'open'";
        $query = "SELECT j.job_id, j.poster_id, j.company_name, j.job_title, j.description_file_path, j.location, j.salary_range,
                         j.application_url, j.job_type, COALESCE(j.status, 'open') AS status, j.created_at,
                         COALESCE(p.full_name, 'RJIT member') AS poster_name, u.role AS poster_role,
                         (SELECT COUNT(*) FROM job_applications a WHERE a.job_id = j.job_id) AS applicant_count,
                         (SELECT COUNT(*) FROM job_applications a WHERE a.job_id = j.job_id AND a.applicant_id = :viewer) AS applied
                  FROM {$this->table} j
                  LEFT JOIN users u ON j.poster_id = u.user_id
                  LEFT JOIN profiles p ON p.user_id = j.poster_id
                  $statusFilter
                  ORDER BY j.created_at DESC
                  LIMIT 300";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['viewer' => $viewerId ?: 0]);
        return $stmt;
    }

    public function getDescription($path): string
    {
        $path = (string)$path;
        if (extract_content_token($path)) {
            $payload = load_content_payload($this->conn, $path);
            return (string)($payload['content'] ?? '');
        }
        $realPath = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path));
        $base = realpath(dirname(__DIR__) . '/storage');
        if ($realPath && $base && strpos($realPath, $base) === 0 && is_file($realPath)) {
            return (string)file_get_contents($realPath);
        }
        return '';
    }
}
