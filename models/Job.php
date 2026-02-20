<?php
class Job
{
    private $conn;
    private $table = "jobs";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create($data, $poster_id)
    {
        // 1. Handle 3.5NF File Storage for Description
        $filename = "job_desc_" . $poster_id . "_" . time() . ".txt";
        $storage_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . "jobs" . DIRECTORY_SEPARATOR;

        if (!file_exists($storage_dir)) {
            mkdir($storage_dir, 0777, true);
        }

        file_put_contents($storage_dir . $filename, $data->description);
        $relative_path = "storage/jobs/" . $filename;

        // 2. Prepare SQL (Using the columns verified in \d jobs)
        $query = "INSERT INTO " . $this->table . " 
                  (poster_id, company_name, job_title, description_file_path, location, salary_range, application_url, job_type) 
                  VALUES (:pid, :company, :title, :path, :loc, :salary, :url, :type)";

        $stmt = $this->conn->prepare($query);

        // Bind and Execute
        return $stmt->execute([
            'pid'     => $poster_id,
            'company' => $data->company_name,
            'title'   => $data->job_title,
            'path'    => $relative_path,
            'loc'     => $data->location ?? 'Remote',
            'salary'  => $data->salary_range ?? 'Not Disclosed',
            'url'     => $data->application_url ?? '',
            'type'    => $data->job_type ?? 'full-time'
        ]);
    }


    // LIST ALL JOBS
    public function getAll()
    {
        $query = "SELECT 
                    j.job_id, 
                    j.company_name, 
                    j.job_title, 
                    j.description_file_path, 
                    j.location, 
                    j.salary_range, 
                    j.application_url, 
                    j.job_type, 
                    j.created_at, 
                    u.email as poster_name -- Using email as name for now since we join users
                  FROM " . $this->table . " j
                  LEFT JOIN users u ON j.poster_id = u.user_id
                  ORDER BY j.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        // Note: The description is in a file. 
        // In a real high-perf scenario, we might only fetch the file content when viewing details.
        // For getAll(), we'll return the path, or fetch it if needed.
        // For now, let's keep it simple and just return the metadata. 
        // The API layer (get_jobs.php) expects 'description', so we can modify the query 
        // to return the path as description, or fetch content here. A better pattern is to fetch content.

        return $stmt;
    }

    // Helper to get file content if needed for a specific job
    public function getDescription($path)
    {
        $realPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (file_exists($realPath)) {
            return file_get_contents($realPath);
        }
        return "Description not available.";
    }
}
