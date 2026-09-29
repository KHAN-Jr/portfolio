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

$requests = [];
$services = [];
$error = "";
$success = "";

/*
|--------------------------------------------------------------------------
| Flash messages
|--------------------------------------------------------------------------
*/

if (isset($_GET["status"])) {

    if ($_GET["status"] === "updated") {
        $success = "Request status updated successfully.";
    }

    if ($_GET["status"] === "error") {
        $error = "Unable to update request status.";
    }
}

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");
$filterStatus = trim($_GET["filter_status"] ?? "");
$filterService = trim($_GET["service_id"] ?? "");
$filterUrgency = trim($_GET["urgency"] ?? "");

$page = filter_input(
    INPUT_GET,
    "page",
    FILTER_VALIDATE_INT
);

$page = $page && $page > 0 ? $page : 1;

$perPage = 10;

$allowedStatuses = [
    "pending",
    "in_progress",
    "completed",
    "cancelled"
];

$allowedUrgencies = [
    "low",
    "medium",
    "high"
];

if (!in_array($filterStatus, $allowedStatuses, true)) {
    $filterStatus = "";
}

if (!in_array($filterUrgency, $allowedUrgencies, true)) {
    $filterUrgency = "";
}

if ($filterService !== "" && !ctype_digit($filterService)) {
    $filterService = "";
}

/*
|--------------------------------------------------------------------------
| Helper functions
|--------------------------------------------------------------------------
*/

function statusLabel(string $status): string
{
    return match ($status) {
        "in_progress" => "In Progress",
        "completed" => "Completed",
        "cancelled" => "Cancelled",
        default => "Pending"
    };
}

function urgencyLabel(string $urgency): string
{
    return ucfirst($urgency);
}

function cleanOutput($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

/*
|--------------------------------------------------------------------------
| Load services for filter
|--------------------------------------------------------------------------
*/

try {

    $serviceStmt = $pdo->query(
        "SELECT
            id,
            name
         FROM services
         ORDER BY name ASC"
    );

    $services = $serviceStmt->fetchAll();

} catch (Throwable $e) {

    error_log(
        "Admin Requests Services Error: " . $e->getMessage()
    );

    $error = "Unable to load request filters.";
}

/*
|--------------------------------------------------------------------------
| Build request filters
|--------------------------------------------------------------------------
*/

$where = [];
$params = [];

if ($search !== "") {

    $where[] = "(
        sr.title LIKE ?
        OR sr.description LIKE ?
        OR u.full_name LIKE ?
        OR u.email LIKE ?
        OR u.phone LIKE ?
        OR sr.location LIKE ?
    )";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}

if ($filterStatus !== "") {

    $where[] = "sr.status = ?";
    $params[] = $filterStatus;
}

if ($filterService !== "") {

    $where[] = "sr.service_id = ?";
    $params[] = (int) $filterService;
}

if ($filterUrgency !== "") {

    $where[] = "sr.urgency = ?";
    $params[] = $filterUrgency;
}

$whereSql = "";

if (!empty($where)) {
    $whereSql = "WHERE " . implode(" AND ", $where);
}

/*
|--------------------------------------------------------------------------
| Count filtered requests
|--------------------------------------------------------------------------
*/

$totalRequests = 0;
$totalPages = 1;

try {

    $countSql = "
        SELECT COUNT(*)
        FROM service_requests sr
        LEFT JOIN users u
            ON u.id = sr.user_id
        LEFT JOIN services s
            ON s.id = sr.service_id
        $whereSql
    ";

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);

    $totalRequests = (int) $countStmt->fetchColumn();

    $totalPages = max(
        1,
        (int) ceil($totalRequests / $perPage)
    );

    if ($page > $totalPages) {
        $page = $totalPages;
    }

} catch (Throwable $e) {

    error_log(
        "Admin Requests Count Error: " . $e->getMessage()
    );

    $error = "Unable to count service requests.";
}

