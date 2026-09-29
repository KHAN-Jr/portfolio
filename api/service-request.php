<?php

header("Content-Type: application/json; charset=UTF-8");

$allowedOrigins = [
    "https://khan-jr.github.io",
    "https://khan-jr.netlify.app"
];

$origin = $_SERVER["HTTP_ORIGIN"] ?? "";

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: " . $origin);
    header("Vary: Origin");
}

header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

require_once __DIR__ . "/../config/security.php";
require_once __DIR__ . "/../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Method not allowed."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Read request data
|--------------------------------------------------------------------------
*/

$contentType = $_SERVER["CONTENT_TYPE"] ?? "";

if (
    stripos(
        $contentType,
        "multipart/form-data"
    ) !== false
) {
    $input = $_POST;
} else {
    $input = json_decode(
        file_get_contents("php://input"),
        true
    );
}

if (!is_array($input)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request data."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Basic fields
|--------------------------------------------------------------------------
*/

$service = trim(
    $input["service"] ?? ""
);

$name = trim(
    $input["name"] ?? ""
);

$email = trim(
    $input["email"] ?? ""
);

$phone = trim(
    $input["phone"] ?? ""
);

$title = trim(
    $input["title"] ?? ""
);

$message = trim(
    $input["message"] ?? ""
);

$location = trim(
    $input["location"] ?? ""
);

$urgency = strtolower(
    trim(
        $input["urgency"] ?? "normal"
    )
);

/*
|--------------------------------------------------------------------------
| Required validation
|--------------------------------------------------------------------------
*/

if (
    $service === "" ||
    $name === "" ||
    $email === "" ||
    $phone === "" ||
    $title === "" ||
    $message === ""
) {
    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Please complete all required fields."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Email validation
|--------------------------------------------------------------------------
*/

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Invalid email address."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Length validation
|--------------------------------------------------------------------------
*/

if (strlen($name) > 100) {
    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Name is too long."
    ]);

    exit;
}

if (strlen($title) > 150) {
    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Title is too long."
    ]);

    exit;
}

if (strlen($message) > 5000) {
    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Description is too long."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Urgency mapping
|--------------------------------------------------------------------------
*/

$urgencyMap = [
    "normal" => "medium",
    "urgent" => "high",
    "emergency" => "high"
];

if (!isset($urgencyMap[$urgency])) {
    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Invalid urgency value."
    ]);

    exit;
}

$dbUrgency = $urgencyMap[$urgency];

/*
|--------------------------------------------------------------------------
| Attachment validation
|--------------------------------------------------------------------------
*/

$attachment = null;
$storedFilePath = null;

