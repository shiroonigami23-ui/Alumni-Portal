<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../helpers/StudentLifecycleHelper.php';

$database = new Database();
$db = $database->getConnection();
if (!$db) {
    fwrite(STDERR, "DB unavailable\n");
    exit(1);
}

try {
    $db->beginTransaction();
    $count = StudentLifecycleHelper::promoteEligibleStudents($db);
    $db->commit();
    echo "PROMOTION_OK promoted={$count}\n";
    exit(0);
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    fwrite(STDERR, "PROMOTION_FAIL " . $e->getMessage() . "\n");
    exit(1);
}

