<?php
class Profile {
    private $conn;
    private $table_name = "profiles";

    public $user_id;
    public $full_name;
    public $bio;
    public $location_city;
    public $location_country;
    public $tech_stack;
    public $personal_website;
    public $linkedin_url;
    public $github_url;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function update() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, full_name, bio, location_city, location_country, tech_stack, personal_website, linkedin_url, github_url)
                  VALUES (:user_id, :full_name, :bio, :location_city, :location_country, :tech_stack, :personal_website, :linkedin_url, :github_url)
                  ON CONFLICT (user_id) 
                  DO UPDATE SET 
                    full_name = EXCLUDED.full_name,
                    bio = EXCLUDED.bio,
                    location_city = EXCLUDED.location_city,
                    location_country = EXCLUDED.location_country,
                    tech_stack = EXCLUDED.tech_stack,
                    personal_website = EXCLUDED.personal_website,
                    linkedin_url = EXCLUDED.linkedin_url,
                    github_url = EXCLUDED.github_url,
                    updated_at = CURRENT_TIMESTAMP";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':full_name', $this->full_name);
        $stmt->bindParam(':bio', $this->bio);
        $stmt->bindParam(':location_city', $this->location_city);
        $stmt->bindParam(':location_country', $this->location_country);
        $stmt->bindParam(':tech_stack', $this->tech_stack);
        $stmt->bindParam(':personal_website', $this->personal_website);
        $stmt->bindParam(':linkedin_url', $this->linkedin_url);
        $stmt->bindParam(':github_url', $this->github_url);

        return $stmt->execute();
    }
}
?>