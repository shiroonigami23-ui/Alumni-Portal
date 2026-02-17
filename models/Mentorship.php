<?php
class Mentorship
{
    private $conn;
    private $table = "mentorship_requests";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Request Mentorship
    public function createRequest($mentee_id, $mentor_id, $message)
    {
        // Check if exists
        $check = "SELECT request_id FROM " . $this->table . " 
                  WHERE mentee_id = :mentee AND mentor_id = :mentor AND status IN ('pending', 'accepted')";
        $stmt = $this->conn->prepare($check);
        $stmt->execute(['mentee' => $mentee_id, 'mentor' => $mentor_id]);
        if ($stmt->rowCount() > 0) return ["status" => false, "message" => "Request already pending or accepted."];

        // Create
        $query = "INSERT INTO " . $this->table . " (mentee_id, mentor_id, message, status) VALUES (:mentee, :mentor, :msg, 'pending')";
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute(['mentee' => $mentee_id, 'mentor' => $mentor_id, 'msg' => $message])) {
            return ["status" => true, "message" => "Mentorship request sent."];
        }
        return ["status" => false, "message" => "Failed to send request."];
    }

    // Response to Request
    public function updateStatus($request_id, $mentor_id, $status)
    {
        if (!in_array($status, ['accepted', 'rejected'])) return false;

        $query = "UPDATE " . $this->table . " SET status = :status WHERE request_id = :id AND mentor_id = :mentor";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute(['status' => $status, 'id' => $request_id, 'mentor' => $mentor_id]);
    }

    // Get Requests for Mentor
    public function getRequestsForMentor($mentor_id)
    {
        $query = "SELECT r.*, p.full_name, p.profile_picture_url 
                  FROM " . $this->table . " r
                  JOIN profiles p ON r.mentee_id = p.user_id
                  WHERE r.mentor_id = :id AND r.status = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id' => $mentor_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
