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
    header("Location: services.php");
    exit;
}

if (!verifyCsrfToken($_POST["csrf_token"] ?? "")) {
    $_SESSION["service_error"] =
        "Invalid security token. Please refresh the page and try again.";

    header("Location: services.php");
    exit;
}

$id = filter_var(
    $_POST["id"] ?? null,
    FILTER_VALIDATE_INT
);

if (!$id || $id < 1) {
    $_SESSION["service_error"] =
        "Invalid service ID.";

    header("Location: services.php");
    exit;
}

try {

    $stmt = $pdo->prepare(
        "SELECT
            id,
            is_active
         FROM services
         WHERE id = :id
         LIMIT 1"
    );

    $stmt->execute([
        ":id" => $id
    ]);

    $service = $stmt->fetch();

    if (!$service) {
        $_SESSION["service_error"] =
            "Service not found.";

        header("Location: services.php");
        exit;
    }

    $newStatus = ((int) $service["is_active"] === 1)
        ? 0
        : 1;

    $stmt = $pdo->prepare(
        "UPDATE services
         SET is_active = :is_active
         WHERE id = :id"
    );

    $stmt->execute([
        ":is_active" => $newStatus,
        ":id" => $id
    ]);

    if ($newStatus === 1) {
        $_SESSION["service_success"] =
            "Service activated successfully.";
    } else {
        $_SESSION["service_success"] =
            "Service deactivated successfully.";
    }

} catch (Throwable $e) {

    error_log(
        "Toggle Service Status Error: " .
        $e->getMessage()
    );

    $_SESSION["service_error"] =
        "Unable to update service status.";
}

header("Location: services.php");
exit;
