<?php

require_once __DIR__ . "/../config/security.php";

if (
    !isset($_SESSION["admin_id"]) ||
    !isset($_SESSION["admin_role"]) ||
    $_SESSION["admin_role"] !== "admin"
) {
    http_response_code(403);
    exit("Access denied.");
}

require_once __DIR__ . "/../config/database.php";

$attachmentId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$attachmentId || $attachmentId < 1) {
    http_response_code(400);
    exit("Invalid attachment.");
}

try {

    $stmt = $pdo->prepare(
        "SELECT
            id,
            file_name,
            file_path,
            file_type
         FROM request_attachments
         WHERE id = :id
         LIMIT 1"
    );

    $stmt->execute([
        ":id" => $attachmentId
    ]);

    $attachment = $stmt->fetch();

    if (!$attachment) {
        http_response_code(404);
        exit("Attachment not found.");
    }

    $filePath = __DIR__ . "/../" . $attachment["file_path"];

    if (
        !is_file($filePath) ||
        !is_readable($filePath)
    ) {
        http_response_code(404);
        exit("File not found.");
    }

    $fileName = basename(
        $attachment["file_name"]
    );

    $mimeType = $attachment["file_type"];

    if (!$mimeType) {
        $mimeType = "application/octet-stream";
    }

    header(
        "Content-Type: " . $mimeType
    );

    header(
        "Content-Length: " . filesize($filePath)
    );

    header(
        "Content-Disposition: inline; filename=\"" .
        str_replace(
            ["\"", "\r", "\n"],
            "",
            $fileName
        ) .
        "\""
    );

    header("X-Content-Type-Options: nosniff");

    readfile($filePath);
    exit;

} catch (Throwable $e) {

    error_log(
        "Request Attachment Error: " .
        $e->getMessage()
    );

    http_response_code(500);
    exit("Unable to open attachment.");
}
