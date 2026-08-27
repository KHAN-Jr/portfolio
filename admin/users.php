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

$users = [];
$error = "";

try {

    $stmt = $pdo->query(
        "SELECT
            id,
            full_name,
            email,
            phone,
            role,
            created_at,
            updated_at
         FROM users
         ORDER BY id DESC"
    );

    $users = $stmt->fetchAll();

} catch (Throwable $e) {

    error_log(
        "Admin Users Error: " . $e->getMessage()
    );

    $error = "Unable to load users.";
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
        Users — KHAN SOLUTIONS Admin
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

        .logout {
            color: #b91c1c;
        }

        .container {
            width: min(1200px, 94%);
            margin: 35px auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        .page-header h2 {
            margin: 0 0 7px;
        }

        .page-header p {
            margin: 0;
            color: #6b7280;
        }

        .count {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 700;
        }

        .alert {
            padding: 14px;
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .table-wrapper {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow-x: auto;
            box-shadow:
                0 2px 8px rgba(0, 0, 0, 0.04);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eef0f2;
        }

        th {
            background: #f9fafb;
            font-size: 13px;
            text-transform: uppercase;
            color: #6b7280;
        }

        td {
            font-size: 14px;
        }

        tr:last-child td {
            border-bottom: none;
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

        .empty {
            padding: 40px 20px;
            text-align: center;
            color: #6b7280;
        }

        @media (max-width: 650px) {

            .page-header {
                align-items: flex-start;
                flex-direction: column;
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

    <form method="POST" action="logout.php">

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

    <div class="page-header">

        <div>

            <h2>
                Users
            </h2>

            <p>
                View clients and administrator accounts.
            </p>

        </div>

        <div class="count">

            <?= count($users) ?>

            Account<?= count($users) === 1 ? "" : "s" ?>

        </div>

    </div>


    <?php if ($error !== ""): ?>

        <div class="alert">

            <?= htmlspecialchars(
                $error,
                ENT_QUOTES,
                "UTF-8"
            ) ?>

        </div>

    <?php endif; ?>


    <?php if (empty($users)): ?>

        <div class="table-wrapper">

            <div class="empty">

                No user accounts found.

            </div>

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
                            Full Name
                        </th>

                        <th>
                            Email
                        </th>

                        <th>
                            Phone
                        </th>

                        <th>
                            Role
                        </th>

                        <th>
                            Created
                        </th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($users as $user): ?>

                        <tr>

                            <td>
                                #<?= (int) $user["id"] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $user["full_name"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $user["email"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $user["phone"] ?? "",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>

                                <span
                                    class="role role-<?= htmlspecialchars(
                                        $user["role"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>"
                                >

                                    <?= htmlspecialchars(
                                        ucfirst(
                                            $user["role"]
                                        ),
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                </span>

                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $user["created_at"],
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

</body>

</html>