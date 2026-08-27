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

$admin = null;
$system = [
    "users" => 0,
    "services" => 0,
    "requests" => 0
];

$error = "";

try {

    $stmt = $pdo->prepare(
        "SELECT
            id,
            full_name,
            email,
            role,
            created_at,
            updated_at
         FROM users
         WHERE id = ?
           AND role = 'admin'
         LIMIT 1"
    );

    $stmt->execute([
        (int) $_SESSION["admin_id"]
    ]);

    $admin = $stmt->fetch();

    if (!$admin) {
        session_unset();
        session_destroy();

        header("Location: login.php");
        exit;
    }


    $stmt = $pdo->query(
        "SELECT COUNT(*) AS total
         FROM users
         WHERE role = 'user'"
    );

    $system["users"] =
        (int) $stmt->fetch()["total"];


    $stmt = $pdo->query(
        "SELECT COUNT(*) AS total
         FROM services
         WHERE is_active = 1"
    );

    $system["services"] =
        (int) $stmt->fetch()["total"];


    $stmt = $pdo->query(
        "SELECT COUNT(*) AS total
         FROM service_requests"
    );

    $system["requests"] =
        (int) $stmt->fetch()["total"];


} catch (Throwable $e) {

    error_log(
        "Admin Settings Error: " .
        $e->getMessage()
    );

    $error =
        "Unable to load system information.";
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
        Settings — KHAN SOLUTIONS Admin
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
            width: min(1000px, 94%);
            margin: 35px auto;
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

        .alert {
            padding: 14px;
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .section {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow:
                0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .section h3 {
            margin-top: 0;
            margin-bottom: 20px;
        }

        .profile-grid {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .field {
            padding: 14px;
            background: #f9fafb;
            border-radius: 8px;
        }

        .label {
            display: block;
            color: #6b7280;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .value {
            word-break: break-word;
        }

        .role {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            background: #dbeafe;
            color: #1e40af;
            font-size: 12px;
            font-weight: 700;
        }

        .system-grid {
            display: grid;
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .system-card {
            padding: 20px;
            background: #f9fafb;
            border: 1px solid #eef0f2;
            border-radius: 10px;
        }

        .system-card strong {
            display: block;
            font-size: 28px;
            margin-bottom: 6px;
        }

        .system-card span {
            color: #6b7280;
            font-size: 13px;
        }

        .security-note {
            line-height: 1.7;
            color: #4b5563;
        }

        @media (max-width: 700px) {

            .profile-grid,
            .system-grid {
                grid-template-columns: 1fr;
            }

            nav a {
                display: inline-block;
                margin-bottom: 10px;
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

        <h2>
            Settings
        </h2>

        <p>
            Administrator and system information.
        </p>

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


    <?php if ($admin): ?>

        <section class="section">

            <h3>
                Administrator Account
            </h3>

            <div class="profile-grid">

                <div class="field">

                    <span class="label">
                        Administrator ID
                    </span>

                    <span class="value">

                        #<?= (int) $admin["id"] ?>

                    </span>

                </div>


                <div class="field">

                    <span class="label">
                        Full Name
                    </span>

                    <span class="value">

                        <?= htmlspecialchars(
                            $admin["full_name"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </span>

                </div>


                <div class="field">

                    <span class="label">
                        Email
                    </span>

                    <span class="value">

                        <?= htmlspecialchars(
                            $admin["email"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </span>

                </div>


                <div class="field">

                    <span class="label">
                        Role
                    </span>

                    <span class="value">

                        <span class="role">

                            <?= htmlspecialchars(
                                ucfirst(
                                    $admin["role"]
                                ),
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </span>

                    </span>

                </div>


                <div class="field">

                    <span class="label">
                        Account Created
                    </span>

                    <span class="value">

                        <?= htmlspecialchars(
                            $admin["created_at"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </span>

                </div>


                <div class="field">

                    <span class="label">
                        Last Updated
                    </span>

                    <span class="value">

                        <?= htmlspecialchars(
                            $admin["updated_at"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </span>

                </div>

            </div>

        </section>


        <section class="section">

            <h3>
                System Overview
            </h3>

            <div class="system-grid">

                <div class="system-card">

                    <strong>
                        <?= $system["users"] ?>
                    </strong>

                    <span>
                        Client Accounts
                    </span>

                </div>


                <div class="system-card">

                    <strong>
                        <?= $system["services"] ?>
                    </strong>

                    <span>
                        Active Services
                    </span>

                </div>


                <div class="system-card">

                    <strong>
                        <?= $system["requests"] ?>
                    </strong>

                    <span>
                        Service Requests
                    </span>

                </div>

            </div>

        </section>


        <section class="section">

            <h3>
                Security
            </h3>

            <div class="security-note">

                <p>
                    Administrator access is protected by
                    session-based authentication.
                </p>

                <p>
                    Password credentials are stored as secure
                    password hashes and are never displayed
                    in the administration interface.
                </p>

                <p>
                    Use the Logout option whenever you finish
                    an administration session.
                </p>

            </div>

        </section>

    <?php endif; ?>

</div>

</body>

</html>