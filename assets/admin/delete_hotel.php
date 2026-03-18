<?php
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: " . BASE_URL . "/user/login.php");
    exit;
}

$hotelId = isset($_GET["hotel_id"]) ? (int)$_GET["hotel_id"] : 0;
if ($hotelId > 0) {
    $sql = "DELETE FROM hotels WHERE hotel_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $hotelId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

header("Location: " . BASE_URL . "/admin/dashboard.php");
exit;
?>
