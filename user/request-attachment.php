<?php

require_once __DIR__ . "/../config/security.php";
require_once __DIR__ . "/../config/database.php";

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "user"
) {
    http_response_code(403);
    exit("Access denied.");
}

$userId = (int) $_SESSION["user_id"];

$attachmentId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$attachmentId || $attachmentId < 1) {
    http_response_code(400);
    exit("Invalid attachment.");
}

/*
|--------------------------------------------------------------------------
| Verify attachment belongs to a request owned by logged-in user
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        ra.id,
        ra.file_name,
        ra.file_path,
        ra.file_type
     FROM request_attachments ra
     INNER JOIN service_requests sr
        ON ra.request_id = sr.id
     WHERE ra.id = :attachment_id
       AND sr.user_id = :user_id
     LIMIT 1"
);

$stmt->execute([
    ":attachment_id" => $attachmentId,
    ":user_id" => $userId
]);

$attachment = $stmt->fetch();

if (!$attachment) {
    http_response_code(404);
    exit("Attachment not found.");
}

/*
|--------------------------------------------------------------------------
| Private file path
|--------------------------------------------------------------------------
*/

$relativePath = $attachment["file_path"];

$projectRoot = realpath(
    __DIR__ . "/.."
);

if ($projectRoot === false) {
    http_response_code(500);
    exit("Server configuration error.");
}

$filePath = realpath(
    $projectRoot . "/" . $relativePath
);

$privateRoot = realpath(
    dirname($projectRoot) . "/khan uploads/requests"
);

if (
    $filePath === false ||
    $privateRoot === false
) {
    http_response_code(404);
    exit("File not found.");
}

/*
|--------------------------------------------------------------------------
| Prevent path traversal
|--------------------------------------------------------------------------
*/

$privateRootWithSlash =
    rtrim($privateRoot, DIRECTORY_SEPARATOR)
    . DIRECTORY_SEPARATOR;

if (
    strpos(
        $filePath,
        $privateRootWithSlash
    ) !== 0
) {
    http_response_code(403);
    exit("Access denied.");
}

if (!is_file($filePath) || !is_readable($filePath)) {
    http_response_code(404);
    exit("File not found.");
}

/*
|--------------------------------------------------------------------------
| Send file
|--------------------------------------------------------------------------
*/

$fileName = basename(
    $attachment["file_name"]
);

$contentType = $attachment["file_type"];

if (
    !$contentType ||
    strpos($contentType, "/") === false
) {
    $contentType = "application/octet-stream";
}

header(
    "Content-Type: " . $contentType
);

header(
    "Content-Length: " . filesize($filePath)
);

header(
    'Content-Disposition: attachment; filename="' .
    str_replace(
        '"',
        "",
        $fileName
    ) .
    '"'
);

header("X-Content-Type-Options: nosniff");

readfile($filePath);
exit;
