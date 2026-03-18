<?php
require_once __DIR__ . "/../config/db.php";

unset($_SESSION["is_admin"], $_SESSION["admin_email"]);
header("Location: " . BASE_URL . "/user/login.php");
exit;
?>