/*
|--------------------------------------------------------------------------
| Load filtered requests
|--------------------------------------------------------------------------
*/

try {

    $offset = ($page - 1) * $perPage;

    $requestSql = "
        SELECT
            sr.id,
            sr.title,
            sr.description,
            sr.location,
            sr.urgency,
            sr.status,
            sr.created_at,
            sr.updated_at,
            u.full_name,
            u.email,
            u.phone,
            s.name AS service_name
        FROM service_requests sr
        LEFT JOIN users u
            ON u.id = sr.user_id
        LEFT JOIN services s
            ON s.id = sr.service_id
        $whereSql
        ORDER BY sr.created_at DESC
        LIMIT $perPage OFFSET $offset
    ";

    $stmt = $pdo->prepare($requestSql);
    $stmt->execute($params);

    $requests = $stmt->fetchAll();

} catch (Throwable $e) {

    error_log(
        "Admin Requests Error: " . $e->getMessage()
    );

    $error = "Unable to load service requests.";
}

/*
|--------------------------------------------------------------------------
| Pagination URL
|--------------------------------------------------------------------------
*/

function pageUrl(
    int $pageNumber,
    string $search,
    string $filterStatus,
    string $filterService,
    string $filterUrgency
): string {

    $query = [
        "page" => $pageNumber
    ];

    if ($search !== "") {
        $query["search"] = $search;
    }

    if ($filterStatus !== "") {
        $query["filter_status"] = $filterStatus;
    }

    if ($filterService !== "") {
        $query["service_id"] = $filterService;
    }

    if ($filterUrgency !== "") {
        $query["urgency"] = $filterUrgency;
    }

    return "requests.php?" . http_build_query($query);
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Requests | KHAN SOLUTIONS
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family:
                Arial,
                Helvetica,
                sans-serif;
            background:
                #0f172a;
            color: #f8fafc;
        }

        header {
            padding: 25px 20px;
            text-align: center;
            background: #111827;
            border-bottom:
                1px solid
                rgba(255, 255, 255, 0.08);
        }

        header h1 {
            margin: 0;
            font-size: 1.7rem;
        }

        header p {
            margin: 6px 0 0;
            color: #94a3b8;
        }

        nav {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            padding: 14px 20px;
            background: #111827;
            border-bottom:
                1px solid
                rgba(255, 255, 255, 0.08);
        }

        nav a,
        nav button {
            display: inline-block;
            padding: 9px 13px;
            border: 0;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
        }

        nav a {
            color: #cbd5e1;
            background:
                rgba(255, 255, 255, 0.05);
        }

        nav a:hover {
            background:
                rgba(255, 255, 255, 0.1);
        }

        nav form {
            margin: 0;
        }

        nav button {
            color: #fff;
            background: #dc2626;
            cursor: pointer;
        }

        nav button:hover {
            opacity: 0.9;
        }

        .container {
            width: min(1200px, 94%);
            margin: 0 auto;
            padding: 30px 0 50px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 22px;
        }

        .page-header h2 {
            margin: 0;
            font-size: 1.7rem;
        }

        .page-header p {
            margin: 7px 0 0;
            color: #94a3b8;
        }

        .request-count {
            padding: 10px 15px;
            border-radius: 10px;
            background:
                rgba(255, 255, 255, 0.06);
            color: #cbd5e1;
            white-space: nowrap;
            font-weight: 700;
        }

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        .filters {
            margin-bottom: 25px;
            padding: 18px;
            border:
                1px solid
                rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            background:
                rgba(255, 255, 255, 0.04);
        }

        .filter-form {
            display: grid;
            grid-template-columns:
                minmax(220px, 2fr)
                repeat(3, minmax(140px, 1fr))
                auto
                auto;
            gap: 10px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filter-group label {
            font-size: 0.78rem;
            color: #94a3b8;
            font-weight: 700;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 11px 12px;
            border:
                1px solid
                rgba(255, 255, 255, 0.12);
            border-radius: 9px;
            outline: none;
            background: #111827;
            color: #f8fafc;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            border-color: #64748b;
        }

        .filter-button,
        .clear-button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 40px;
            padding: 10px 15px;
            border-radius: 9px;
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
        }

        .filter-button {
            border: 0;
            background: #2563eb;
            color: #fff;
        }

        .filter-button:hover {
            opacity: 0.9;
        }

        .clear-button {
            border:
                1px solid
                rgba(255, 255, 255, 0.12);
            background:
                rgba(255, 255, 255, 0.05);
            color: #cbd5e1;
        }

        /*
        |--------------------------------------------------------------------------
        | Alerts
        |--------------------------------------------------------------------------
        */

        .alert,
        .success-alert {
            margin-bottom: 20px;
            padding: 13px 15px;
            border-radius: 10px;
        }

        .alert {
            background:
                rgba(220, 38, 38, 0.12);
            border:
                1px solid
                rgba(220, 38, 38, 0.3);
            color: #fecaca;
        }

        .success-alert {
            background:
                rgba(34, 197, 94, 0.12);
            border:
                1px solid
                rgba(34, 197, 94, 0.3);
            color: #bbf7d0;
        }

        /*
        |--------------------------------------------------------------------------
        | Request cards
        |--------------------------------------------------------------------------
        */

        .requests {
            display: grid;
            gap: 18px;
        }

        .request-card {
            padding: 20px;
            border:
                1px solid
                rgba(255, 255, 255, 0.08);
            border-radius: 15px;
            background:
                rgba(255, 255, 255, 0.045);
            box-shadow:
                0 10px 30px
                rgba(0, 0, 0, 0.16);
        }

        .request-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 18px;
        }

        .request-id {
            display: block;
            margin-bottom: 5px;
            color: #94a3b8;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .request-title {
            margin: 0;
            font-size: 1.15rem;
            line-height: 1.4;
        }

        .badges {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 7px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 800;
        }

        .status-pending {
            background:
                rgba(234, 179, 8, 0.15);
            color: #fde68a;
        }

        .status-in_progress {
            background:
                rgba(59, 130, 246, 0.15);
            color: #bfdbfe;
        }

        .status-completed {
            background:
                rgba(34, 197, 94, 0.15);
            color: #bbf7d0;
        }

        .status-cancelled {
            background:
                rgba(239, 68, 68, 0.15);
            color: #fecaca;
        }

        .urgency-low {
            background:
                rgba(34, 197, 94, 0.12);
            color: #bbf7d0;
        }

        .urgency-medium {
            background:
                rgba(234, 179, 8, 0.12);
            color: #fde68a;
        }

        .urgency-high {
            background:
                rgba(239, 68, 68, 0.14);
            color: #fecaca;
        }

        .details {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .detail {
            padding: 12px;
            border-radius: 10px;
            background:
                rgba(0, 0, 0, 0.14);
        }

        .detail-label {
            display: block;
            margin-bottom: 5px;
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .detail-value {
            display: block;
            color: #e2e8f0;
            font-size: 0.9rem;
            overflow-wrap: anywhere;
        }

        .description {
            margin-bottom: 18px;
            padding: 14px;
            border-left:
                3px solid
                #475569;
            border-radius: 8px;
            background:
                rgba(0, 0, 0, 0.12);
            color: #cbd5e1;
            line-height: 1.6;
        }

        .description strong {
            color: #f8fafc;
        }

        /*
        |--------------------------------------------------------------------------
        | Actions
        |--------------------------------------------------------------------------
        */

        .request-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            gap: 10px;
            margin-bottom: 14px;
        }

        .status-form {
            display: flex;
            align-items: end;
            gap: 9px;
            margin: 0;
        }

        .status-form div {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .status-form label {
            color: #94a3b8;
            font-size: 0.76rem;
            font-weight: 700;
        }

        .status-form select {
            min-width: 170px;
            padding: 10px 11px;
            border:
                1px solid
                rgba(255, 255, 255, 0.12);
            border-radius: 8px;
            background: #111827;
            color: #f8fafc;
            outline: none;
        }

        .status-form button {
            padding: 10px 14px;
            border: 0;
            border-radius: 8px;
            background: #2563eb;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }

        .status-form button:hover {
            opacity: 0.9;
        }

        .view-details-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 10px 15px;
            border:
                1px solid
                rgba(255, 255, 255, 0.14);
            border-radius: 8px;
            background:
                rgba(255, 255, 255, 0.06);
            color: #f8fafc;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 700;
        }

        .view-details-button:hover {
            background:
                rgba(255, 255, 255, 0.12);
        }

        .submitted {
            color: #64748b;
            font-size: 0.78rem;
        }

        /*
        |--------------------------------------------------------------------------
        | Empty state
        |--------------------------------------------------------------------------
        */

        .empty {
            padding: 50px 20px;
            text-align: center;
            border:
                1px solid
                rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            background:
                rgba(255, 255, 255, 0.04);
        }

        .empty h3 {
            margin: 0 0 8px;
        }

        .empty p {
            margin: 0;
            color: #94a3b8;
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 28px;
        }

        .pagination a,
        .pagination span {
            min-width: 40px;
            padding: 9px 12px;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.86rem;
        }

        .pagination a {
            border:
                1px solid
                rgba(255, 255, 255, 0.1);
            background:
                rgba(255, 255, 255, 0.05);
            color: #cbd5e1;
        }

        .pagination a:hover {
            background:
                rgba(255, 255, 255, 0.1);
        }

        .pagination .current {
            background: #2563eb;
            color: #fff;
        }

        .pagination .disabled {
            opacity: 0.35;
        }

        .pagination-info {
            margin-top: 12px;
            text-align: center;
            color: #64748b;
            font-size: 0.78rem;
        }

        @media (max-width: 900px) {

            .filter-form {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .filter-group.search-group {
                grid-column: 1 / -1;
            }

        }

        @media (max-width: 700px) {

            .page-header,
            .request-top {
                flex-direction: column;
                align-items: flex-start;
            }

            .badges {
                justify-content: flex-start;
            }

            .details {
                grid-template-columns: 1fr;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }

            .filter-group.search-group {
                grid-column: auto;
            }

            .filter-button,
            .clear-button {
                width: 100%;
            }

            .request-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .status-form {
                width: 100%;
                align-items: stretch;
                flex-direction: column;
            }

            .status-form select,
            .status-form button,
            .view-details-button {
                width: 100%;
            }

            .status-form select {
                min-width: 0;
            }

            nav a,
            nav form,
            nav button {
                width: 100%;
            }

            nav a,
            nav button {
                text-align: center;
            }

            nav form {
                margin: 0;
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
            value="<?= cleanOutput(csrfToken()) ?>"
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
                Service Requests
            </h2>

            <p>
                Manage incoming client service requests.
            </p>

        </div>

        <div class="request-count">

            <?= $totalRequests ?>

            Request<?= $totalRequests === 1 ? "" : "s" ?>

        </div>

    </div>

    <?php if ($error !== ""): ?>

        <div class="alert">

            <?= cleanOutput($error) ?>

        </div>

    <?php endif; ?>

    <?php if ($success !== ""): ?>

        <div class="success-alert">

            <?= cleanOutput($success) ?>

        </div>

    <?php endif; ?>

    <!-- FILTERS -->

    <section class="filters">

        <form
            method="GET"
            action="requests.php"
            class="filter-form"
        >

            <div class="filter-group search-group">

                <label for="search">
                    Search Requests
                </label>

                <input
                    type="search"
                    id="search"
                    name="search"
                    value="<?= cleanOutput($search) ?>"
                    placeholder="Title, client, email, phone, location..."
                >

            </div>

            <div class="filter-group">

                <label for="filter_status">
                    Status
                </label>

                <select
                    id="filter_status"
                    name="filter_status"
                >

                    <option value="">
                        All Statuses
                    </option>

                    <?php foreach ($allowedStatuses as $status): ?>

                        <option
                            value="<?= cleanOutput($status) ?>"
                            <?= $filterStatus === $status
                                ? "selected"
                                : "" ?>
                        >
                            <?= cleanOutput(statusLabel($status)) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="filter-group">

                <label for="service_id">
                    Service
                </label>

                <select
                    id="service_id"
                    name="service_id"
                >

                    <option value="">
                        All Services
                    </option>

                    <?php foreach ($services as $service): ?>

                        <option
                            value="<?= (int) $service["id"] ?>"
                            <?= $filterService === (string) $service["id"]
                                ? "selected"
                                : "" ?>
                        >
                            <?= cleanOutput($service["name"]) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="filter-group">

                <label for="urgency">
                    Urgency
                </label>

                <select
                    id="urgency"
                    name="urgency"
                >

                    <option value="">
                        All Urgency
                    </option>

                    <?php foreach ($allowedUrgencies as $urgency): ?>

                        <option
                            value="<?= cleanOutput($urgency) ?>"
                            <?= $filterUrgency === $urgency
                                ? "selected"
                                : "" ?>
                        >
                            <?= cleanOutput(urgencyLabel($urgency)) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <button
                type="submit"
                class="filter-button"
            >
                Filter
            </button>

            <a
                href="requests.php"
                class="clear-button"
            >
                Clear
            </a>

        </form>

    </section>

    <?php if (empty($requests)): ?>

        <div class="empty">

            <h3>
                No Service Requests Found
            </h3>

            <?php if (
                $search !== "" ||
                $filterStatus !== "" ||
                $filterService !== "" ||
                $filterUrgency !== ""
            ): ?>

                <p>
                    No requests match the current search or filters.
                </p>

            <?php else: ?>

                <p>
                    There are currently no service requests.
                </p>

            <?php endif; ?>

        </div>

    <?php else: ?>

        <div class="requests">

            <?php foreach ($requests as $request): ?>

                <article class="request-card">

                    <div class="request-top">

                        <div>

                            <span class="request-id">

                                Request #
                                <?= (int) $request["id"] ?>

                            </span>

                            <h3 class="request-title">

                                <?= cleanOutput($request["title"]) ?>

                            </h3>

                        </div>

                        <div class="badges">

                            <span
                                class="badge status-<?= cleanOutput(
                                    $request["status"]
                                ) ?>"
                            >

                                <?= cleanOutput(
                                    statusLabel(
                                        $request["status"]
                                    )
                                ) ?>

                            </span>

                            <span
                                class="badge urgency-<?= cleanOutput(
                                    $request["urgency"]
                                ) ?>"
                            >

                                <?= cleanOutput(
                                    urgencyLabel(
                                        $request["urgency"]
                                    )
                                ) ?>

                            </span>

                        </div>

                    </div>

                    <div class="details">

                        <div class="detail">

                            <span class="detail-label">
                                Service
                            </span>

                            <span class="detail-value">
                                <?= cleanOutput(
                                    $request["service_name"]
                                ) ?>
                            </span>

                        </div>

                        <div class="detail">

                            <span class="detail-label">
                                Customer
                            </span>

                            <span class="detail-value">
                                <?= cleanOutput(
                                    $request["full_name"]
                                ) ?>
                            </span>

                        </div>

                        <div class="detail">

                            <span class="detail-label">
                                Email
                            </span>

                            <span class="detail-value">
                                <?= cleanOutput(
                                    $request["email"]
                                ) ?>
                            </span>

                        </div>

                        <div class="detail">

                            <span class="detail-label">
                                Phone
                            </span>

                            <span class="detail-value">
                                <?= cleanOutput(
                                    $request["phone"]
                                ) ?>
                            </span>

                        </div>

                        <div class="detail">

                            <span class="detail-label">
                                Location
                            </span>

                            <span class="detail-value">
                                <?= cleanOutput(
                                    $request["location"]
                                ) ?>
                            </span>

                        </div>

                    </div>

                    <div class="description">

                        <strong>
                            Request Details
                        </strong>

                        <br><br>

                        <?= nl2br(
                            cleanOutput(
                                $request["description"]
                            )
                        ) ?>

                    </div>

                    <div class="request-actions">

                        <form
                            class="status-form"
                            method="POST"
                            action="update-request-status.php"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= cleanOutput(
                                    csrfToken()
                                ) ?>"
                            >

                            <input
                                type="hidden"
                                name="request_id"
                                value="<?= (int) $request["id"] ?>"
                            >

                            <div>

                                <label
                                    for="status-<?= (int) $request["id"] ?>"
                                >
                                    Update Status
                                </label>

                                <select
                                    id="status-<?= (int) $request["id"] ?>"
                                    name="status"
                                    required
                                >

                                    <?php foreach (
                                        $allowedStatuses
                                        as $status
                                    ): ?>

                                        <option
                                            value="<?= cleanOutput($status) ?>"
                                            <?= $request["status"] === $status
                                                ? "selected"
                                                : "" ?>
                                        >
                                            <?= cleanOutput(
                                                statusLabel($status)
                                            ) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <button type="submit">
                                Update Status
                            </button>

                        </form>

                        <a
                            href="request-details.php?id=<?= (int) $request["id"] ?>"
                            class="view-details-button"
                        >
                            View Details
                        </a>

                    </div>

                    <div class="submitted">

                        Submitted:
                        <?= cleanOutput(
                            $request["created_at"]
                        ) ?>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

        <?php if ($totalPages > 1): ?>

            <div class="pagination">

                <?php if ($page > 1): ?>

                    <a
                        href="<?= cleanOutput(
                            pageUrl(
                                $page - 1,
                                $search,
                                $filterStatus,
                                $filterService,
                                $filterUrgency
                            )
                        ) ?>"
                    >
                        ← Previous
                    </a>

                <?php else: ?>

                    <span class="disabled">
                        ← Previous
                    </span>

                <?php endif; ?>

                <?php

                $startPage = max(1, $page - 2);
                $endPage = min(
                    $totalPages,
                    $page + 2
                );

                for (
                    $pageNumber = $startPage;
                    $pageNumber <= $endPage;
                    $pageNumber++
                ):
                ?>

                    <?php if ($pageNumber === $page): ?>

                        <span class="current">
                            <?= $pageNumber ?>
                        </span>

                    <?php else: ?>

                        <a
                            href="<?= cleanOutput(
                                pageUrl(
                                    $pageNumber,
                                    $search,
                                    $filterStatus,
                                    $filterService,
                                    $filterUrgency
                                )
                            ) ?>"
                        >
                            <?= $pageNumber ?>
                        </a>

                    <?php endif; ?>

                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>

                    <a
                        href="<?= cleanOutput(
                            pageUrl(
                                $page + 1,
                                $search,
                                $filterStatus,
                                $filterService,
                                $filterUrgency
                            )
                        ) ?>"
                    >
                        Next →
                    </a>

                <?php else: ?>

                    <span class="disabled">
                        Next →
                    </span>

                <?php endif; ?>

            </div>

            <div class="pagination-info">

                Page <?= $page ?>
                of <?= $totalPages ?>

                ·

                Showing
                <?= min(
                    $totalRequests,
                    $offset + 1
                ) ?>

                -
                <?= min(
                    $totalRequests,
                    $offset + count($requests)
                ) ?>

                of <?= $totalRequests ?> requests

            </div>

        <?php endif; ?>

    <?php endif; ?>

</div>

</body>

</html>