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

$requestId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$requestId || $requestId < 1) {
    header("Location: requests.php");
    exit;
}

$error = "";
$request = null;

try {

    $stmt = $pdo->prepare(
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
         WHERE sr.id = :id
         LIMIT 1"
    );

    $stmt->execute([
        ":id" => $requestId
    ]);

    $request = $stmt->fetch();
    $historyStmt = $pdo->prepare(
    "SELECT
        aa.action,
        aa.created_at,
        u.full_name AS admin_name
     FROM admin_actions aa
     INNER JOIN users u ON u.id = aa.admin_id
     WHERE aa.request_id = :request_id
     ORDER BY aa.created_at DESC"
);

$historyStmt->execute([
    ":request_id" => $requestId
]);

$history = $historyStmt->fetchAll();
       $attachmentStmt = $pdo->prepare(
        "SELECT
            id,
            file_name,
            file_type,
            created_at
         FROM request_attachments
         WHERE request_id = :request_id
         ORDER BY created_at DESC"
    );

    $attachmentStmt->execute([
        ":request_id" => $requestId

    ]);

    $attachments = $attachmentStmt->fetchAll();
    if (!$request) {
        $error = "Service request not found.";
    }

} catch (Throwable $e) {

    error_log(
        "Request Details Error: " .
        $e->getMessage()
    );

    $error = "Unable to load request details.";
}

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
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
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="request-details.css">

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Request #<?= e($requestId) ?> - Admin
    </title>
    
</head>

<body class="admin-request-details">

<div class="container">

    <div class="topbar">

        <h1 class="title">
            Request #<?= e($requestId) ?>
        </h1>

        <a
            href="requests.php"
            class="back-button"
        >
            ← Back to Requests
        </a>

    </div>

    <?php if ($error !== ""): ?>

        <div class="card">

            <div class="error">
                <?= e($error) ?>
            </div>

        </div>

    <?php elseif ($request): ?>

        <div class="card">

            <h2 class="card-title">
                Client Information
            </h2>

            <div class="grid">

                <div class="field">
                    <span class="label">
                        Full Name
                    </span>

                    <div class="value">
                        <?= e($request["full_name"]) ?>
                    </div>
                </div>

                <div class="field">
                    <span class="label">
                        Email
                    </span>

                    <div class="value">
                        <?= e($request["email"]) ?>
                    </div>
                </div>

                <div class="field">
                    <span class="label">
                        Phone
                    </span>

                    <div class="value">
                        <?= e($request["phone"]) ?>
                    </div>
                </div>

                <div class="field">
                    <span class="label">
                        Location
                    </span>

                    <div class="value">
                        <?= e($request["location"]) ?>
                    </div>
                </div>

            </div>

        </div>


        <div class="card">

            <h2 class="card-title">
                Request Information
            </h2>

            <div class="grid">

                <div class="field">
                    <span class="label">
                        Service
                    </span>

                    <div class="value">
                        <?= e($request["service_name"]) ?>
                    </div>
                </div>

                <div class="field">
                    <span class="label">
                        Title
                    </span>

                    <div class="value">
                        <?= e($request["title"]) ?>
                    </div>
                </div>

                <div class="field">
                    <span class="label">
                        Urgency
                    </span>

                    <div class="value">
                        <?= e(
                            urgencyLabel(
                                $request["urgency"]
                            )
                        ) ?>
                    </div>
                </div>

                <div class="field">
                    <span class="label">
                        Status
                    </span>

                    <div class="value">

                        <span class="status">
                            <?= e(
                                statusLabel(
                                    $request["status"]
                                )
                            ) ?>
                        </span>

                    </div>
                </div>

                <div class="field">
                    <span class="label">
                        Created At
                    </span>

                    <div class="value">
                        <?= e(
                            $request["created_at"]
                        ) ?>
                    </div>
                </div>

                <div class="field">
                    <span class="label">
                        Updated At
                    </span>

                    <div class="value">
                        <?= e(
                            $request["updated_at"]
                        ) ?>
                    </div>
                </div>

            </div>

        </div>


        <div class="card">

            <h2 class="card-title">
                Description / Message
            </h2>

            <div class="field">

                <div class="value description">
                    <?= e(
                        $request["description"]
                    ) ?>
                </div>

            </div>

        </div>

    <?php endif; ?>
                     <div class="card">

            <h2 class="card-title">
                Attachments
            </h2>

            <?php if (!$attachments): ?>

                <div class="value">
                    No attachments submitted.
                </div>

            <?php else: ?>

                <div class="history-list">

                    <?php foreach ($attachments as $attachment): ?>

                        <div class="history-item">

                            <div class="history-action">
                                <?= e($attachment["file_name"]) ?>
                            </div>

                            <div class="history-meta">
                                Type:
                                <?= e($attachment["file_type"]) ?>
                            </div>

                            <div class="history-meta">
                                Uploaded:
                                <?= e($attachment["created_at"]) ?>
                            </div>

                            <div class="history-meta">

                                <a
                                    href="request-attachment.php?id=<?= e($attachment["id"]) ?>"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    View Attachment
                                </a>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>
              <div class="card">
            <h2 class="card-title">
                Request History
            </h2>

            <?php if (!$history): ?>

                <div class="value">
                    No status history available.
                </div>

            <?php else: ?>

                <div class="history-list">

                    <?php foreach ($history as $item): ?>

                        <div class="history-item">

                            <div class="history-action">
                                <?= htmlspecialchars($item["action"]) ?>
                            </div>

                            <div class="history-meta">
                                Admin:
                                <?= htmlspecialchars($item["admin_name"]) ?>
                            </div>

                            <div class="history-meta">
                                <?= htmlspecialchars($item["created_at"]) ?>
                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>
</div>

</body>

</html>
