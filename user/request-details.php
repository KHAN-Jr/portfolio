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

$requestId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$requestId || $requestId < 1) {
    header("Location: requests.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Get request
|--------------------------------------------------------------------------
| IMPORTANT:
| The request must belong to the logged-in user.
*/

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
        s.name AS service_name,
        u.full_name,
        u.email,
        u.phone
     FROM service_requests sr
     INNER JOIN services s
        ON sr.service_id = s.id
     INNER JOIN users u
        ON sr.user_id = u.id
     WHERE sr.id = :request_id
       AND sr.user_id = :user_id
     LIMIT 1"
);

$stmt->execute([
    ":request_id" => $requestId,
    ":user_id" => $userId
]);

$request = $stmt->fetch();

if (!$request) {
    header("Location: requests.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Request history
|--------------------------------------------------------------------------
*/

$historyStmt = $pdo->prepare(
    "SELECT
        aa.action,
        aa.created_at,
        u.full_name AS admin_name
     FROM admin_actions aa
     LEFT JOIN users u
        ON aa.admin_id = u.id
     WHERE aa.request_id = :request_id
     ORDER BY aa.created_at DESC"
);

$historyStmt->execute([
    ":request_id" => $requestId
]);

$history = $historyStmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Attachments
|--------------------------------------------------------------------------
*/

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

function statusClass(string $status): string
{
    return strtolower(
        str_replace(" ", "-", $status)
    );
}

function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? "",
        ENT_QUOTES,
        "UTF-8"
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
        Request #<?= (int) $request["id"] ?> |
        KHAN SOLUTIONS
    </title>

    <style>
        .request-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            flex-wrap: wrap;
        }

        .request-id {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .request-title {
            font-size: 23px;
            margin-bottom: 8px;
        }

        .service {
            color: #60a5fa;
            font-size: 14px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .description {
            white-space: pre-wrap;
        }

        @media (max-width: 700px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .field.full {
                grid-column: auto;
            }

            .request-title {
                font-size: 20px;
            }
        }
    </style>

</head>

<body>

<div class="container">

    <div class="topbar">

        <div>
            <h1>Request Details</h1>

            <p>
                Request #<?= (int) $request["id"] ?>
            </p>
        </div>

        <div class="actions">

            <a
                href="requests.php"
                class="btn"
            >
                ← My Requests
            </a>

            <a
                href="request.php"
                class="btn primary"
            >
                + New Request
            </a>

        </div>

    </div>


    <!-- REQUEST HEADER -->

    <div class="card">

        <div class="request-heading">

            <div>

                <div class="request-id">
                    Request #<?= (int) $request["id"] ?>
                </div>

                <h2 class="request-title">
                    <?= e($request["title"]) ?>
                </h2>

                <div class="service">
                    <?= e($request["service_name"]) ?>
                </div>

            </div>

            <span
                class="status <?= e(
                    statusClass($request["status"])
                ) ?>"
            >
                <?= e($request["status"]) ?>
            </span>

        </div>

    </div>


    <!-- REQUEST INFORMATION -->

    <div class="card">

        <h2 class="section-title">
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
                    Urgency
                </span>

                <div class="value">
                    <?= e(ucfirst($request["urgency"])) ?>
                </div>

            </div>


            <div class="field">

                <span class="label">
                    Location
                </span>

                <div class="value">
                    <?= e(
                        $request["location"] ?: "Not specified"
                    ) ?>
                </div>

            </div>


            <div class="field">

                <span class="label">
                    Submitted
                </span>

                <div class="value">
                    <?= e($request["created_at"]) ?>
                </div>

            </div>


            <div class="field">

                <span class="label">
                    Last Updated
                </span>

                <div class="value">
                    <?= e($request["updated_at"]) ?>
                </div>

            </div>


            <div class="field full">

                <span class="label">
                    Description
                </span>

                <div class="value description">
                    <?= e($request["description"]) ?>
                </div>

            </div>

        </div>

    </div>


    <!-- CLIENT INFORMATION -->

    <div class="card">

        <h2 class="section-title">
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

        </div>

    </div>


    <!-- STATUS HISTORY -->

    <div class="card">

        <h2 class="section-title">
            Request History
        </h2>

        <?php if ($history): ?>

            <div class="history">

                <?php foreach ($history as $item): ?>

                    <div class="history-item">

                        <div class="history-action">
                            <?= e($item["action"]) ?>
                        </div>

                        <div class="history-meta">

                            <?= e(
                                $item["admin_name"]
                                ?: "System"
                            ) ?>

                            ·

                            <?= e($item["created_at"]) ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <p class="no-data">
                No status history is available yet.
            </p>

        <?php endif; ?>

    </div>


    <!-- ATTACHMENTS -->

    <div class="card">

        <h2 class="section-title">
            Attachments
        </h2>

        <?php if ($attachments): ?>

            <div class="attachment-list">

                <?php foreach ($attachments as $attachment): ?>

                    <div class="attachment">

                        <div>

                            <div class="attachment-name">
                                <?= e(
                                    $attachment["file_name"]
                                ) ?>
                            </div>

                            <div class="attachment-meta">

                                <?= e(
                                    $attachment["file_type"]
                                ) ?>

                                ·

                                <?= e(
                                    $attachment["created_at"]
                                ) ?>
                              <a href="request-attachment.php?id=<?= (int) $attachment["id"] ?>"
                                    class="view-btn" >
                                Download
                             </a>
                            </div>

                        </div>

                        <span class="attachment-meta">
                            Private attachment
                        </span>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <p class="no-data">
                No attachments were submitted with this request.
            </p>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
