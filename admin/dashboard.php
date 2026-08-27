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

$adminName = $_SESSION["admin_name"] ?? "Administrator";

$totalRequests = 0;
$pendingRequests = 0;
$inProgressRequests = 0;
$completedRequests = 0;
$totalUsers = 0;
$totalServices = 0;

try {

    $stmt = $pdo->query(
        "SELECT
            COUNT(*) AS total_requests,
            SUM(status = 'pending') AS pending_requests,
            SUM(status = 'in_progress') AS in_progress_requests,
            SUM(status = 'completed') AS completed_requests
         FROM service_requests"
    );

    $stats = $stmt->fetch();

    $totalRequests = (int) ($stats["total_requests"] ?? 0);
    $pendingRequests = (int) ($stats["pending_requests"] ?? 0);
    $inProgressRequests = (int) ($stats["in_progress_requests"] ?? 0);
    $completedRequests = (int) ($stats["completed_requests"] ?? 0);

    $stmt = $pdo->query(
        "SELECT COUNT(*) AS total_users
         FROM users
         WHERE role = 'user'"
    );

    $totalUsers = (int) $stmt->fetch()["total_users"];

    $stmt = $pdo->query(
        "SELECT COUNT(*) AS total_services
         FROM services
         WHERE is_active = 1"
    );

    $totalServices = (int) $stmt->fetch()["total_services"];

} catch (Throwable $e) {

    error_log(
        "Admin Dashboard Error: " . $e->getMessage()
    );
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
        Dashboard — KHAN SOLUTIONS Admin
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

        .container {
            width: min(1200px, 90%);
            margin: 35px auto;
        }

        .welcome {
            margin-bottom: 30px;
        }

        .welcome h2 {
            margin: 0 0 8px;
            font-size: 28px;
        }

        .welcome p {
            margin: 0;
            color: #6b7280;
        }

        .stats {
            display: grid;
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 22px;
            box-shadow:
                0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .stat-label {
            margin: 0 0 10px;
            color: #6b7280;
            font-size: 14px;
            font-weight: 600;
        }

        .stat-value {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
        }

        .stat-description {
            margin: 8px 0 0;
            color: #9ca3af;
            font-size: 13px;
        }

        .section {
            margin-top: 35px;
        }

        .section h3 {
            margin-bottom: 18px;
        }

        .quick-links {
            display: grid;
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .quick-link {
            display: block;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            text-decoration: none;
            color: #1f2937;
        }

        .quick-link:hover {
            border-color: #9ca3af;
        }

        .quick-link strong {
            display: block;
            margin-bottom: 6px;
        }

        .quick-link span {
            color: #6b7280;
            font-size: 14px;
        }

        .logout {
            color: #b91c1c;
        }

        footer {
            margin-top: 50px;
            padding: 25px;
            text-align: center;
            color: #9ca3af;
            font-size: 13px;
        }

        @media (max-width: 850px) {

            .stats,
            .quick-links {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 550px) {

            .stats,
            .quick-links {
                grid-template-columns: 1fr;
            }

            nav a {
                display: inline-block;
                margin-bottom: 10px;
            }

            .welcome h2 {
                font-size: 23px;
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

    <section class="welcome">

        <h2>
            Welcome,
            <?= htmlspecialchars(
                $adminName,
                ENT_QUOTES,
                "UTF-8"
            ) ?>
        </h2>

        <p>
            Here's an overview of your service request system.
        </p>

    </section>


    <section class="stats">

        <article class="stat-card">

            <p class="stat-label">
                Total Requests
            </p>

            <p class="stat-value">
                <?= $totalRequests ?>
            </p>

            <p class="stat-description">
                All service requests
            </p>

        </article>


        <article class="stat-card">

            <p class="stat-label">
                Pending
            </p>

            <p class="stat-value">
                <?= $pendingRequests ?>
            </p>

            <p class="stat-description">
                Awaiting action
            </p>

        </article>


        <article class="stat-card">

            <p class="stat-label">
                In Progress
            </p>

            <p class="stat-value">
                <?= $inProgressRequests ?>
            </p>

            <p class="stat-description">
                Currently being handled
            </p>

        </article>


        <article class="stat-card">

            <p class="stat-label">
                Completed
            </p>

            <p class="stat-value">
                <?= $completedRequests ?>
            </p>

            <p class="stat-description">
                Successfully completed
            </p>

        </article>


        <article class="stat-card">

            <p class="stat-label">
                Clients
            </p>

            <p class="stat-value">
                <?= $totalUsers ?>
            </p>

            <p class="stat-description">
                Registered clients
            </p>

        </article>


        <article class="stat-card">

            <p class="stat-label">
                Active Services
            </p>

            <p class="stat-value">
                <?= $totalServices ?>
            </p>

            <p class="stat-description">
                Available services
            </p>

        </article>

    </section>


    <section class="section">

        <h3>
            Quick Access
        </h3>

        <div class="quick-links">

            <a
                class="quick-link"
                href="requests.php"
            >

                <strong>
                    Service Requests
                </strong>

                <span>
                    View and manage client requests.
                </span>

            </a>


            <a
                class="quick-link"
                href="services.php"
            >

                <strong>
                    Services
                </strong>

                <span>
                    Manage available KHAN SOLUTIONS services.
                </span>

            </a>


            <a
                class="quick-link"
                href="users.php"
            >

                <strong>
                    Clients
                </strong>

                <span>
                    View registered client accounts.
                </span>

            </a>

        </div>

    </section>

</div>


<footer>

    KHAN SOLUTIONS Administration System

</footer>

</body>

</html>