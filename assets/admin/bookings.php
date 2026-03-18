<?php
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: " . BASE_URL . "/user/login.php");
    exit;
}

$bookings = [];
$sql = "SELECT b.booking_id, b.date, b.check_in_date, b.check_out_date, u.name AS user_name, u.email, u.phone, u.city AS user_city, h.name AS hotel_name, h.city, h.price,
               p.payment_status, p.payment_method, p.transaction_id
        FROM bookings b
        INNER JOIN users u ON b.user_id = u.user_id
        INNER JOIN hotels h ON b.hotel_id = h.hotel_id
        LEFT JOIN payments p ON b.booking_id = p.booking_id
        ORDER BY b.booking_id DESC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $bookings[] = $row;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Bookings | Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-premium">
        <div class="container">
            <a class="navbar-brand" href="<?= BASE_URL; ?>/admin/dashboard.php">Admin Panel</a>
            <a class="btn btn-outline-light btn-sm" href="<?= BASE_URL; ?>/admin/dashboard.php">Back</a>
        </div>
    </nav>

    <main class="container py-4">
        <h3 class="section-title">All User Bookings</h3>
        <div class="table-card p-3 p-md-4">
            <?php if (count($bookings) > 0): ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>User City</th>
                                <th>Hotel</th>
                                <th>City</th>
                                <th>Price (INR)</th>
                                <th>Stay</th>
                                <th>Payment</th>
                                <th>Method</th>
                                <th>Transaction</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $item): ?>
                                <?php
                                    $status = $item["payment_status"] ?? "Pending";
                                    $statusClass = $status === "Paid" ? "text-bg-success" : "text-bg-warning";
                                ?>
                                <tr>
                                    <td><?= (int)$item["booking_id"]; ?></td>
                                    <td><?= htmlspecialchars($item["user_name"]); ?></td>
                                    <td><?= htmlspecialchars($item["email"]); ?></td>
                                    <td><?= htmlspecialchars($item["phone"] ?? "-"); ?></td>
                                    <td><?= htmlspecialchars($item["user_city"] ?? "-"); ?></td>
                                    <td><?= htmlspecialchars($item["hotel_name"]); ?></td>
                                    <td><?= htmlspecialchars($item["city"]); ?></td>
                                    <td>Rs. <?= number_format((float)$item["price"], 2); ?></td>
                                    <td>
                                        <?= htmlspecialchars($item["check_in_date"] ?: $item["date"]); ?>
                                        to
                                        <?= htmlspecialchars($item["check_out_date"] ?: ($item["date"] ?: "-")); ?>
                                    </td>
                                    <td><span class="badge <?= $statusClass; ?>"><?= htmlspecialchars($status); ?></span></td>
                                    <td><?= htmlspecialchars($item["payment_method"] ?? "-"); ?></td>
                                    <td><?= htmlspecialchars($item["transaction_id"] ?? "-"); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info mb-0">No bookings available yet.</div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
