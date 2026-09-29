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

$admin = null;

$system = [
    "users" => 0,
    "services" => 0,
    "active_services" => 0,
    "requests" => 0
];

$error = "";
$success = "";

$profileName = "";
$profileEmail = "";


/*
 * HANDLE SETTINGS ACTIONS
 */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $csrf = $_POST["csrf_token"] ?? "";

    if (!hash_equals(csrfToken(), $csrf)) {

        $error = "Invalid security token. Please try again.";

    } else {

        $action = $_POST["action"] ?? "";

        /*
         * UPDATE PROFILE
         */

        if ($action === "update_profile") {

            $profileName = trim(
                $_POST["full_name"] ?? ""
            );

            $profileEmail = trim(
                $_POST["email"] ?? ""
            );

            if ($profileName === "") {

                $error = "Full name is required.";

            } elseif (
                $profileEmail === "" ||
                !filter_var($profileEmail, FILTER_VALIDATE_EMAIL)
            ) {

                $error = "Please enter a valid email address.";

            } else {

                try {

                    $stmt = $pdo->prepare(
                        "SELECT id
                         FROM users
                         WHERE email = ?
                           AND id != ?
                         LIMIT 1"
                    );

                    $stmt->execute([
                        $profileEmail,
                        (int) $_SESSION["admin_id"]
                    ]);

                    if ($stmt->fetch()) {

                        $error =
                            "That email address is already in use.";

                    } else {

                        $stmt = $pdo->prepare(
                            "UPDATE users
                             SET full_name = ?,
                                 email = ?,
                                 updated_at = CURRENT_TIMESTAMP
                             WHERE id = ?
                               AND role = 'admin'"
                        );

                        $stmt->execute([
                            $profileName,
                            $profileEmail,
                            (int) $_SESSION["admin_id"]
                        ]);

                        $_SESSION["admin_name"] =
                            $profileName;

                        $_SESSION["admin_email"] =
                            $profileEmail;

                        $success =
                            "Administrator profile updated successfully.";
                    }

                } catch (Throwable $e) {

                    error_log(
                        "Admin Profile Update Error: " .
                        $e->getMessage()
                    );

                    $error =
                        "Unable to update administrator profile.";
                }
            }
        }


        /*
         * CHANGE PASSWORD
         */

        elseif ($action === "change_password") {

            $currentPassword =
                $_POST["current_password"] ?? "";

            $newPassword =
                $_POST["new_password"] ?? "";

            $confirmPassword =
                $_POST["confirm_password"] ?? "";

            if ($currentPassword === "") {

                $error =
                    "Current password is required.";

            } elseif ($newPassword === "") {

                $error =
                    "New password is required.";

            } elseif (strlen($newPassword) < 8) {

                $error =
                    "New password must be at least 8 characters.";

            } elseif ($newPassword !== $confirmPassword) {

                $error =
                    "New password and confirmation do not match.";

            } else {

                try {

                    $stmt = $pdo->prepare(
                        "SELECT password
                         FROM users
                         WHERE id = ?
                           AND role = 'admin'
                         LIMIT 1"
                    );

                    $stmt->execute([
                        (int) $_SESSION["admin_id"]
                    ]);

                    $adminPassword =
                        $stmt->fetch();

                    if (
                        !$adminPassword ||
                        !password_verify(
                            $currentPassword,
                            $adminPassword["password"]
                        )
                    ) {

                        $error =
                            "Current password is incorrect.";

                    } else {

                        $passwordHash =
                            password_hash(
                                $newPassword,
                                PASSWORD_DEFAULT
                            );

                        $stmt = $pdo->prepare(
                            "UPDATE users
                             SET password = ?,
                                 updated_at = CURRENT_TIMESTAMP
                             WHERE id = ?
                               AND role = 'admin'"
                        );

                        $stmt->execute([
                            $passwordHash,
                            (int) $_SESSION["admin_id"]
                        ]);

                        $success =
                            "Password changed successfully.";
                    }

                } catch (Throwable $e) {

                    error_log(
                        "Admin Password Update Error: " .
                        $e->getMessage()
                    );

                    $error =
                        "Unable to change password.";
                }
            }
        }
    }
}


/*
 * LOAD ADMIN + SYSTEM INFORMATION
 */

