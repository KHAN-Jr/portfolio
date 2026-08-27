<?php

require_once __DIR__ . "/../config/security.php";

if (
    !isset($_SESSION["admin_id"]) ||
    !isset($_SESSION["admin_role"]) ||
    $_SESSION["admin_role"] !== "admin"
) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: requests.php");
    exit;
}
if (!verifyCsrfToken($_POST["csrf_token"] ?? null)) {
    header("Location: requests.php?status=error");
    exit;
}
$requestId = filter_input(
    INPUT_POST,
    "request_id",
    FILTER_VALIDATE_INT
);

$newStatus = trim(
    $_POST["status"] ?? ""
);

$allowedStatuses = [
    "pending",
    "in_progress",
    "completed",
    "cancelled"
];

if (!$requestId || $requestId < 1) {
    header("Location: requests.php");
    exit;
}

if (!in_array($newStatus, $allowedStatuses, true)) {
    header("Location: requests.php");
    exit;
}

try {

    $pdo->beginTransaction();

    $requestStmt = $pdo->prepare(
        "SELECT id, status
         FROM service_requests
         WHERE id = :request_id
         LIMIT 1"
    );

    $requestStmt->execute([
        ":request_id" => $requestId
    ]);

    $request = $requestStmt->fetch();

    if (!$request) {

        $pdo->rollBack();

        header("Location: requests.php");
        exit;
    }

    $oldStatus = $request["status"];

    if ($oldStatus === $newStatus) {

        $pdo->rollBack();

        header("Location: requests.php");
        exit;
    }

    $updateStmt = $pdo->prepare(
        "UPDATE service_requests
         SET status = :status
         WHERE id = :request_id"
    );

    $updateStmt->execute([
        ":status" => $newStatus,
        ":request_id" => $requestId
    ]);

    $action = sprintf(
        "Changed status from %s to %s",
        $oldStatus,
        $newStatus
    );

    $adminActionStmt = $pdo->prepare(
        "INSERT INTO admin_actions
            (admin_id, request_id, action)
         VALUES
            (:admin_id, :request_id, :action)"
    );

    $adminActionStmt->execute([
        ":admin_id" => (int) $_SESSION["admin_id"],
        ":request_id" => $requestId,
        ":action" => $action
    ]);

    $pdo->commit();

    header(
        "Location: requests.php?status=updated"
    );
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        "Update Request Status Error: " . $e->getMessage()
    );

    header(
        "Location: requests.php?status=error"
    );
    exit;
}