<?php
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: " . BASE_URL . "/user/login.php");
    exit;
}

$hotels = [];
$sql = "SELECT hotel_id, name, city, price, image FROM hotels ORDER BY hotel_id DESC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $hotels[] = $row;
    }
}

$resolveCardImage = static function (array $hotel): string {
    $img = trim((string)($hotel["image"] ?? ""));
    $host = strtolower((string)parse_url($img, PHP_URL_HOST));
    if ($img !== "" && filter_var($img, FILTER_VALIDATE_URL) && $host !== "source.unsplash.com") {
        return $img;
    }
    return "https://images.pexels.com/photos/271618/pexels-photo-271618.jpeg?auto=compress&cs=tinysrgb&w=900";
};
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Stay India</title>
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
            <div class="d-flex gap-2">
                <a class="btn btn-gradient btn-sm" href="<?= BASE_URL; ?>/admin/add_hotel.php">Add Hotel</a>
                <a class="btn btn-outline-light btn-sm" href="<?= BASE_URL; ?>/admin/bookings.php">View Bookings</a>
                <a class="btn btn-outline-light btn-sm" href="<?= BASE_URL; ?>/admin/logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <h3 class="section-title">Manage Hotels</h3>
        <div class="row g-4">
            <?php if (count($hotels) > 0): ?>
                <?php foreach ($hotels as $hotel): ?>
                    <div class="col-sm-6 col-lg-4">
                        <div class="card hotel-card h-100">
                            <img src="<?= htmlspecialchars($resolveCardImage($hotel)); ?>" class="hotel-image" alt="Hotel" onerror="this.onerror=null;this.src='https://images.pexels.com/photos/261395/pexels-photo-261395.jpeg?auto=compress&cs=tinysrgb&w=900';">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($hotel["name"]); ?></h5>
                                <p class="meta-text mb-2">City: <?= htmlspecialchars($hotel["city"]); ?></p>
                                <p class="price-tag mb-3">Rs. <?= number_format((float)$hotel["price"], 2); ?></p>
                                <div class="d-flex gap-2 mt-auto">
                                    <a href="<?= BASE_URL; ?>/admin/edit_hotel.php?hotel_id=<?= (int)$hotel["hotel_id"]; ?>" class="btn btn-gradient btn-sm flex-fill">Edit Hotel</a>
                                    <a href="<?= BASE_URL; ?>/admin/delete_hotel.php?hotel_id=<?= (int)$hotel["hotel_id"]; ?>" class="btn btn-danger btn-sm flex-fill" onclick="return confirm('Delete this hotel?')">Delete Hotel</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">No hotels found. Add your first hotel.</div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