try {

    $stmt = $pdo->prepare(
        "SELECT
            id,
            full_name,
            email,
            role,
            created_at,
            updated_at
         FROM users
         WHERE id = ?
           AND role = 'admin'
         LIMIT 1"
    );

    $stmt->execute([
        (int) $_SESSION["admin_id"]
    ]);

    $admin = $stmt->fetch();

    if (!$admin) {

        session_unset();
        session_destroy();

        header("Location: login.php");
        exit;
    }


    /*
     * CLIENTS
     */

    $stmt = $pdo->query(
        "SELECT COUNT(*) AS total
         FROM users
         WHERE role = 'user'"
    );

    $system["users"] =
        (int) $stmt->fetch()["total"];


    /*
     * SERVICES
     */

    $stmt = $pdo->query(
        "SELECT
            COUNT(*) AS total,
            COALESCE(
                SUM(is_active = 1),
                0
            ) AS active
         FROM services"
    );

    $serviceStats =
        $stmt->fetch();

    $system["services"] =
        (int) ($serviceStats["total"] ?? 0);

    $system["active_services"] =
        (int) ($serviceStats["active"] ?? 0);


    /*
     * REQUESTS
     */

    $stmt = $pdo->query(
        "SELECT COUNT(*) AS total
         FROM service_requests"
    );

    $system["requests"] =
        (int) $stmt->fetch()["total"];


} catch (Throwable $e) {

    error_log(
        "Admin Settings Error: " .
        $e->getMessage()
    );

    if ($admin === null) {

        $error =
            "Unable to load system information.";
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
        Settings — KHAN SOLUTIONS Admin
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
            width: min(1000px, 94%);
            margin: 35px auto;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h2 {
            margin: 0 0 7px;
        }

        .page-header p {
            margin: 0;
            color: #6b7280;
        }


        /*
         * ALERTS
         */

        .alert,
        .success {
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .success {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #166534;
        }


        /*
         * SECTION
         */

        .section {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow:
                0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .section h3 {
            margin-top: 0;
            margin-bottom: 20px;
        }


        /*
         * PROFILE
         */

        .profile-grid {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .field {
            padding: 14px;
            background: #f9fafb;
            border-radius: 8px;
        }

        .label {
            display: block;
            color: #6b7280;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .value {
            word-break: break-word;
        }

        .role {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            background: #dbeafe;
            color: #1e40af;
            font-size: 12px;
            font-weight: 700;
        }


        /*
         * FORMS
         */

        .form-grid {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .form-group {
            margin-bottom: 4px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 700;
            color: #374151;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            background: #ffffff;
            color: #111827;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow:
                0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-note {
            margin: 0 0 18px;
            color: #6b7280;
            font-size: 13px;
            line-height: 1.6;
        }

        .form-actions {
            margin-top: 20px;
        }

        .btn {
            border: 0;
            border-radius: 8px;
            padding: 11px 18px;
            background: #111827;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn:hover {
            opacity: 0.9;
        }


        /*
         * SYSTEM
         */

        .system-grid {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .system-card {
            padding: 20px;
            background: #f9fafb;
            border: 1px solid #eef0f2;
            border-radius: 10px;
        }

        .system-card strong {
            display: block;
            font-size: 28px;
            margin-bottom: 6px;
        }

        .system-card span {
            color: #6b7280;
            font-size: 13px;
        }


        /*
         * SECURITY
         */

        .security-note {
            line-height: 1.7;
            color: #4b5563;
        }

        .security-note p {
            margin-top: 0;
        }


        /*
         * MOBILE
         */

        @media (max-width: 800px) {

            .system-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 700px) {

            .profile-grid,
            .form-grid,
            .system-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            nav a {
                display: inline-block;
                margin-bottom: 5px;
            }

            .container {
                width: 92%;
                margin: 25px auto;
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

        <h2>
            Settings
        </h2>

        <p>
            Manage your administrator account and system information.
        </p>

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

        <div class="success">

            <?= htmlspecialchars(
                $success,
                ENT_QUOTES,
                "UTF-8"
            ) ?>

        </div>

    <?php endif; ?>


    <?php if ($admin): ?>


        <!-- ADMINISTRATOR ACCOUNT -->

        <section class="section">

            <h3>
                Administrator Account
            </h3>

            <div class="profile-grid">

                <div class="field">

                    <span class="label">
                        Administrator ID
                    </span>

                    <span class="value">
                        #<?= (int) $admin["id"] ?>
                    </span>

                </div>


                <div class="field">

                    <span class="label">
                        Role
                    </span>

                    <span class="value">

                        <span class="role">

                            <?= htmlspecialchars(
                                ucfirst($admin["role"]),
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </span>

                    </span>

                </div>


                <div class="field">

                    <span class="label">
                        Full Name
                    </span>

                    <span class="value">

                        <?= htmlspecialchars(
                            $admin["full_name"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </span>

                </div>


                <div class="field">

                    <span class="label">
                        Email
                    </span>

                    <span class="value">

                        <?= htmlspecialchars(
                            $admin["email"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </span>

                </div>


                <div class="field">

                    <span class="label">
                        Account Created
                    </span>

                    <span class="value">

                        <?= htmlspecialchars(
                            $admin["created_at"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </span>

                </div>


                <div class="field">

                    <span class="label">
                        Last Updated
                    </span>

                    <span class="value">

                        <?= htmlspecialchars(
                            $admin["updated_at"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </span>

                </div>

            </div>

        </section>


        <!-- EDIT PROFILE -->

        <section class="section">

            <h3>
                Edit Administrator Profile
            </h3>

            <p class="form-note">
                Update the administrator name or email address
                used by this account.
            </p>

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

                <input
                    type="hidden"
                    name="action"
                    value="update_profile"
                >


                <div class="form-grid">

                    <div class="form-group">

                        <label for="full_name">
                            Full Name
                        </label>

                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            maxlength="150"
                            value="<?= htmlspecialchars(
                                $profileName !== ""
                                    ? $profileName
                                    : $admin["full_name"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="email">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            maxlength="190"
                            value="<?= htmlspecialchars(
                                $profileEmail !== ""
                                    ? $profileEmail
                                    : $admin["email"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                            required
                        >

                    </div>

                </div>


                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn"
                    >
                        Save Profile
                    </button>

                </div>

            </form>

        </section>


        <!-- CHANGE PASSWORD -->

        <section class="section">

            <h3>
                Change Password
            </h3>

            <p class="form-note">
                Use a strong password with at least 8 characters.
                Your current password is required before a new
                password can be saved.
            </p>

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

                <input
                    type="hidden"
                    name="action"
                    value="change_password"
                >


                <div class="form-grid">

                    <div class="form-group full">

                        <label for="current_password">
                            Current Password
                        </label>

                        <input
                            type="password"
                            id="current_password"
                            name="current_password"
                            autocomplete="current-password"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="new_password">
                            New Password
                        </label>

                        <input
                            type="password"
                            id="new_password"
                            name="new_password"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="confirm_password">
                            Confirm New Password
                        </label>

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >

                    </div>

                </div>


                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn"
                    >
                        Change Password
                    </button>

                </div>

            </form>

        </section>


        <!-- SYSTEM OVERVIEW -->

        <section class="section">

            <h3>
                System Overview
            </h3>

            <div class="system-grid">

                <div class="system-card">

                    <strong>
                        <?= $system["users"] ?>
                    </strong>

                    <span>
                        Client Accounts
                    </span>

                </div>


                <div class="system-card">

                    <strong>
                        <?= $system["services"] ?>
                    </strong>

                    <span>
                        Total Services
                    </span>

                </div>


                <div class="system-card">

                    <strong>
                        <?= $system["active_services"] ?>
                    </strong>

                    <span>
                        Active Services
                    </span>

                </div>


                <div class="system-card">

                    <strong>
                        <?= $system["requests"] ?>
                    </strong>

                    <span>
                        Service Requests
                    </span>

                </div>

            </div>

        </section>


        <!-- SECURITY -->

        <section class="section">

            <h3>
                Security
            </h3>

            <div class="security-note">

                <p>
                    Administrator access is protected by
                    session-based authentication and
                    CSRF protection.
                </p>

                <p>
                    Password credentials are stored using
                    secure password hashes and are never
                    displayed in the administration interface.
                </p>

                <p>
                    Always use the Logout option when you
                    finish an administration session.
                </p>

            </div>

        </section>


    <?php endif; ?>


</div>


<footer>

    KHAN SOLUTIONS Administration System

</footer>


</body>

</html>