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

$id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$id || $id < 1) {
    header("Location: services.php");
    exit;
}

$service = null;
$requests = [];
$error = "";

try {

    /*
     * Load service information
     */
    $stmt = $pdo->prepare(
        "SELECT
            id,
            name,
            slug,
            description,
            icon,
            is_active,
            created_at
         FROM services
         WHERE id = :id
         LIMIT 1"
    );

    $stmt->execute([
        ":id" => $id
    ]);

    $service = $stmt->fetch();

    if (!$service) {
        header("Location: services.php");
        exit;
    }


    /*
     * Load requests belonging to this service
     */
    $stmt = $pdo->prepare(
        "SELECT
            sr.id,
            sr.title,
            sr.location,
            sr.urgency,
            sr.status,
            sr.created_at,
            u.full_name,
            u.email
         FROM service_requests sr
         LEFT JOIN users u
            ON u.id = sr.user_id
         WHERE sr.service_id = :service_id
         ORDER BY sr.id DESC"
    );

    $stmt->execute([
        ":service_id" => $id
    ]);

    $requests = $stmt->fetchAll();

} catch (Throwable $e) {

    error_log(
        "Service Details Error: " .
        $e->getMessage()
    );

    $error = "Unable to load service details.";
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
        Service Details — KHAN SOLUTIONS Admin
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            color: #111827;
        }

        .container {
            width: min(1000px, 92%);
            margin: 40px auto;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .back {
            display: inline-block;
            padding: 9px 13px;
            background: #e5e7eb;
            color: #111827;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
        }

        .card {
            background: #ffffff;
            padding: 24px;
            border-radius: 14px;
            box-shadow:
                0 10px 30px rgba(0, 0, 0, 0.06);
            margin-bottom: 20px;
        }

        h2,
        h3 {
            margin-top: 0;
        }

        .subtitle {
            color: #6b7280;
        }

        .details {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-top: 20px;
        }

        .field {
            padding: 15px;
            background: #f9fafb;
            border-radius: 9px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .label {
            display: block;
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 6px;
            font-weight: 700;
        }

        .value {
            font-weight: 600;
            word-break: break-word;
        }

        .description {
            line-height: 1.6;
            font-weight: 400;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
        }

        .status.active {
            background: #dcfce7;
            color: #166534;
        }

        .status.inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .request-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .request-table th,
        .request-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            vertical-align: top;
        }

        .request-table th {
            font-size: 13px;
            color: #6b7280;
        }

        .request-title {
            font-weight: 700;
        }

        .request-client {
            font-size: 14px;
        }

        .request-email {
            color: #6b7280;
            font-size: 13px;
            margin-top: 3px;
        }

        .request-status {
            font-weight: 700;
            font-size: 13px;
        }

        .empty {
            padding: 25px;
            text-align: center;
            background: #f9fafb;
            border-radius: 10px;
            color: #6b7280;
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        @media (max-width: 700px) {

            .details {
                grid-template-columns: 1fr;
            }

            .field.full {
                grid-column: auto;
            }

            .request-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            .top-bar {
                align-items: flex-start;
                flex-direction: column;
            }
        }

    </style>

</head>

<body>

<div class="container">

    <div class="top-bar">

        <div>

            <h2>
                Service Details
            </h2>

            <?php if ($service): ?>

                <div class="subtitle">

                    <?= htmlspecialchars(
                        $service["name"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>

                </div>

            <?php endif; ?>

        </div>

        <a
            href="services.php"
            class="back"
        >
            Back to Services
        </a>

    </div>


    <?php if ($error !== ""): ?>

        <div class="alert error">

            <?= htmlspecialchars(
                $error,
                ENT_QUOTES,
                "UTF-8"
            ) ?>

        </div>

    <?php endif; ?>


    <?php if ($service): ?>

        <div class="card">

            <h3>
                Service Information
            </h3>

            <div class="details">

                <div class="field">

                    <span class="label">
                        Service ID
                    </span>

                    <span class="value">
                        #<?= (int) $service["id"] ?>
                    </span>

                </div>


                <div class="field">

                    <span class="label">
                        Status
                    </span>

                    <span class="status <?= (int) $service["is_active"] === 1
                        ? "active"
                        : "inactive" ?>">

                        <?= (int) $service["is_active"] === 1
                            ? "Active"
                            : "Inactive" ?>

                    </span>

                </div>


                <div class="field">

                    <span class="label">
                        Name
                    </span>

                    <span class="value">

                        <?= htmlspecialchars(
                            $service["name"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </span>

                </div>


                <div class="field">

                    <span class="label">
                        Slug
                    </span>

                    <span class="value">

                        <?= htmlspecialchars(
                            $service["slug"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </span>

                </div>


                <div class="field">

                    <span class="label">
                        Icon
                    </span>

                    <span class="value">

                        <?= htmlspecialchars(
                            $service["icon"] ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?: "Not configured" ?>

                    </span>

                </div>


                <div class="field">

                    <span class="label">
                        Created
                    </span>

                    <span class="value">

                        <?= htmlspecialchars(
                            $service["created_at"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </span>

                </div>


                <div class="field full">

                    <span class="label">
                        Description
                    </span>

                    <div class="description">

                        <?php

                        $description =
                            trim(
                                (string)
                                ($service["description"] ?? "")
                            );

                        echo $description !== ""
                            ? nl2br(
                                htmlspecialchars(
                                    $description,
                                    ENT_QUOTES,
                                    "UTF-8"
                                )
                            )
                            : "No description available.";

                        ?>

                    </div>

                </div>

            </div>

        </div>


        <div class="card">

            <h3>
                Service Requests
                (<?= count($requests) ?>)
            </h3>

            <?php if (empty($requests)): ?>

                <div class="empty">

                    No service requests are associated
                    with this service yet.

                </div>

            <?php else: ?>

                <div style="overflow-x: auto;">

                    <table class="request-table">

                        <thead>

                            <tr>

                                <th>
                                    Request
                                </th>

                                <th>
                                    Client
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

                                        <div class="request-title">

                                            #<?= (int) $request["id"] ?>

                                            —

                                            <?= htmlspecialchars(
                                                $request["title"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </div>

                                    </td>


                                    <td>

                                        <div class="request-client">

                                            <?= htmlspecialchars(
                                                $request["full_name"] ?? "Unknown",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </div>

                                        <div class="request-email">

                                            <?= htmlspecialchars(
                                                $request["email"] ?? "",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </div>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $request["location"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?: "—" ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $request["urgency"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?: "—" ?>

                                    </td>


                                    <td>

                                        <span class="request-status">

                                            <?= htmlspecialchars(
                                                $request["status"] ?? "",
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
