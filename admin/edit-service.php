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

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: services.php");
    exit;
}

$service = null;
$error = "";
$success = "";

try {
    $stmt = $pdo->prepare(
        "SELECT
            id,
            name,
            slug,
            description,
            icon,
            is_active
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

} catch (Throwable $e) {
    error_log(
        "Edit Service Load Error: " . $e->getMessage()
    );

    $error = "Unable to load service.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verifyCsrfToken($_POST["csrf_token"] ?? "")) {
        $error = "Invalid security token. Please refresh the page and try again.";
    } else {

        $name = trim($_POST["name"] ?? "");
        $slug = trim($_POST["slug"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $icon = trim($_POST["icon"] ?? "");
        $is_active = isset($_POST["is_active"]) ? 1 : 0;

        if ($name === "") {
            $error = "Service name is required.";
        } elseif (mb_strlen($name) > 100) {
            $error = "Service name is too long.";
        } elseif ($slug === "") {
            $error = "Service slug is required.";
        } elseif (
            !preg_match(
                "/^[a-z0-9]+(?:-[a-z0-9]+)*$/",
                $slug
            )
        ) {
            $error = "Slug must contain only lowercase letters, numbers and hyphens.";
        } elseif (mb_strlen($slug) > 100) {
            $error = "Service slug is too long.";
        } elseif (mb_strlen($description) > 1000) {
            $error = "Description is too long.";
        } elseif (mb_strlen($icon) > 100) {
            $error = "Icon value is too long.";
        }

        if ($error === "") {
            try {
                $stmt = $pdo->prepare(
                    "SELECT id
                     FROM services
                     WHERE slug = :slug
                     AND id != :id
                     LIMIT 1"
                );

                $stmt->execute([
                    ":slug" => $slug,
                    ":id" => $id
                ]);

                if ($stmt->fetch()) {
                    $error = "Another service already uses this slug.";
                }
            } catch (Throwable $e) {
                error_log(
                    "Edit Service Slug Check Error: " . $e->getMessage()
                );

                $error = "Unable to validate service slug.";
            }
        }

        if ($error === "") {
            try {
                $stmt = $pdo->prepare(
                    "UPDATE services
                     SET
                        name = :name,
                        slug = :slug,
                        description = :description,
                        icon = :icon,
                        is_active = :is_active
                     WHERE id = :id"
                );

                $stmt->execute([
                    ":name" => $name,
                    ":slug" => $slug,
                    ":description" => $description !== "" ? $description : null,
                    ":icon" => $icon !== "" ? $icon : null,
                    ":is_active" => $is_active,
                    ":id" => $id
                ]);

                $success = "Service updated successfully.";

                $service["name"] = $name;
                $service["slug"] = $slug;
                $service["description"] = $description;
                $service["icon"] = $icon;
                $service["is_active"] = $is_active;

            } catch (Throwable $e) {
                error_log(
                    "Edit Service Update Error: " . $e->getMessage()
                );

                $error = "Unable to update service.";
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
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Service — KHAN SOLUTIONS Admin</title>

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
            width: min(700px, 92%);
            margin: 40px auto;
        }

        .card {
            background: #ffffff;
            padding: 25px;
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        h2 {
            margin-top: 0;
        }

        .subtitle {
            color: #6b7280;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: 700;
        }

        input,
        textarea {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 15px;
        }

        textarea {
            min-height: 130px;
            resize: vertical;
        }

        .checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox input {
            width: auto;
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        button,
        .back {
            display: inline-block;
            padding: 11px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            border: 0;
            cursor: pointer;
        }

        button {
            background: #111827;
            color: #ffffff;
        }

        .back {
            background: #e5e7eb;
            color: #111827;
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 18px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }
    </style>
</head>

<body>

<div class="container">

    <div class="card">

        <h2>Edit Service</h2>

        <p class="subtitle">
            Update service information in KHAN SOLUTIONS.
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

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    csrfToken(),
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
            >

            <div class="form-group">
                <label for="name">Service Name</label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="100"
                    required
                    value="<?= htmlspecialchars(
                        $service["name"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                >
            </div>

            <div class="form-group">
                <label for="slug">Slug</label>

                <input
                    type="text"
                    id="slug"
                    name="slug"
                    maxlength="100"
                    required
                    value="<?= htmlspecialchars(
                        $service["slug"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                >
            </div>

            <div class="form-group">
                <label for="description">Description</label>

                <textarea
                    id="description"
                    name="description"
                    maxlength="1000"
                ><?= htmlspecialchars(
                    $service["description"] ?? "",
                    ENT_QUOTES,
                    "UTF-8"
                ) ?></textarea>
            </div>

            <div class="form-group">
                <label for="icon">Icon</label>

                <input
                    type="text"
                    id="icon"
                    name="icon"
                    maxlength="100"
                    value="<?= htmlspecialchars(
                        $service["icon"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                >
            </div>

            <div class="form-group checkbox">
                <input
                    type="checkbox"
                    id="is_active"
                    name="is_active"
                    <?= !empty($service["is_active"])
                        ? "checked"
                        : "" ?>
                >

                <label for="is_active">
                    Active Service
                </label>
            </div>

            <div class="actions">

                <button type="submit">
                    Update Service
                </button>

                <a
                    href="services.php"
                    class="back"
                >
                    Back to Services
                </a>

            </div>

        </form>

    </div>

</div>

</body>
</html>
