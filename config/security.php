<?php

/*
 * KHAN SOLUTIONS
 * Central security configuration
 */

/*
 * Session security settings.
 */
ini_set("session.use_strict_mode", "1");
ini_set("session.use_only_cookies", "1");
ini_set("session.cookie_httponly", "1");

$isHttps = (
    isset($_SERVER["HTTPS"]) &&
    $_SERVER["HTTPS"] !== "" &&
    $_SERVER["HTTPS"] !== "off"
);

session_set_cookie_params([
    "lifetime" => 0,
    "path" => "/",
    "secure" => $isHttps,
    "httponly" => true,
    "samesite" => "Lax"
]);

session_start();


/*
 * Generate CSRF token.
 */
function csrfToken(): string
{
    if (
        !isset($_SESSION["csrf_token"]) ||
        !is_string($_SESSION["csrf_token"]) ||
        strlen($_SESSION["csrf_token"]) < 64
    ) {
        $_SESSION["csrf_token"] = bin2hex(
            random_bytes(32)
        );
    }

    return $_SESSION["csrf_token"];
}


/*
 * Validate CSRF token.
 */
function verifyCsrfToken(?string $token): bool
{
    if (
        !isset($_SESSION["csrf_token"]) ||
        !is_string($token)
    ) {
        return false;
    }

    return hash_equals(
        $_SESSION["csrf_token"],
        $token
    );
}