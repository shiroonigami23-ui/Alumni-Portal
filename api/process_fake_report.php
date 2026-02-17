<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$requestor_id = $auth->validateRequest();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->reporter_id)) {
    // 1. Get Roles of both parties
    $stmt = $db->prepare("SELECT role FROM users WHERE user_id = :id");
    
    $stmt->execute(['id' => $requestor_id]);
    $my_role = $stmt->fetchColumn();

    $stmt->execute(['id' => $data->reporter_id]);
    $target_role = $stmt->fetchColumn();

    // 2. Hierarchy Check (Section 4.C/D)
    $can_warn = false;
    if ($my_role === 'admin') {
        $can_warn = true; // Admin warns anyone
    } elseif ($my_role === 'faculty' && ($target_role === 'student' || $target_role === 'alumni')) {
        $can_warn = true; // Faculty warns lower tiers
    }

    if (!$can_warn) {
        http_response_code(403);
        echo json_encode(["message" => "Unauthorized. You cannot issue warnings to this user level."]);
        exit();
    }

    try {
        $db->beginTransaction();

        // 3. Upsert Warning
        $query = "INSERT INTO moderation_strikes (user_id, warning_count) 
                  VALUES (:uid, 1) 
                  ON CONFLICT (user_id) 
                  DO UPDATE SET warning_count = moderation_strikes.warning_count + 1";
        $db->prepare($query)->execute(['uid' => $data->reporter_id]);

        $check = $db->prepare("SELECT warning_count, strike_count FROM moderation_strikes WHERE user_id = :uid");
        $check->execute(['uid' => $data->reporter_id]);
        $stats = $check->fetch(PDO::FETCH_ASSOC);

        $response = ["message" => "Warning issued by $my_role."];

        // 4. Strike & Shadow Ban Logic (5 Warnings = 1 Strike)
        if ($stats['warning_count'] >= 5) {
            $new_strikes = $stats['strike_count'] + 1;
            $ban_hours = $new_strikes * 24; 
            $until = date('Y-m-d H:i:s', strtotime("+$ban_hours hours"));
            
            $db->prepare("UPDATE moderation_strikes SET strike_count = :s, warning_count = 0, shadow_ban_until = :u WHERE user_id = :uid")
               ->execute(['s' => $new_strikes, 'u' => $until, 'uid' => $data->reporter_id]);
            
            $response = ["message" => "Strike $new_strikes issued. Shadow ban active until $until."];
        }

        $db->commit();
        echo json_encode($response);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
}
?>