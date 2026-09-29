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

$userStmt = $pdo->prepare(
    "SELECT
        id,
        full_name,
        email,
        phone,
       password
     FROM users
     WHERE id = :user_id
     LIMIT 1"
);

$userStmt->execute([
    ":user_id" => $userId
]);

$user = $userStmt->fetch();

if (!$user) {
    header("Location: logout.php");
    exit;
}

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verifyCsrfToken($_POST["csrf_token"] ?? null)) {

        $error =
            "Invalid security token. Please refresh the page and try again.";

    } else {

        $action = $_POST["action"] ?? "";

        /*
        |--------------------------------------------------------------------------
        | UPDATE PROFILE
        |--------------------------------------------------------------------------
        */

        if ($action === "profile") {

            $fullName = trim(
                $_POST["full_name"] ?? ""
            );

            $phone = trim(
                $_POST["phone"] ?? ""
            );

            if ($fullName === "") {

                $error = "Full name is required.";

            } elseif (
                mb_strlen($fullName) < 2 ||
                mb_strlen($fullName) > 100
            ) {

                $error =
                    "Full name must be between 2 and 100 characters.";

            } elseif (
                $phone !== "" &&
                !preg_match(
                    '/^[0-9+\-\s()]{7,20}$/',
                    $phone
                )
            ) {

                $error = "Please enter a valid phone number.";

            } else {

                $checkPhone = $pdo->prepare(
                    "SELECT id
                     FROM users
                     WHERE phone = :phone
                       AND id != :user_id
                     LIMIT 1"
                );

                if ($phone === "") {

                    $checkPhone = null;

                } else {

                    $checkPhone->execute([
                        ":phone" => $phone,
                        ":user_id" => $userId
                    ]);

                    if ($checkPhone->fetch()) {

                        $error =
                            "This phone number is already in use.";

                    }
                }

                if ($error === "") {

                    $updateStmt = $pdo->prepare(
                        "UPDATE users
                         SET
                            full_name = :full_name,
                            phone = :phone
                         WHERE id = :user_id
                         LIMIT 1"
                    );

                    $updateStmt->execute([
                        ":full_name" => $fullName,
                        ":phone" => $phone !== ""
                            ? $phone
                            : null,
                        ":user_id" => $userId
                    ]);

                    $_SESSION["user_name"] = $fullName;

                    $user["full_name"] = $fullName;
                    $user["phone"] = $phone;

                    $success =
                        "Profile updated successfully.";
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | CHANGE PASSWORD
        |--------------------------------------------------------------------------
        */

        if ($action === "password") {

            $currentPassword =
                $_POST["current_password"] ?? "";

            $newPassword =
                $_POST["new_password"] ?? "";

            $confirmPassword =
                $_POST["confirm_password"] ?? "";

            if (
                $currentPassword === "" ||
                $newPassword === "" ||
                $confirmPassword === ""
            ) {

                $error =
                    "Please complete all password fields.";

            } elseif (
                !password_verify(
                    $currentPassword,
                    $user["password"] ?? ""
                )
            ) {

                /*
                |--------------------------------------------------------------------------
                | Password hash was not selected above.
                |--------------------------------------------------------------------------
                */

                $passwordStmt = $pdo->prepare(
                    "SELECT password
                     FROM users
                     WHERE id = :user_id
                     LIMIT 1"
                );

                $passwordStmt->execute([
                    ":user_id" => $userId
                ]);

                $passwordRow =
                    $passwordStmt->fetch();

                if (
                    !$passwordRow ||
                    !password_verify(
                        $currentPassword,
                        $passwordRow["password"]
                    )
                ) {

                    $error =
                        "Current password is incorrect.";

                }
            }

            if ($error === "") {

                if (strlen($newPassword) < 8) {

                    $error =
                        "New password must be at least 8 characters.";

                } elseif (
                    $newPassword !== $confirmPassword
                ) {

                    $error =
                        "New passwords do not match.";

                } elseif (
                    $newPassword === $currentPassword
                ) {

                    $error =
                        "New password must be different from the current password.";

                } else {

                    $newHash = password_hash(
                        $newPassword,
                        PASSWORD_DEFAULT
                    );

                    $updatePassword = $pdo->prepare(
                        "UPDATE users
                         SET password = :password
                         WHERE id = :user_id
                         LIMIT 1"
                    );

                    $updatePassword->execute([
                        ":password" => $newHash,
                        ":user_id" => $userId
                    ]);

                    session_regenerate_id(true);

                    $_SESSION["user_id"] = $userId;
                    $_SESSION["user_name"] =
                        $user["full_name"];
                    $_SESSION["user_email"] =
                        $user["email"];
                    $_SESSION["user_role"] = "user";
                    $_SESSION["csrf_token"] =
                        bin2hex(random_bytes(32));

                    $success =
                        "Password changed successfully.";
                }
            }
        }
    }
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
        My Profile | KHAN SOLUTIONS
    </title>

    <style>
        .card h2 {
            font-size: 18px;
            margin-bottom: 6px;
        }

        .card-description {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .message {
            padding: 12px 14px;
            border-radius: 9px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .success {
            background: #14532d;
            color: #bbf7d0;
            border: 1px solid #166534;
        }

        .error {
            background: #7f1d1d;
            color: #fecaca;
            border: 1px solid #991b1b;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            color: #cbd5e1;
            font-size: 13px;
            margin-bottom: 7px;
        }

        input[readonly] {
            color: #64748b;
        }

        .submit {
            margin-top: 18px;
            border: 0;
            cursor: pointer;
            background: #2563eb;
            color: #fff;
            padding: 11px 17px;
            border-radius: 8px;
            font-weight: bold;
        }

        .password-note {
            color: #64748b;
            font-size: 12px;
            margin-top: 10px;
        }

        @media (max-width: 650px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .field.full {
                grid-column: auto;
            }
        }
    </style>

</head>

<body>

<div class="container">

    <div class="topbar">

        <div>

            <h1>My Profile</h1>

            <p>
                Manage your account information and password.
            </p>

        </div>

        <div class="actions">

            <a
                href="dashboard.php"
                class="btn"
            >
                ← Dashboard
            </a>

        </div>

    </div>


    <?php if ($success !== ""): ?>

        <div class="message success">
            <?= e($success) ?>
        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="message error">
            <?= e($error) ?>
        </div>

    <?php endif; ?>


    <!-- PROFILE -->

    <section class="card">

        <h2>
            Account Information
        </h2>

        <p class="card-description">
            Update your personal contact information.
        </p>

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrfToken()) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="profile"
            >

            <div class="form-grid">

                <div class="field full">

                    <label for="full_name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        value="<?= e($user["full_name"]) ?>"
                        maxlength="100"
                        required
                    >

                </div>


                <div class="field">

                    <label for="email">
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        value="<?= e($user["email"]) ?>"
                        readonly
                    >

                </div>


                <div class="field">

                    <label for="phone">
                        Phone
                    </label>

                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        value="<?= e($user["phone"]) ?>"
                        maxlength="20"
                    >

                </div>

            </div>

            <button
                type="submit"
                class="submit"
            >
                Save Profile
            </button>

        </form>

    </section>


    <!-- PASSWORD -->

    <section class="card">

        <h2>
            Change Password
        </h2>

        <p class="card-description">
            Use a strong password that you do not reuse elsewhere.
        </p>

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrfToken()) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="password"
            >

            <div class="form-grid">

                <div class="field full">

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


                <div class="field">

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


                <div class="field">

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

            <p class="password-note">
                Minimum 8 characters.
            </p>

            <button
                type="submit"
                class="submit"
            >
                Change Password
            </button>

        </form>

    </section>

</div>

</body>
</html>
