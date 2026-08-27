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
$error = "";
$success = "";

if (isset($_GET["status"])) {

    if ($_GET["status"] === "updated") {
        $success = "Request status updated successfully.";
    }

    if ($_GET["status"] === "error") {
        $error = "Unable to update request status.";
    }
}

try {

    $stmt = $pdo->query(
        "SELECT
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
         INNER JOIN users u
            ON u.id = sr.user_id
         INNER JOIN services s
            ON s.id = sr.service_id
         ORDER BY sr.created_at DESC"
    );

    $requests = $stmt->fetchAll();

} catch (Throwable $e) {

    error_log(
        "Admin Requests Error: " . $e->getMessage()
    );

    $error = "Unable to load service requests.";
}

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
        Requests — KHAN SOLUTIONS Admin
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
            padding: 20px;
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
            padding: 14px 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        nav a {
            text-decoration: none;
            margin-right: 18px;
            font-weight: 600;
            color: #374151;
        }

        nav a:hover {
            text-decoration: underline;
        }

        .container {
            width: min(1200px, 94%);
            margin: 30px auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 24px;
        }

        .page-header h2 {
            margin: 0;
        }

        .request-count {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
        }

        .alert {
            padding: 14px;
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .success-alert {
            padding: 14px;
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #166534;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .empty {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            padding: 40px 20px;
            text-align: center;
            border-radius: 12px;
        }

        .requests {
            display: grid;
            gap: 20px;
        }

        .request-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .request-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 18px;
        }

        .request-title {
            margin: 0 0 7px;
            font-size: 19px;
        }

        .request-id {
            color: #6b7280;
            font-size: 14px;
        }

        .badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .badge {
            display: inline-block;
            padding: 6px 10px;
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

        .urgency-low {
            background: #e5e7eb;
            color: #374151;
        }

        .urgency-medium {
            background: #fef3c7;
            color: #92400e;
        }

        .urgency-high {
            background: #fee2e2;
            color: #991b1b;
        }

        .details {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .detail {
            border: 1px solid #eef0f2;
            border-radius: 8px;
            padding: 12px;
        }

        .detail-label {
            display: block;
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .detail-value {
            word-break: break-word;
        }

        .description {
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .status-form {
            display: flex;
            align-items: end;
            gap: 12px;
            flex-wrap: wrap;
            border-top: 1px solid #e5e7eb;
            padding-top: 18px;
        }

        .status-form label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .status-form select {
            min-width: 180px;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            background: #ffffff;
        }

        .status-form button {
            border: 0;
            padding: 10px 16px;
            border-radius: 7px;
            cursor: pointer;
            font-weight: 700;
            background: #111827;
            color: #ffffff;
        }

        .status-form button:hover {
            opacity: 0.9;
        }

        .submitted {
            margin-top: 16px;
            color: #6b7280;
            font-size: 13px;
        }

        @media (max-width: 700px) {

            .page-header,
            .request-top {
                flex-direction: column;
                align-items: flex-start;
            }

            .details {
                grid-template-columns: 1fr;
            }

            .status-form {
                align-items: stretch;
                flex-direction: column;
            }

            .status-form select,
            .status-form button {
                width: 100%;
            }

            nav a {
                display: inline-block;
                margin-bottom: 8px;
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
                Service Requests
            </h2>

            <p>
                Manage incoming client service requests.
            </p>

        </div>

        <div class="request-count">

            <?= count($requests) ?>

            Request<?= count($requests) === 1 ? "" : "s" ?>

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
        <?php if ($success !== ""): ?>

    <div class="success-alert">

        <?= htmlspecialchars(
            $success,
            ENT_QUOTES,
            "UTF-8"
        ) ?>

    </div>

    <?php endif; ?>


    <?php if (empty($requests)): ?>

        <div class="empty">

            <h3>
                No Service Requests
            </h3>

            <p>
                There are currently no service requests.
            </p>

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

                                <?= htmlspecialchars(
                                    $request["title"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </h3>

                        </div>


                        <div class="badges">

                            <span
                                class="badge status-<?= htmlspecialchars(
                                    $request["status"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                            >

                                <?= htmlspecialchars(
                                    statusLabel(
                                        $request["status"]
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </span>


                            <span
                                class="badge urgency-<?= htmlspecialchars(
                                    $request["urgency"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                            >

                                <?= htmlspecialchars(
                                    urgencyLabel(
                                        $request["urgency"]
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
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

                                <?= htmlspecialchars(
                                    $request["service_name"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </span>

                        </div>


                        <div class="detail">

                            <span class="detail-label">
                                Customer
                            </span>

                            <span class="detail-value">

                                <?= htmlspecialchars(
                                    $request["full_name"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </span>

                        </div>


                        <div class="detail">

                            <span class="detail-label">
                                Email
                            </span>

                            <span class="detail-value">

                                <?= htmlspecialchars(
                                    $request["email"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </span>

                        </div>


                        <div class="detail">

                            <span class="detail-label">
                                Phone
                            </span>

                            <span class="detail-value">

                                <?= htmlspecialchars(
                                    $request["phone"] ?? "",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </span>

                        </div>


                        <div class="detail">

                            <span class="detail-label">
                                Location
                            </span>

                            <span class="detail-value">

                                <?= htmlspecialchars(
                                    $request["location"] ?? "",
                                    ENT_QUOTES,
                                    "UTF-8"
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
                            htmlspecialchars(
                                $request["description"] ?? "",
                                ENT_QUOTES,
                                "UTF-8"
                            )
                        ) ?>

                    </div>


                    <form
                        class="status-form"
                        method="POST"
                        action="update-request-status.php"
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

                                <option
                                    value="pending"
                                    <?= $request["status"] === "pending"
                                        ? "selected"
                                        : "" ?>
                                >
                                    Pending
                                </option>

                                <option
                                    value="in_progress"
                                    <?= $request["status"] === "in_progress"
                                        ? "selected"
                                        : "" ?>
                                >
                                    In Progress
                                </option>

                                <option
                                    value="completed"
                                    <?= $request["status"] === "completed"
                                        ? "selected"
                                        : "" ?>
                                >
                                    Completed
                                </option>

                                <option
                                    value="cancelled"
                                    <?= $request["status"] === "cancelled"
                                        ? "selected"
                                        : "" ?>
                                >
                                    Cancelled
                                </option>

                            </select>

                        </div>


                        <button type="submit">
                            Update Status
                        </button>

                    </form>


                    <div class="submitted">

                        Submitted:
                        <?= htmlspecialchars(
                            $request["created_at"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

</body>

</html>