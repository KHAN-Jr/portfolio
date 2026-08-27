<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Method not allowed."
    ]);

    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request data."
    ]);

    exit;
}

$service = trim($input["service"] ?? "");
$name = trim($input["name"] ?? "");
$email = trim($input["email"] ?? "");
$phone = trim($input["phone"] ?? "");
$title = trim($input["title"] ?? "");
$message = trim($input["message"] ?? "");
$location = trim($input["location"] ?? "");
$urgency = strtolower(trim($input["urgency"] ?? "normal"));

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

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Invalid email address."
    ]);

    exit;
}

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

try {

    $pdo->beginTransaction();

    /*
     * 1. Find active service
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

        http_response_code(422);

        echo json_encode([
            "success" => false,
            "message" => "Selected service is not available."
        ]);

        exit;
    }

    $serviceId = (int) $serviceRow["id"];

    /*
     * 2. Find existing client by email
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

        /*
         * 3. Create client account record
         */
        $temporaryPassword = password_hash(
            bin2hex(random_bytes(32)),
            PASSWORD_DEFAULT
        );

        $createUserStmt = $pdo->prepare(
            "INSERT INTO users
                (full_name, email, phone, password, role)
             VALUES
                (:full_name, :email, :phone, :password, 'user')"
        );

        $createUserStmt->execute([
            ":full_name" => $name,
            ":email" => $email,
            ":phone" => $phone,
            ":password" => $temporaryPassword
        ]);

        $userId = (int) $pdo->lastInsertId();
    }

    /*
     * 4. Save service request
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
        ":location" => $location !== "" ? $location : null,
        ":urgency" => $dbUrgency
    ]);

    $requestId = (int) $pdo->lastInsertId();

    $pdo->commit();

    http_response_code(201);

    echo json_encode([
        "success" => true,
        "message" => "Service request submitted successfully.",
        "request_id" => $requestId
    ]);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        "Service Request Error: " . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to process service request."
    ]);
}
