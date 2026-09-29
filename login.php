<?php

require_once __DIR__ . "/config/security.php";

if (isset($_SESSION["user_id"])) {
    header("Location: user/dashboard.php");
    exit;
}

require_once __DIR__ . "/config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verifyCsrfToken($_POST["csrf_token"] ?? null)) {

        $error = "Invalid security token.";

    } else {

        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";

        if ($email === "" || $password === "") {

            $error = "Please enter your email and password.";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $error = "Please enter a valid email address.";

        } else {

            try {

                $stmt = $pdo->prepare(
                    "SELECT
                        id,
                        full_name,
                        email,
                        password,
                        role
                     FROM users
                     WHERE email = :email
                     AND role = 'user'
                     LIMIT 1"
                );

                $stmt->execute([
                    ":email" => $email
                ]);

                $user = $stmt->fetch();

                if (
                    $user &&
                    password_verify(
                        $password,
                        $user["password"]
                    )
                ) {

                    session_regenerate_id(true);

                    $_SESSION["user_id"] =
                        (int) $user["id"];

                    $_SESSION["user_name"] =
                        $user["full_name"];

                    $_SESSION["user_email"] =
                        $user["email"];

                    $_SESSION["user_role"] =
                        $user["role"];

                    /*
                     * Fresh CSRF token after
                     * successful authentication.
                     */
                    $_SESSION["csrf_token"] =
                        bin2hex(random_bytes(32));

                    header("Location: user/dashboard.php");
                    exit;

                } else {

                    $error =
                        "Invalid email or password.";
                }

            } catch (Throwable $e) {

                error_log(
                    "User Login Error: " .
                    $e->getMessage()
                );

                $error =
                    "Unable to process login right now.";
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
        Client Login — KHAN SOLUTIONS
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
            width: min(100%, 430px);
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
            gap: 20px;
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

        input[type="email"],
        input[type="password"] {
            width: 100%;
            height: 48px;
            padding: 0 14px;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            background: #ffffff;
            color: #111827;
            font-size: 15px;
            outline: none;
            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #2563eb;
            box-shadow:
                0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        input::placeholder {
            color: #9ca3af;
        }

        button[type="submit"] {
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
            transition:
                background 0.2s ease,
                transform 0.1s ease;
        }

        button[type="submit"]:hover {
            background: #1f2937;
        }

        button[type="submit"]:active {
            transform: translateY(1px);
        }

        button[type="submit"]:focus-visible {
            outline:
                3px solid rgba(37, 99, 235, 0.3);
            outline-offset: 2px;
        }

        .message {
            margin: 0 0 20px;
            padding: 12px 14px;
            border: 1px solid #fecaca;
            border-radius: 8px;
            background: #fef2f2;
            color: #991b1b;
            font-size: 14px;
            line-height: 1.5;
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

        <h2>Client Login</h2>

        <?php if ($error !== ""): ?>

            <p class="message">
                <?= htmlspecialchars(
                    $error,
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

                <label for="email">
                    Email
                </label>

                <input
                    id="email"
                    name="email"
                    type="email"
                    autocomplete="username"
                    value="<?= htmlspecialchars(
                        $_POST["email"] ?? "",
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
                    autocomplete="current-password"
                    required>

            </div>

            <button type="submit">
                Login
            </button>

        </form>

        <div class="links">

            Don't have an account?
            <a href="register.php">
                Create Account
            </a>

        </div>

    </main>

</body>

</html>
