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
$cancelledRequests = 0;

$totalUsers = 0;
$totalServices = 0;
$activeServices = 0;

$recentRequests = [];

try {

    /*
     * REQUEST STATISTICS
     */
    $stmt = $pdo->query(
        "SELECT
            COUNT(*) AS total_requests,
            COALESCE(SUM(status = 'pending'), 0) AS pending_requests,
            COALESCE(SUM(status = 'in_progress'), 0) AS in_progress_requests,
            COALESCE(SUM(status = 'completed'), 0) AS completed_requests,
            COALESCE(SUM(status = 'cancelled'), 0) AS cancelled_requests
         FROM service_requests"
    );

    $stats = $stmt->fetch();

    $totalRequests = (int) ($stats["total_requests"] ?? 0);
    $pendingRequests = (int) ($stats["pending_requests"] ?? 0);
    $inProgressRequests = (int) ($stats["in_progress_requests"] ?? 0);
    $completedRequests = (int) ($stats["completed_requests"] ?? 0);
    $cancelledRequests = (int) ($stats["cancelled_requests"] ?? 0);


    /*
     * CLIENT STATISTICS
     */
    $stmt = $pdo->query(
        "SELECT COUNT(*) AS total_users
         FROM users
         WHERE role = 'user'"
    );

    $totalUsers = (int) $stmt->fetch()["total_users"];


    /*
     * SERVICE STATISTICS
     */
    $stmt = $pdo->query(
        "SELECT
            COUNT(*) AS total_services,
            COALESCE(SUM(is_active = 1), 0) AS active_services
         FROM services"
    );

    $serviceStats = $stmt->fetch();

    $totalServices = (int) ($serviceStats["total_services"] ?? 0);
    $activeServices = (int) ($serviceStats["active_services"] ?? 0);


    /*
     * RECENT SERVICE REQUESTS
     */
    $stmt = $pdo->query(
        "SELECT
            sr.id,
            sr.title,
            sr.location,
            sr.urgency,
            sr.status,
            sr.created_at,
            u.full_name AS client_name,
            s.name AS service_name
         FROM service_requests sr
         LEFT JOIN users u
            ON u.id = sr.user_id
         LEFT JOIN services s
            ON s.id = sr.service_id
         ORDER BY sr.id DESC
         LIMIT 8"
    );

    $recentRequests = $stmt->fetchAll();

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
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        nav a {
            color: #374151;
            text-decoration: none;
            font-weight: 600;
        }

        nav a:hover {
            text-decoration: underline;
        }

        nav form {
            margin: 0;
        }

        nav button {
            border: 0;
            background: transparent;
            color: #b91c1c;
            font-weight: 600;
            cursor: pointer;
            padding: 0;
            font-size: 14px;
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


        /*
         * STATISTICS
         */

        .stats {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
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


        /*
         * SECTIONS
         */

        .section {
            margin-top: 40px;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 18px;
        }

        .section-header h3 {
            margin: 0;
            font-size: 20px;
        }

        .view-all {
            color: #2563eb;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .view-all:hover {
            text-decoration: underline;
        }


        /*
         * RECENT REQUESTS
         */

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
            min-width: 850px;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }

        th {
            background: #f8fafc;
            color: #475569;
            font-size: 13px;
            font-weight: 700;
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        tbody tr:hover {
            background: #fafafa;
        }

        .request-title {
            font-weight: 600;
            color: #111827;
        }

        .client-name {
            color: #374151;
        }

        .service-name {
            color: #64748b;
        }

        .date {
            color: #64748b;
            white-space: nowrap;
        }


        /*
         * STATUS BADGES
         */

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            text-transform: capitalize;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-in-progress {
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

        .status-default {
            background: #e5e7eb;
            color: #374151;
        }


        /*
         * URGENCY
         */

        .urgency {
            font-size: 13px;
            font-weight: 600;
            text-transform: capitalize;
        }


        /*
         * EMPTY STATE
         */

        .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: #6b7280;
        }


        /*
         * QUICK LINKS
         */

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
            box-shadow:
                0 2px 8px rgba(0, 0, 0, 0.03);
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


        /*
         * FOOTER
         */

        footer {
            margin-top: 50px;
            padding: 25px;
            text-align: center;
            color: #9ca3af;
            font-size: 13px;
        }


        /*
         * TABLET
         */

        @media (max-width: 1000px) {

            .stats {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .quick-links {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }


        /*
         * MOBILE
         */

        @media (max-width: 550px) {

            .container {
                width: 92%;
                margin: 25px auto;
            }

            header {
                padding: 20px 4%;
            }

            nav {
                padding: 14px 4%;
                gap: 14px;
            }

            nav a {
                font-size: 14px;
            }

            .welcome h2 {
                font-size: 23px;
            }

            .stats,
            .quick-links {
                grid-template-columns: 1fr;
            }

            .stat-card {
                padding: 18px;
            }

            .stat-value {
                font-size: 28px;
            }

            .section {
                margin-top: 30px;
            }

            .section-header {
                align-items: flex-start;
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


    <!-- WELCOME -->

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


    <!-- STATISTICS -->

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
                Cancelled
            </p>

            <p class="stat-value">
                <?= $cancelledRequests ?>
            </p>

            <p class="stat-description">
                Cancelled requests
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
                Total Services
            </p>

            <p class="stat-value">
                <?= $totalServices ?>
            </p>

            <p class="stat-description">
                Services in system
            </p>

        </article>


        <article class="stat-card">

            <p class="stat-label">
                Active Services
            </p>

            <p class="stat-value">
                <?= $activeServices ?>
            </p>

            <p class="stat-description">
                Currently available
            </p>

        </article>


    </section>


    <!-- RECENT REQUESTS -->

    <section class="section">

        <div class="section-header">

            <h3>
                Recent Service Requests
            </h3>

            <a
                class="view-all"
                href="requests.php"
            >
                View All
            </a>

        </div>


        <div class="table-wrapper">

            <?php if (count($recentRequests) > 0): ?>

                <table>

                    <thead>

                        <tr>

                            <th>
                                ID
                            </th>

                            <th>
                                Client
                            </th>

                            <th>
                                Service
                            </th>

                            <th>
                                Request
                            </th>

                            <th>
                                Urgency
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Date
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($recentRequests as $request): ?>

                            <?php

                            $status = $request["status"] ?? "";

                            $statusClass = "status-default";

                            if ($status === "pending") {
                                $statusClass = "status-pending";
                            } elseif ($status === "in_progress") {
                                $statusClass = "status-in-progress";
                            } elseif ($status === "completed") {
                                $statusClass = "status-completed";
                            } elseif ($status === "cancelled") {
                                $statusClass = "status-cancelled";
                            }

                            ?>

                            <tr>

                                <td>
                                    #<?= (int) $request["id"] ?>
                                </td>


                                <td class="client-name">

                                    <?= htmlspecialchars(
                                        $request["client_name"] ?? "Unknown",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                </td>


                                <td class="service-name">

                                    <?= htmlspecialchars(
                                        $request["service_name"] ?? "Unknown",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                </td>


                                <td class="request-title">

                                    <?= htmlspecialchars(
                                        $request["title"] ?? "",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                </td>


                                <td class="urgency">

                                    <?= htmlspecialchars(
                                        $request["urgency"] ?? "",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                </td>


                                <td>

                                    <span
                                        class="status <?= $statusClass ?>"
                                    >

                                        <?= htmlspecialchars(
                                            str_replace(
                                                "_",
                                                " ",
                                                $status
                                            ),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>

                                    </span>

                                </td>


                                <td class="date">

                                    <?= htmlspecialchars(
                                        $request["created_at"] ?? "",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <div class="empty-state">

                    No service requests found.

                </div>

            <?php endif; ?>

        </div>

    </section>


    <!-- QUICK ACCESS -->

    <section class="section">

        <div class="section-header">

            <h3>
                Quick Access
            </h3>

        </div>


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