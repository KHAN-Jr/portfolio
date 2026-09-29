<?php

require_once __DIR__ . "/../config/security.php";

unset(
    $_SESSION["user_id"],
    $_SESSION["user_name"],
    $_SESSION["user_email"],
    $_SESSION["user_role"]
);

header("Location: ../login.php");
exit;