if (
    isset($_FILES["attachment"]) &&
    is_array($_FILES["attachment"])
) {
    $attachment = $_FILES["attachment"];

    if (
        $attachment["error"] !== UPLOAD_ERR_OK
    ) {
        http_response_code(422);

        echo json_encode([
            "success" => false,
            "message" => "Unable to upload attachment."
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Maximum size: 10 MB
    |--------------------------------------------------------------------------
    */

    $maxFileSize = 10 * 1024 * 1024;

    if (
        $attachment["size"] <= 0 ||
        $attachment["size"] > $maxFileSize
    ) {
        http_response_code(422);

        echo json_encode([
            "success" => false,
            "message" => "Attachment must be between 1 byte and 10MB."
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Allowed extensions
    |--------------------------------------------------------------------------
    */

    $originalName = $attachment["name"];

    $extension = strtolower(
        pathinfo(
            $originalName,
            PATHINFO_EXTENSION
        )
    );

    $allowedExtensions = [
        "jpg",
        "jpeg",
        "png",
        "pdf",
        "doc",
        "docx"
    ];

    if (
        !in_array(
            $extension,
            $allowedExtensions,
            true
        )
    ) {
        http_response_code(422);

        echo json_encode([
            "success" => false,
            "message" => "File type is not allowed."
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | MIME validation
    |--------------------------------------------------------------------------
    */

    $finfo = new finfo(FILEINFO_MIME_TYPE);

    $mimeType = $finfo->file(
        $attachment["tmp_name"]
    );

    $allowedMimeTypes = [
        "jpg" => [
            "image/jpeg"
        ],

        "jpeg" => [
            "image/jpeg"
        ],

        "png" => [
            "image/png"
        ],

        "pdf" => [
            "application/pdf"
        ],

        "doc" => [
            "application/msword"
        ],

        "docx" => [
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
        ]
    ];

    if (
        !isset($allowedMimeTypes[$extension]) ||
        !in_array(
            $mimeType,
            $allowedMimeTypes[$extension],
            true
        )
    ) {
        http_response_code(422);

        echo json_encode([
            "success" => false,
            "message" => "Attachment content does not match its file type."
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Upload directory
    |--------------------------------------------------------------------------
    */

    $uploadDirectory =
    "/storage/emulated/0/khan uploads/requests/";

    if (
        !is_dir($uploadDirectory)
    ) {
        if (
            !mkdir(
                $uploadDirectory,
                0755,
                true
            )
        ) {
            http_response_code(500);

            echo json_encode([
                "success" => false,
                "message" => "Upload directory is unavailable."
            ]);

            exit;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Generate random storage filename
    |--------------------------------------------------------------------------
    */

    $storedFileName =
        bin2hex(
            random_bytes(16)
        ) .
        "." .
        $extension;

    $storedFilePath =
        $uploadDirectory .
        $storedFileName;

    if (
        !move_uploaded_file(
            $attachment["tmp_name"],
            $storedFilePath
        )
    ) {
        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "Unable to save attachment."
        ]);

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Database transaction
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Find service
    |--------------------------------------------------------------------------
    */

    $serviceStmt = $pdo->prepare(
        "SELECT id
         FROM services
         WHERE name = :service
         AND is_active = 1
         LIMIT 1"
    );

    $serviceStmt->execute([
        ":service" => $service
    ]);

    $serviceRow = $serviceStmt->fetch();

    if (!$serviceRow) {

        $pdo->rollBack();

        if (
            $storedFilePath &&
            is_file($storedFilePath)
        ) {
            unlink($storedFilePath);
        }

        http_response_code(422);

        echo json_encode([
            "success" => false,
            "message" => "Selected service is not available."
        ]);

        exit;
    }

    $serviceId = (int) $serviceRow["id"];

    /*
    |--------------------------------------------------------------------------
    | Identify client
    |--------------------------------------------------------------------------
    */

    if (
        isset($_SESSION["user_id"]) &&
        isset($_SESSION["user_role"]) &&
        $_SESSION["user_role"] === "user"
    ) {
        $csrfToken = $_POST["csrf_token"] ?? null;

        if (!verifyCsrfToken($csrfToken)) {
            throw new RuntimeException(
                "Invalid security token. Please refresh the page and try again."
            );
        }

        $userId = (int) $_SESSION["user_id"];

        /*
         * Authenticated client:
         * Always read identity from the database.
         * Do not trust name, email or phone from POST.
         */
        $userStmt = $pdo->prepare(
            "SELECT
                id,
                full_name,
                email,
                phone
             FROM users
             WHERE id = :user_id
             AND role = 'user'
             LIMIT 1"
        );

        $userStmt->execute([
            ":user_id" => $userId
        ]);

        $userRow = $userStmt->fetch();

        if (!$userRow) {
            throw new RuntimeException(
                "Authenticated client account could not be verified."
            );
        }

        $name = $userRow["full_name"];
        $email = $userRow["email"];
        $phone = $userRow["phone"];

    } else {

        /*
         * Guest submission:
         * find an existing account by email or create
         * a normal user account using the database default role.
         */
        $userStmt = $pdo->prepare(
            "SELECT id
             FROM users
             WHERE email = :email
             LIMIT 1"
        );

        $userStmt->execute([
            ":email" => $email
        ]);

        $userRow = $userStmt->fetch();

        if ($userRow) {

            $userId = (int) $userRow["id"];

        } else {

            $temporaryPassword = password_hash(
                bin2hex(
                    random_bytes(32)
                ),
                PASSWORD_DEFAULT
            );

            $createUserStmt = $pdo->prepare(
                "INSERT INTO users
                    (
                        full_name,
                        email,
                        phone,
                        password
                    )
                 VALUES
                    (
                        :full_name,
                        :email,
                        :phone,
                        :password
                    )"
            );

            $createUserStmt->execute([
                ":full_name" => $name,
                ":email" => $email,
                ":phone" => $phone,
                ":password" => $temporaryPassword
            ]);

            $userId = (int) $pdo->lastInsertId();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Create service request
    |--------------------------------------------------------------------------
    */

    $requestStmt = $pdo->prepare(
        "INSERT INTO service_requests
            (
                user_id,
                service_id,
                title,
                description,
                location,
                urgency,
                status
            )
         VALUES
            (
                :user_id,
                :service_id,
                :title,
                :description,
                :location,
                :urgency,
                'pending'
            )"
    );

    $requestStmt->execute([
        ":user_id" => $userId,
        ":service_id" => $serviceId,
        ":title" => $title,
        ":description" => $message,
        ":location" =>
            $location !== ""
                ? $location
                : null,
        ":urgency" => $dbUrgency
    ]);

    $requestId = (int) $pdo->lastInsertId();

    /*
    |--------------------------------------------------------------------------
    | Save attachment metadata
    |--------------------------------------------------------------------------
    */

    if (
        $attachment !== null &&
        $storedFilePath !== null
    ) {

        $relativePath =
    "../khan uploads/requests/" .
    basename($storedFilePath);

        $attachmentStmt = $pdo->prepare(
            "INSERT INTO request_attachments
                (
                    request_id,
                    file_name,
                    file_path,
                    file_type
                )
             VALUES
                (
                    :request_id,
                    :file_name,
                    :file_path,
                    :file_type
                )"
        );

        $attachmentStmt->execute([
            ":request_id" => $requestId,
            ":file_name" =>
                basename(
                    $attachment["name"]
                ),
            ":file_path" =>
                $relativePath,
            ":file_type" =>
                $mimeType
        ]);
    }

    $pdo->commit();

    /*
    |--------------------------------------------------------------------------
    | Success
    |--------------------------------------------------------------------------
    */

    http_response_code(201);

    echo json_encode([
        "success" => true,
        "message" =>
            "Service request submitted successfully.",
        "request_id" => $requestId
    ]);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    /*
    |--------------------------------------------------------------------------
    | Remove uploaded file if database operation failed
    |--------------------------------------------------------------------------
    */

    if (
        $storedFilePath &&
        is_file($storedFilePath)
    ) {
        unlink($storedFilePath);
    }

    error_log(
        "Service Request Error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to process service request."
    ]);
}
