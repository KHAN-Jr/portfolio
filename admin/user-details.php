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

$userId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$userId || $userId < 1) {
    http_response_code(400);
    exit("Invalid user.");
}

$user = null;
$requests = [];
$error = "";

try {

    /*
     * Load user
     */
    $stmt = $pdo->prepare(
        "SELECT
            id,
            full_name,
            email,
            phone,
            role,
            created_at,
            updated_at
         FROM users
         WHERE id = :id
         LIMIT 1"
    );

    $stmt->execute([
        ":id" => $userId
    ]);

    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        exit("User not found.");
    }

    /*
     * Load user's service requests
     */
    $requestStmt = $pdo->prepare(
        "SELECT
            sr.id,
            sr.title,
            sr.location,
            sr.urgency,
            sr.status,
            sr.created_at,
            s.name AS service_name
         FROM service_requests sr
         LEFT JOIN services s
            ON s.id = sr.service_id
         WHERE sr.user_id = :user_id
         ORDER BY sr.id DESC"
    );

    $requestStmt->execute([
        ":user_id" => $userId
    ]);

    $requests = $requestStmt->fetchAll();

} catch (Throwable $e) {

    error_log(
        "Admin User Details Error: " .
        $e->getMessage()
    );

    $error = "Unable to load user details.";
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <meta
        name="robots"
        content="noindex, nofollow">

    <title>
        User Details — KHAN SOLUTIONS Admin
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            color: #1f2937;
        }

        header {
            background: #111827;
            color: #ffffff;
            padding: 22px 5%;
        }

        header h1 {
            margin: 0;
            font-size: 22px;
        }

        header p {
            margin: 6px 0 0;
            opacity: 0.8;
        }

        nav {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 15px 5%;
        }

        nav a {
            color: #374151;
            text-decoration: none;
            font-weight: 600;
            margin-right: 22px;
        }

        nav a:hover {
            text-decoration: underline;
        }

        nav form {
            display: inline;
        }

        nav button {
            border: 0;
            background: transparent;
            color: #b91c1c;
            font-weight: 600;
            cursor: pointer;
            padding: 0;
        }

        .container {
            width: min(1100px, 94%);
            margin: 35px auto;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        .back:hover {
            text-decoration: underline;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h2 {
            margin: 0 0 7px;
        }

        .page-header p {
            margin: 0;
            color: #6b7280;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 25px;
            box-shadow:
                0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .card-title {
            margin: 0 0 20px;
            font-size: 18px;
        }

        .details {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .field-label {
            display: block;
            margin-bottom: 6px;
            color: #6b7280;
            font-size: 13px;
            font-weight: 600;
        }

        .field-value {
            font-size: 15px;
            word-break: break-word;
        }

        .role {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .role-user {
            background: #e5e7eb;
            color: #374151;
        }

        .role-admin {
            background: #dbeafe;
            color: #1e40af;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        th,
        td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #eef0f2;
        }

        th {
            background: #f9fafb;
            color: #6b7280;
            font-size: 12px;
            text-transform: uppercase;
        }

        td {
            font-size: 14px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-in_progress {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .empty {
            padding: 25px 10px;
            text-align: center;
            color: #6b7280;
        }

        .alert {
            padding: 14px;
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        @media (max-width: 650px) {

            .details {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<header>

    <h1>
        KHAN SOLUTIONS
    </h1>

    <p>
        Administration Panel
    </p>

</header>

<nav>

    <a href="dashboard.php">
        Dashboard
    </a>

    <a href="requests.php">
        Requests
    </a>

    <a href="services.php">
        Services
    </a>

    <a href="users.php">
        Users
    </a>

    <a href="settings.php">
        Settings
    </a>

    <form
        method="POST"
        action="logout.php"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(
                csrfToken(),
                ENT_QUOTES,
                "UTF-8"
            ) ?>"
        >

        <button type="submit">
            Logout
        </button>

    </form>

</nav>

<div class="container">

    <a
        href="users.php"
        class="back"
    >
        ← Back to Users
    </a>

    <?php if ($error !== ""): ?>

        <div class="alert">

            <?= htmlspecialchars(
                $error,
                ENT_QUOTES,
                "UTF-8"
            ) ?>

        </div>

    <?php endif; ?>

    <?php if ($user): ?>

        <div class="page-header">

            <h2>
                User Details
            </h2>

            <p>
                Account information and service request history.
            </p>

        </div>

        <div class="card">

            <h3 class="card-title">
                Account Information
            </h3>

            <div class="details">

                <div>
                    <span class="field-label">
                        User ID
                    </span>

                    <div class="field-value">
                        #<?= (int) $user["id"] ?>
                    </div>
                </div>

                <div>
                    <span class="field-label">
                        Full Name
                    </span>

                    <div class="field-value">
                        <?= htmlspecialchars(
                            $user["full_name"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </div>
                </div>

                <div>
                    <span class="field-label">
                        Email
                    </span>

                    <div class="field-value">
                        <?= htmlspecialchars(
                            $user["email"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </div>
                </div>

                <div>
                    <span class="field-label">
                        Phone
                    </span>

                    <div class="field-value">
                        <?= htmlspecialchars(
                            $user["phone"] ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?: "Not provided" ?>
                    </div>
                </div>

                <div>
                    <span class="field-label">
                        Role
                    </span>

                    <div class="field-value">

                        <span
                            class="role role-<?= htmlspecialchars(
                                $user["role"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                        >
                            <?= htmlspecialchars(
                                ucfirst($user["role"]),
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </span>

                    </div>
                </div>

                <div>
                    <span class="field-label">
                        Created At
                    </span>

                    <div class="field-value">
                        <?= htmlspecialchars(
                            $user["created_at"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </div>
                </div>

                <div>
                    <span class="field-label">
                        Updated At
                    </span>

                    <div class="field-value">
                        <?= htmlspecialchars(
                            $user["updated_at"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </div>

                </div>

            </div>

        </div>

        <div class="card">

            <h3 class="card-title">
                Service Requests
            </h3>

            <?php if (!$requests): ?>

                <div class="empty">
                    This user has no service requests.
                </div>

            <?php else: ?>

                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Service
                                </th>

                                <th>
                                    Title
                                </th>

                                <th>
                                    Location
                                </th>

                                <th>
                                    Urgency
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Created
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($requests as $request): ?>

                                <tr>

                                    <td>
                                        #<?= (int) $request["id"] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $request["service_name"] ?? "N/A",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $request["title"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $request["location"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?: "Not provided" ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            ucfirst(
                                                $request["urgency"] ?? ""
                                            ),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </td>

                                    <td>

                                        <span
                                            class="status status-<?= htmlspecialchars(
                                                $request["status"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                        >

                                            <?= htmlspecialchars(
                                                ucwords(
                                                    str_replace(
                                                        "_",
                                                        " ",
                                                        $request["status"]
                                                    )
                                                ),
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </span>

                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $request["created_at"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    <?php endif; ?>

</div>

</body>

</html>
