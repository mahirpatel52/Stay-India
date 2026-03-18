<?php
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: " . BASE_URL . "/user/login.php");
    exit;
}

$message = "";
$type = "danger";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $city = trim($_POST["city"] ?? "");
    $price = trim($_POST["price"] ?? "");
    $image = trim($_POST["image"] ?? "");
    $hotelImages = trim($_POST["hotel_images"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $rating = trim($_POST["rating"] ?? "");
    $roomType = trim($_POST["room_type"] ?? "");
    $amenities = trim($_POST["amenities"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $hotelRules = trim($_POST["hotel_rules"] ?? "");
    $checkIn = trim($_POST["check_in_time"] ?? "");
    $checkOut = trim($_POST["check_out_time"] ?? "");

    if ($name === "" || $city === "" || $price === "") {
        $message = "Name, city and price are required.";
    } elseif (!is_numeric($price) || (float)$price <= 0) {
        $message = "Price must be a valid number.";
    } elseif ($rating !== "" && (!is_numeric($rating) || (float)$rating < 0 || (float)$rating > 5)) {
        $message = "Rating must be between 0 and 5.";
    } else {
        $sql = "INSERT INTO hotels (name, city, price, image, hotel_images, address, rating, room_type, amenities, description, hotel_rules, check_in_time, check_out_time)
                VALUES (?, ?, ?, ?, ?, ?, NULLIF(?, ''), ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            $priceVal = (float)$price;
            mysqli_stmt_bind_param($stmt, "ssdssssssssss", $name, $city, $priceVal, $image, $hotelImages, $address, $rating, $roomType, $amenities, $description, $hotelRules, $checkIn, $checkOut);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Hotel added successfully.";
                $type = "success";
            } else {
                $message = "Unable to add hotel.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $message = "Server error. Try again.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Hotel | Admin</title>
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
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="glass-panel p-4 p-md-5">
                    <h3 class="section-title">Add New Hotel</h3>
                    <?php if ($message !== ""): ?>
                        <div class="alert alert-<?= $type; ?> auto-dismiss"><?= htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Hotel Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price Per Night (INR)</label>
                            <input type="number" step="0.01" name="price" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image URL</label>
                            <input type="text" name="image" class="form-control" placeholder="https://example.com/hotel.jpg">
                            <small class="text-muted">Use direct image links. Avoid `source.unsplash.com` links.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gallery Image URLs (6-7)</label>
                            <textarea name="hotel_images" class="form-control" rows="4" placeholder="Add one URL per line"></textarea>
                            <small class="text-muted">Paste 6-7 direct image links, one per line (or comma separated).</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" placeholder="Street, area, landmark">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rating (0 to 5)</label>
                            <input type="number" name="rating" min="0" max="5" step="0.1" class="form-control" placeholder="4.6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Room Type</label>
                            <input type="text" name="room_type" class="form-control" placeholder="Deluxe King / Suite">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amenities (comma separated)</label>
                            <input type="text" name="amenities" class="form-control" placeholder="Pool, Spa, WiFi, Breakfast">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="5" placeholder="Write a full hotel description (location, vibe, services, who it is best for)"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hotel Rules</label>
                            <textarea name="hotel_rules" class="form-control" rows="5" placeholder="Add one rule per line (ID proof, age, check-in rules, noise policy, etc.)"></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Check-in Time</label>
                                <input type="text" name="check_in_time" class="form-control" placeholder="2:00 PM">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Check-out Time</label>
                                <input type="text" name="check_out_time" class="form-control" placeholder="11:00 AM">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-gradient px-4">Add Hotel</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <script src="<?= BASE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
