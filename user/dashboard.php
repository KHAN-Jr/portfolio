<?php

require_once __DIR__ . "/../config/security.php";
require_once __DIR__ . "/../config/database.php";

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "user"
) {
    header("Location: ../login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];

$userStmt = $pdo->prepare(
    "SELECT
        id,
        full_name,
        email,
        phone
     FROM users
     WHERE id = :user_id
     LIMIT 1"
);

$userStmt->execute([
    ":user_id" => $userId
]);

$user = $userStmt->fetch();

if (!$user) {
    header("Location: logout.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Request statistics
|--------------------------------------------------------------------------
*/

$statsStmt = $pdo->prepare(
    "SELECT
        COUNT(*) AS total,
        SUM(status = 'Pending') AS pending,
        SUM(status = 'In Progress') AS in_progress,
        SUM(status = 'Completed') AS completed,
        SUM(status = 'Cancelled') AS cancelled
     FROM service_requests
     WHERE user_id = :user_id"
);

$statsStmt->execute([
    ":user_id" => $userId
]);

$stats = $statsStmt->fetch();

$total = (int) ($stats["total"] ?? 0);
$pending = (int) ($stats["pending"] ?? 0);
$inProgress = (int) ($stats["in_progress"] ?? 0);
$completed = (int) ($stats["completed"] ?? 0);
$cancelled = (int) ($stats["cancelled"] ?? 0);

/*
|--------------------------------------------------------------------------
| Recent requests
|--------------------------------------------------------------------------
*/

$requestStmt = $pdo->prepare(
    "SELECT
        sr.id,
        sr.title,
        sr.status,
        sr.urgency,
        sr.created_at,
        s.name AS service_name
     FROM service_requests sr
     INNER JOIN services s
        ON sr.service_id = s.id
     WHERE sr.user_id = :user_id
     ORDER BY sr.created_at DESC
     LIMIT 6"
);

$requestStmt->execute([
    ":user_id" => $userId
]);

$requests = $requestStmt->fetchAll();

function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? "",
        ENT_QUOTES,
        "UTF-8"
    );
}

function statusClass(string $status): string
{
    return strtolower(
        str_replace(" ", "-", $status)
    );
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="assets/css/client.css">

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Client Dashboard | KHAN SOLUTIONS
    </title>

    <style>
        .layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 245px;
            background: #111827;
            border-right: 1px solid #1f2937;
            padding: 22px 15px;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
        }

        .brand {
            padding: 5px 10px 25px;
        }

        .brand h2 {
            font-size: 20px;
        }

        .brand span {
            color: #60a5fa;
        }

        .brand p {
            color: #64748b;
            font-size: 12px;
            margin-top: 5px;
        }

        .nav {
            display: grid;
            gap: 6px;
        }

        .nav a {
            color: #cbd5e1;
            text-decoration: none;
            padding: 12px;
            border-radius: 9px;
            font-size: 14px;
        }

        .nav a:hover,
        .nav a.active {
            background: #1e293b;
            color: #fff;
        }

        .nav .logout {
            margin-top: 15px;
            color: #fca5a5;
        }

        .main {
            margin-left: 245px;
            width: calc(100% - 245px);
            padding: 25px;
        }

        .welcome h1 {
            font-size: 27px;
        }

        .welcome p {
            color: #94a3b8;
            margin-top: 6px;
        }

        .new-request {
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            padding: 11px 16px;
            border-radius: 9px;
            font-weight: bold;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 14px;
            margin-bottom: 22px;
        }

        .stat {
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 13px;
            padding: 18px;
        }

        .stat-label {
            color: #94a3b8;
            font-size: 13px;
        }

        .stat-number {
            font-size: 27px;
            font-weight: bold;
            margin-top: 8px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .card-header h2 {
            font-size: 18px;
        }

        .card-header a {
            color: #60a5fa;
            text-decoration: none;
            font-size: 13px;
        }

        .requests {
            display: grid;
            gap: 10px;
        }

        .request {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            background: #0b1220;
            border: 1px solid #1f2937;
            border-radius: 10px;
            padding: 14px;
        }

        .request-title {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .request-service {
            color: #60a5fa;
            font-size: 12px;
        }

        .request-date {
            color: #64748b;
            font-size: 11px;
            margin-top: 4px;
        }

        .request-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .view {
            color: #60a5fa;
            text-decoration: none;
            font-size: 12px;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        .profile-item {
            background: #0b1220;
            border: 1px solid #1f2937;
            padding: 14px;
            border-radius: 10px;
        }

        .profile-label {
            color: #64748b;
            font-size: 11px;
            margin-bottom: 6px;
        }

        .profile-value {
            color: #e2e8f0;
            font-size: 14px;
            word-break: break-word;
        }

        @media (max-width: 900px) {
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .profile-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .sidebar {
                position: static;
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #1f2937;
            }

            .layout {
                display: block;
            }

            .main {
                margin-left: 0;
                width: 100%;
                padding: 15px 0;
            }

            .stats {
                grid-template-columns: 1fr 1fr;
            }

            .request {
                align-items: flex-start;
                flex-direction: column;
            }

            .request-right {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>

</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="brand">

            <h2>
                KHAN <span>SOLUTIONS</span>
            </h2>

            <p>Client Portal</p>

        </div>

        <nav class="nav">

            <a
                href="dashboard.php"
                class="active"
            >
                Dashboard
            </a>

            <a href="requests.php">
                My Requests
            </a>

            <a href="request.php">
                + New Request
            </a>
            <a href="profile.php">
                My Profile
            </a>
            <a href="../index.html">
                Main Website
            </a>

            <a
                href="logout.php"
                class="logout"
            >
                Logout
            </a>

        </nav>

    </aside>


    <!-- MAIN -->

    <main class="main">

        <div class="topbar">

            <div class="welcome">

                <h1>
                    Welcome, <?= e($user["full_name"]) ?>
                </h1>

                <p>
                    Manage your service requests from one place.
                </p>

            </div>

            <a
                href="request.php"
                class="new-request"
            >
                + Submit New Request
            </a>

        </div>


        <!-- STATISTICS -->

        <section class="stats">

            <div class="stat">

                <div class="stat-label">
                    Total Requests
                </div>

                <div class="stat-number">
                    <?= $total ?>
                </div>

            </div>


            <div class="stat">

                <div class="stat-label">
                    Pending
                </div>

                <div class="stat-number">
                    <?= $pending ?>
                </div>

            </div>


            <div class="stat">

                <div class="stat-label">
                    In Progress
                </div>

                <div class="stat-number">
                    <?= $inProgress ?>
                </div>

            </div>


            <div class="stat">

                <div class="stat-label">
                    Completed
                </div>

                <div class="stat-number">
                    <?= $completed ?>
                </div>

            </div>


            <div class="stat">

                <div class="stat-label">
                    Cancelled
                </div>

                <div class="stat-number">
                    <?= $cancelled ?>
                </div>

            </div>

        </section>


        <!-- RECENT REQUESTS -->

        <section class="card">

            <div class="card-header">

                <h2>
                    Recent Requests
                </h2>

                <a href="requests.php">
                    View All
                </a>

            </div>


            <?php if ($requests): ?>

                <div class="requests">

                    <?php foreach ($requests as $request): ?>

                        <div class="request">

                            <div>

                                <div class="request-title">

                                    <?= e(
                                        $request["title"]
                                    ) ?>

                                </div>

                                <div class="request-service">

                                    <?= e(
                                        $request["service_name"]
                                    ) ?>

                                </div>

                                <div class="request-date">

                                    Submitted:
                                    <?= e(
                                        $request["created_at"]
                                    ) ?>

                                </div>

                            </div>


                            <div class="request-right">

                                <span
                                    class="status <?= e(
                                        statusClass(
                                            $request["status"]
                                        )
                                    ) ?>"
                                >
                                    <?= e(
                                        $request["status"]
                                    ) ?>
                                </span>

                                <a
                                    href="request-details.php?id=<?= (int) $request["id"] ?>"
                                    class="view"
                                >
                                    View
                                </a>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="empty">

                    <p>
                        You have not submitted any service request yet.
                    </p>

                    <a href="request.php">
                        Submit Your First Request
                    </a>

                </div>

            <?php endif; ?>

        </section>


        <!-- CLIENT PROFILE -->

        <section class="card">

            <div class="card-header">

                <h2>
                    My Profile
                </h2>

            </div>

            <div class="profile-grid">

                <div class="profile-item">

                    <div class="profile-label">
                        Full Name
                    </div>

                    <div class="profile-value">
                        <?= e($user["full_name"]) ?>
                    </div>

                </div>


                <div class="profile-item">

                    <div class="profile-label">
                        Email
                    </div>

                    <div class="profile-value">
                        <?= e($user["email"]) ?>
                    </div>

                </div>


                <div class="profile-item">

                    <div class="profile-label">
                        Phone
                    </div>

                    <div class="profile-value">
                        <?= e($user["phone"]) ?>
                    </div>

                </div>

            </div>

        </section>

    </main>

</div>

</body>
</html>