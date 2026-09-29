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

$error = "";
$success = "";

$name = "";
$slug = "";
$description = "";
$icon = "";
$isActive = 1;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $csrfToken = $_POST["csrf_token"] ?? "";

    if (!verifyCsrfToken($csrfToken)) {

        $error = "Invalid security token.";

    } else {

        $name = trim($_POST["name"] ?? "");
        $slug = trim($_POST["slug"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $icon = trim($_POST["icon"] ?? "");
        $isActive = isset($_POST["is_active"]) ? 1 : 0;

        if ($name === "") {

            $error = "Service name is required.";

        } elseif (strlen($name) > 100) {

            $error = "Service name must not exceed 100 characters.";

        } elseif ($slug === "") {

            $error = "Service slug is required.";

        } elseif (
            !preg_match(
                '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                $slug
            )
        ) {

            $error =
                "Slug may contain lowercase letters, numbers and hyphens only.";

        } elseif (strlen($slug) > 100) {

            $error = "Service slug must not exceed 100 characters.";

        } elseif (strlen($description) > 1000) {

            $error =
                "Description must not exceed 1000 characters.";

        } elseif (strlen($icon) > 100) {

            $error = "Icon must not exceed 100 characters.";

        } else {

            try {

                $checkStmt = $pdo->prepare(
                    "SELECT id
                     FROM services
                     WHERE slug = :slug
                     LIMIT 1"
                );

                $checkStmt->execute([
                    ":slug" => $slug
                ]);

                if ($checkStmt->fetch()) {

                    $error =
                        "A service with this slug already exists.";

                } else {

                    $stmt = $pdo->prepare(
                        "INSERT INTO services
                        (
                            name,
                            slug,
                            description,
                            icon,
                            is_active
                        )
                        VALUES
                        (
                            :name,
                            :slug,
                            :description,
                            :icon,
                            :is_active
                        )"
                    );

                    $stmt->execute([
                        ":name" => $name,
                        ":slug" => $slug,
                        ":description" =>
                            $description !== ""
                                ? $description
                                : null,
                        ":icon" =>
                            $icon !== ""
                                ? $icon
                                : null,
                        ":is_active" => $isActive
                    ]);

                    $success =
                        "Service added successfully.";

                    $name = "";
                    $slug = "";
                    $description = "";
                    $icon = "";
                    $isActive = 1;
                }

            } catch (Throwable $e) {

                error_log(
                    "Admin Add Service Error: " .
                    $e->getMessage()
                );

                $error =
                    "Unable to add service.";
            }
        }
    }
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
        Add Service — KHAN SOLUTIONS Admin
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

        nav form {
            display: inline;
        }

        nav button {
            border: 0;
            background: transparent;
            color: #b91c1c;
            font-weight: 600;
            cursor: pointer;
            padding: 0;
        }

        .container {
            width: min(800px, 94%);
            margin: 35px auto;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 25px;
            box-shadow:
                0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .card h2 {
            margin-top: 0;
        }

        .field {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
        }

        input,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font: inherit;
        }

        textarea {
            min-height: 130px;
            resize: vertical;
        }

        .hint {
            display: block;
            margin-top: 5px;
            color: #6b7280;
            font-size: 12px;
        }

        .checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox input {
            width: auto;
        }

        .button {
            border: 0;
            background: #111827;
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
        }

        .alert {
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .success {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #166534;
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

    <form
        method="POST"
        action="logout.php"
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

        <button type="submit">
            Logout
        </button>

    </form>

</nav>

<div class="container">

    <a
        href="services.php"
        class="back"
    >
        ← Back to Services
    </a>

    <div class="card">

        <h2>
            Add Service
        </h2>

        <p>
            Add a new service to KHAN SOLUTIONS.
        </p>

        <?php if ($error !== ""): ?>

            <div class="alert error">

                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </div>

        <?php endif; ?>

        <?php if ($success !== ""): ?>

            <div class="alert success">

                <?= htmlspecialchars(
                    $success,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </div>

        <?php endif; ?>

        <form
            method="POST"
            action="add-service.php"
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

            <div class="field">

                <label for="name">
                    Service Name
                </label>

                <input
                    id="name"
                    name="name"
                    type="text"
                    maxlength="100"
                    value="<?= htmlspecialchars(
                        $name,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    required
                >

            </div>

            <div class="field">

                <label for="slug">
                    Slug
                </label>

                <input
                    id="slug"
                    name="slug"
                    type="text"
                    maxlength="100"
                    value="<?= htmlspecialchars(
                        $slug,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    placeholder="example-service"
                    required
                >

                <span class="hint">
                    Use lowercase letters, numbers and hyphens.
                </span>

            </div>

            <div class="field">

                <label for="description">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    maxlength="1000"
                ><?= htmlspecialchars(
                    $description,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?></textarea>

            </div>

            <div class="field">

                <label for="icon">
                    Icon
                </label>

                <input
                    id="icon"
                    name="icon"
                    type="text"
                    maxlength="100"
                    value="<?= htmlspecialchars(
                        $icon,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    placeholder="fa-bolt or ⚡"
                >

            </div>

            <div class="field checkbox">

                <input
                    id="is_active"
                    name="is_active"
                    type="checkbox"
                    value="1"
                    <?= $isActive === 1
                        ? "checked"
                        : "" ?>
                >

                <label for="is_active">
                    Active service
                </label>

            </div>

            <button
                type="submit"
                class="button"
            >
                Add Service
            </button>

        </form>

    </div>

</div>

</body>

</html>
