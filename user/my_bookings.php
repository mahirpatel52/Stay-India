<?php
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "/user/login.php");
    exit;
}

$bookings = [];
$sql = "SELECT b.booking_id, b.date, b.check_in_date, b.check_out_date, h.name AS hotel_name, h.city, h.price, h.image,
               p.payment_status, p.payment_method, p.transaction_id
        FROM bookings b
        INNER JOIN hotels h ON b.hotel_id = h.hotel_id
        LEFT JOIN payments p ON b.booking_id = p.booking_id
        WHERE b.user_id = ?
        ORDER BY b.booking_id DESC";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    $userId = (int)$_SESSION["user_id"];
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $bookings[] = $row;
    }
    mysqli_stmt_close($stmt);
}

$arrivalMessage = isset($_GET["arrival"]) ? "Pay on Arrival selected successfully." : "";
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings | Stay India</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-premium">
        <div class="container">
            <a class="navbar-brand" href="<?= BASE_URL; ?>/user/dashboard.php">Stay India</a>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-light btn-sm" href="<?= BASE_URL; ?>/user/profile.php">Profile</a>
                <a class="btn btn-outline-light btn-sm" href="<?= BASE_URL; ?>/user/dashboard.php">Back to Hotels</a>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <h3 class="section-title">My Bookings</h3>
        <?php if ($arrivalMessage !== ""): ?>
            <div class="alert alert-success auto-dismiss"><?= htmlspecialchars($arrivalMessage); ?></div>
        <?php endif; ?>
        <div class="table-card p-3 p-md-4">
            <?php if (count($bookings) > 0): ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Hotel</th>
                                <th>City</th>
                                <th>Price (INR)</th>
                                <th>Stay</th>
                                <th>Payment</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $item): ?>
                                <?php
                                    $status = $item["payment_status"] ?? "Pending";
                                    $statusClass = $status === "Paid" ? "text-bg-success" : "text-bg-warning";
                                    $isPayOnArrival = ($item["payment_method"] ?? "") === "Pay on Arrival";
                                ?>
                                <tr>
                                    <td><?= (int)$item["booking_id"]; ?></td>
                                    <td><?= htmlspecialchars($item["hotel_name"]); ?></td>
                                    <td><?= htmlspecialchars($item["city"]); ?></td>
                                    <td>Rs. <?= number_format((float)$item["price"], 2); ?></td>
                                    <td>
                                        <?= htmlspecialchars($item["check_in_date"] ?: $item["date"]); ?>
                                        to
                                        <?= htmlspecialchars($item["check_out_date"] ?: ($item["date"] ?: "-")); ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $statusClass; ?>"><?= htmlspecialchars($status); ?></span>
                                        <?php if (!empty($item["transaction_id"])): ?>
                                            <div class="meta-text">Txn: <?= htmlspecialchars($item["transaction_id"]); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL; ?>/user/payment.php?booking_id=<?= (int)$item["booking_id"]; ?>" class="btn btn-sm <?= $status === "Paid" ? "btn-outline-success" : ($isPayOnArrival ? "btn-outline-secondary" : "btn-gradient"); ?>">
                                            <?= $status === "Paid" ? "View Payment" : ($isPayOnArrival ? "Arrival Payment" : "Pay Now"); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info mb-0">No bookings found yet. Reserve your first hotel from dashboard.</div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
