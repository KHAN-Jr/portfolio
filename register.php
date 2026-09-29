<?php

require_once __DIR__ . "/config/security.php";
require_once __DIR__ . "/config/database.php";

if (isset($_SESSION["user_id"])) {
    header("Location: user/dashboard.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verifyCsrfToken($_POST["csrf_token"] ?? null)) {

        $error = "Invalid security token.";

    } else {

        $fullName = trim($_POST["full_name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $password = $_POST["password"] ?? "";
        $confirmPassword = $_POST["confirm_password"] ?? "";

        if (
            $fullName === "" ||
            $email === "" ||
            $phone === "" ||
            $password === "" ||
            $confirmPassword === ""
        ) {

            $error = "Please complete all required fields.";

        } elseif (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {

            $error = "Full name must be between 2 and 100 characters.";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $error = "Please enter a valid email address.";

        } elseif (mb_strlen($email) > 150) {

            $error = "Email address is too long.";

        } elseif (mb_strlen($phone) > 20) {

            $error = "Phone number is too long.";

        } elseif (strlen($password) < 8) {

            $error = "Password must be at least 8 characters.";

        } elseif ($password !== $confirmPassword) {

            $error = "Passwords do not match.";

        } else {

            try {

                $stmt = $pdo->prepare(
                    "SELECT id
                     FROM users
                     WHERE email = :email
                     LIMIT 1"
                );

                $stmt->execute([
                    ":email" => $email
                ]);

                if ($stmt->fetch()) {

                    $error = "An account with this email already exists.";

                } else {

                    $hashedPassword = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    $stmt = $pdo->prepare(
                        "INSERT INTO users
                        (
                            full_name,
                            email,
                            phone,
                            password
                        )
                        VALUES
                        (
                            :full_name,
                            :email,
                            :phone,
                            :password
                        )"
                    );

                    $stmt->execute([
                        ":full_name" => $fullName,
                        ":email" => $email,
                        ":phone" => $phone,
                        ":password" => $hashedPassword
                    ]);

                    $success = "Account created successfully. You can now login.";

                    $_POST = [];
                }

            } catch (Throwable $e) {

                error_log(
                    "User Registration Error: " .
                    $e->getMessage()
                );

                $error =
                    "Unable to create account right now.";
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

    <title>
        Create Account — KHAN SOLUTIONS
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family:
                Arial,
                Helvetica,
                sans-serif;
            background:
                linear-gradient(
                    135deg,
                    #0f172a 0%,
                    #111827 50%,
                    #1e293b 100%
                );
            color: #1f2937;
        }

        main {
            width: min(100%, 460px);
            background: #ffffff;
            border-radius: 16px;
            padding: 38px;
            box-shadow:
                0 20px 50px rgba(0, 0, 0, 0.25);
        }

        h1 {
            margin: 0;
            text-align: center;
            font-size: 25px;
            letter-spacing: 1px;
            color: #111827;
        }

        h2 {
            margin: 8px 0 30px;
            text-align: center;
            font-size: 16px;
            font-weight: 500;
            color: #6b7280;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        form > div {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        label {
            font-size: 14px;
            font-weight: 700;
            color: #374151;
        }

        input {
            width: 100%;
            height: 48px;
            padding: 0 14px;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            background: #ffffff;
            color: #111827;
            font-size: 15px;
            outline: none;
        }

        input:focus {
            border-color: #2563eb;
            box-shadow:
                0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        button {
            width: 100%;
            height: 48px;
            margin-top: 4px;
            border: 0;
            border-radius: 9px;
            background: #111827;
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }

        button:hover {
            background: #1f2937;
        }

        .message {
            margin: 0 0 20px;
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 14px;
            line-height: 1.5;
        }

        .error {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
        }

        .success {
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #166534;
        }

        .links {
            margin-top: 22px;
            text-align: center;
            font-size: 14px;
            color: #6b7280;
        }

        .links a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 700;
        }

        .links a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {

            body {
                padding: 16px;
            }

            main {
                padding: 30px 22px;
                border-radius: 13px;
            }

            h1 {
                font-size: 22px;
            }
        }

    </style>

</head>

<body>

    <main>

        <h1>KHAN SOLUTIONS</h1>

        <h2>Create Client Account</h2>

        <?php if ($error !== ""): ?>

            <p class="message error">
                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </p>

        <?php endif; ?>

        <?php if ($success !== ""): ?>

            <p class="message success">
                <?= htmlspecialchars(
                    $success,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </p>

        <?php endif; ?>

        <form method="POST" action="">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    csrfToken(),
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>">

            <div>

                <label for="full_name">
                    Full Name
                </label>

                <input
                    id="full_name"
                    name="full_name"
                    type="text"
                    maxlength="100"
                    autocomplete="name"
                    value="<?= htmlspecialchars(
                        $_POST["full_name"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    required>

            </div>

            <div>

                <label for="email">
                    Email
                </label>

                <input
                    id="email"
                    name="email"
                    type="email"
                    maxlength="150"
                    autocomplete="email"
                    value="<?= htmlspecialchars(
                        $_POST["email"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    required>

            </div>

            <div>

                <label for="phone">
                    Phone Number
                </label>

                <input
                    id="phone"
                    name="phone"
                    type="tel"
                    maxlength="20"
                    autocomplete="tel"
                    value="<?= htmlspecialchars(
                        $_POST["phone"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    required>

            </div>

            <div>

                <label for="password">
                    Password
                </label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    minlength="8"
                    autocomplete="new-password"
                    required>

            </div>

            <div>

                <label for="confirm_password">
                    Confirm Password
                </label>

                <input
                    id="confirm_password"
                    name="confirm_password"
                    type="password"
                    minlength="8"
                    autocomplete="new-password"
                    required>

            </div>

            <button type="submit">
                Create Account
            </button>

        </form>

        <div class="links">

            Already have an account?
            <a href="login.php">
                Login
            </a>

        </div>

    </main>

</body>

</html>
