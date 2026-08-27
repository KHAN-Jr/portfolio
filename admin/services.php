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

$services = [];
$error = "";

try {

    $stmt = $pdo->query(
        "SELECT
            id,
            name,
            slug,
            description,
            icon,
            is_active,
            created_at
         FROM services
         ORDER BY id ASC"
    );

    $services = $stmt->fetchAll();

} catch (Throwable $e) {

    error_log(
        "Admin Services Error: " . $e->getMessage()
    );

    $error = "Unable to load services.";
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
        Services — KHAN SOLUTIONS Admin
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
            width: min(1200px, 94%);
            margin: 35px auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        .page-header h2 {
            margin: 0 0 7px;
        }

        .page-header p {
            margin: 0;
            color: #6b7280;
        }

        .count {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 700;
        }

        .alert {
            padding: 14px;
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .services {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .service-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 22px;
            box-shadow:
                0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .service-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 18px;
        }

        .service-id {
            color: #6b7280;
            font-size: 13px;
        }

        .service-name {
            margin: 5px 0 0;
            font-size: 20px;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .active {
            background: #dcfce7;
            color: #166534;
        }

        .inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .service-field {
            margin-bottom: 14px;
        }

        .label {
            display: block;
            color: #6b7280;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .value {
            word-break: break-word;
        }

        .description {
            line-height: 1.6;
            color: #4b5563;
        }

        .empty {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            color: #6b7280;
        }

        @media (max-width: 750px) {

            .services {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
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

    <div class="page-header">

        <div>

            <h2>
                Services
            </h2>

            <p>
                Services currently configured in KHAN SOLUTIONS.
            </p>

        </div>

        <div class="count">

            <?= count($services) ?>

            Service<?= count($services) === 1 ? "" : "s" ?>

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


    <?php if (empty($services)): ?>

        <div class="empty">

            <h3>
                No Services Found
            </h3>

            <p>
                There are currently no services in the database.
            </p>

        </div>

    <?php else: ?>

        <div class="services">

            <?php foreach ($services as $service): ?>

                <article class="service-card">

                    <div class="service-header">

                        <div>

                            <span class="service-id">

                                Service #
                                <?= (int) $service["id"] ?>

                            </span>

                            <h3 class="service-name">

                                <?= htmlspecialchars(
                                    $service["name"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </h3>

                        </div>


                        <?php if ((int) $service["is_active"] === 1): ?>

                            <span class="status active">
                                Active
                            </span>

                        <?php else: ?>

                            <span class="status inactive">
                                Inactive
                            </span>

                        <?php endif; ?>

                    </div>


                    <div class="service-field">

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


                    <div class="service-field">

                        <span class="label">
                            Description
                        </span>

                        <div class="value description">

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


                    <div class="service-field">

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


                    <div class="service-field">

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

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

</body>

</html>