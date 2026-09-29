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

$stmt = $pdo->prepare(
    "SELECT
        sr.id,
        sr.title,
        sr.location,
        sr.urgency,
        sr.status,
        sr.created_at,
        s.name AS service_name
     FROM service_requests sr
     INNER JOIN services s
        ON sr.service_id = s.id
     WHERE sr.user_id = :user_id
     ORDER BY sr.created_at DESC"
);

$stmt->execute([
    ":user_id" => $userId
]);

$requests = $stmt->fetchAll();

function statusClass(string $status): string
{
    return strtolower(str_replace(" ", "-", $status));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="assets/css/client.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Requests | KHAN SOLUTIONS</title>

    <style>
        .requests {
            display: grid;
            gap: 15px;
        }

        .request-card {
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 14px;
            padding: 20px;
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .request-id {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .request-title {
            font-size: 19px;
            margin-bottom: 7px;
        }

        .service {
            color: #60a5fa;
            font-size: 14px;
        }

        .details {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
            padding-top: 15px;
            border-top: 1px solid #1f2937;
        }

        .detail-label {
            display: block;
            color: #64748b;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .detail-value {
            color: #e2e8f0;
            font-size: 14px;
        }

        @media (max-width: 700px) {
            .details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="topbar">

        <div>
            <h1>My Service Requests</h1>
            <p>Track and manage your submitted service requests.</p>
        </div>

        <div class="actions">
            <a href="dashboard.php" class="btn">Dashboard</a>
            <a href="request.php" class="btn primary">+ New Request</a>
        </div>

    </div>

    <?php if (!$requests): ?>

        <div class="empty">
            <h2>No Service Requests Yet</h2>
            <p>You have not submitted any service request.</p>

            <a href="request.php" class="btn primary">
                Submit Your First Request
            </a>
        </div>

    <?php else: ?>

        <div class="requests">

            <?php foreach ($requests as $request): ?>

                <div class="request-card">

                    <div class="request-header">

                        <div>
                            <div class="request-id">
                                Request #<?= (int) $request["id"] ?>
                            </div>

                            <div class="request-title">
                                <?= htmlspecialchars(
                                    $request["title"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </div>

                            <div class="service">
                                <?= htmlspecialchars(
                                    $request["service_name"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </div>
                        </div>

                        <span class="status <?= htmlspecialchars(
                            statusClass($request["status"]),
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>">
                            <?= htmlspecialchars(
                                $request["status"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </span>

                    </div>

                    <div class="details">

                        <div>
                            <span class="detail-label">Location</span>

                            <span class="detail-value">
                                <?= htmlspecialchars(
                                    $request["location"] ?: "Not specified",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </span>
                        </div>

                        <div>
                            <span class="detail-label">Urgency</span>

                            <span class="detail-value">
                                <?= htmlspecialchars(
                                    ucfirst($request["urgency"]),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </span>
                        </div>

                        <div>
                            <span class="detail-label">Submitted</span>

                            <span class="detail-value">
                                <?= htmlspecialchars(
                                    $request["created_at"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </span>
                        </div>

                    </div>

                    <a
                        href="request-details.php?id=<?= (int) $request["id"] ?>"
                        class="view-btn"
                    >
                        View Details
                    </a>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

</body>
</html>
