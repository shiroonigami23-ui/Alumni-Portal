<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->token)) {
    try {
        $db->beginTransaction();

        // 1. Token Validation (Blueprint Section 3.B2)
        $t_stmt = $db->prepare("SELECT token FROM alumni_tokens WHERE token = :t AND used = false");
        $t_stmt->execute(['t' => $data->token]);
        if ($t_stmt->rowCount() == 0) {
            echo json_encode(["message" => "Invalid or already used token."]);
            exit();
        }

        // 2. Create Account with 'pending' status
        $hash = password_hash($data->password, PASSWORD_BCRYPT, ['cost' => 12]);
        $u_query = "INSERT INTO users (email, password_hash, role, status) VALUES (:e, :p, 'alumni', 'pending') RETURNING user_id";
        $u_stmt = $db->prepare($u_query);
        $u_stmt->execute(['e' => $data->email, 'p' => $hash]);
        $user_id = $u_stmt->fetchColumn();

        // 3. Mark Token as used
        $db->prepare("UPDATE alumni_tokens SET used = true, used_by_email = :e WHERE token = :t")
           ->execute(['e' => $data->email, 't' => $data->token]);

        // 4. Create Profile
        $db->prepare("INSERT INTO profiles (user_id, full_name, graduation_year) VALUES (:uid, :name, :yr)")
           ->execute(['uid' => $user_id, 'name' => $data->full_name, 'yr' => $data->grad_year]);

        $db->commit();
        echo json_encode(["message" => "Registration submitted. Pending admin/faculty approval."]);
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(["message" => "Error: " . $e->getMessage()]);
    }
}
?>