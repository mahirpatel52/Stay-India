<?php
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "/user/login.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];
$userCheckSql = "SELECT user_id FROM users WHERE user_id = ?";
$userCheckStmt = mysqli_prepare($conn, $userCheckSql);
if (!$userCheckStmt) {
    die("Unable to validate session user.");
}
mysqli_stmt_bind_param($userCheckStmt, "i", $userId);
mysqli_stmt_execute($userCheckStmt);
$userCheckRes = mysqli_stmt_get_result($userCheckStmt);
$validUser = mysqli_fetch_assoc($userCheckRes);
mysqli_stmt_close($userCheckStmt);

if (!$validUser) {
    session_unset();
    session_destroy();
    header("Location: " . BASE_URL . "/user/login.php");
    exit;
}

$hotelId = isset($_GET["hotel_id"]) ? (int)$_GET["hotel_id"] : 0;
$hotel = null;
$message = "";
$type = "danger";

if ($hotelId > 0) {
    $hotelSql = "SELECT hotel_id, name, city, price, image, hotel_images, address, rating, room_type, amenities, description, hotel_rules, check_in_time, check_out_time
                 FROM hotels WHERE hotel_id = ?";
    $hotelStmt = mysqli_prepare($conn, $hotelSql);
    if ($hotelStmt) {
        mysqli_stmt_bind_param($hotelStmt, "i", $hotelId);
        mysqli_stmt_execute($hotelStmt);
        $hotelRes = mysqli_stmt_get_result($hotelStmt);
        $hotel = mysqli_fetch_assoc($hotelRes);
        mysqli_stmt_close($hotelStmt);
    }
}

if (!$hotel) {
    header("Location: " . BASE_URL . "/user/dashboard.php");
    exit;
}

$isBlockedImageUrl = static function (string $url): bool {
    $host = strtolower((string)parse_url($url, PHP_URL_HOST));
    return $host === "source.unsplash.com";
};

$galleryImages = [];
if (!empty($hotel["image"])) {
    $mainImg = trim($hotel["image"]);
    if (filter_var($mainImg, FILTER_VALIDATE_URL) && !$isBlockedImageUrl($mainImg)) {
        $galleryImages[] = $mainImg;
    }
}
if (!empty($hotel["hotel_images"])) {
    // Accept URLs separated by new lines, commas, or semicolons.
    // Split commas/semicolons only when the next token starts with http(s),
    // so commas inside a URL query are preserved.
    $rawGallery = str_replace(["\r\n", "\r"], "\n", (string)$hotel["hotel_images"]);
    $rawGallery = preg_replace("/\s*,\s*(?=https?:\/\/)/i", "\n", $rawGallery);
    $rawGallery = preg_replace("/\s*;\s*(?=https?:\/\/)/i", "\n", $rawGallery);
    $extraImages = preg_split("/\n+/", $rawGallery);
    foreach ($extraImages as $img) {
        $img = trim($img);
        $img = rtrim($img, ",;");
        if ($img !== "" && filter_var($img, FILTER_VALIDATE_URL) && !$isBlockedImageUrl($img)) {
            $galleryImages[] = $img;
        }
    }
}
$galleryImages = array_values(array_unique($galleryImages));

$hotelKey = strtolower((string)($hotel["name"] ?? ""));
$fallbackByHotel = [
    "oberoi udaivilas" => [
        "https://images.pexels.com/photos/261102/pexels-photo-261102.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/271618/pexels-photo-271618.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/1134176/pexels-photo-1134176.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/189296/pexels-photo-189296.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/338504/pexels-photo-338504.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/271643/pexels-photo-271643.jpeg?auto=compress&cs=tinysrgb&w=1200"
    ],
    "taj lake palace" => [
        "https://images.pexels.com/photos/1838554/pexels-photo-1838554.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/164595/pexels-photo-164595.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/1457842/pexels-photo-1457842.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/271624/pexels-photo-271624.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/271639/pexels-photo-271639.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/1329711/pexels-photo-1329711.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/210604/pexels-photo-210604.jpeg?auto=compress&cs=tinysrgb&w=1200"
    ],
    "leela palace" => [
        "https://images.pexels.com/photos/271619/pexels-photo-271619.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/271624/pexels-photo-271624.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/271643/pexels-photo-271643.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/237371/pexels-photo-237371.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/164595/pexels-photo-164595.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/271639/pexels-photo-271639.jpeg?auto=compress&cs=tinysrgb&w=1200"
    ],
    "falaknuma" => [
        "https://images.pexels.com/photos/1838640/pexels-photo-1838640.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/262047/pexels-photo-262047.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/754268/pexels-photo-754268.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/338504/pexels-photo-338504.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/271643/pexels-photo-271643.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/261102/pexels-photo-261102.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/1838554/pexels-photo-1838554.jpeg?auto=compress&cs=tinysrgb&w=1200"
    ],
    "amarvilas" => [
        "https://images.pexels.com/photos/1838554/pexels-photo-1838554.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/271618/pexels-photo-271618.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/1457842/pexels-photo-1457842.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/271619/pexels-photo-271619.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/189296/pexels-photo-189296.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/1329711/pexels-photo-1329711.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/271624/pexels-photo-271624.jpeg?auto=compress&cs=tinysrgb&w=1200"
    ],
    "itc grand chola" => [
        "https://images.pexels.com/photos/271639/pexels-photo-271639.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/237371/pexels-photo-237371.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/262047/pexels-photo-262047.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/271624/pexels-photo-271624.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/754268/pexels-photo-754268.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/261395/pexels-photo-261395.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/164595/pexels-photo-164595.jpeg?auto=compress&cs=tinysrgb&w=1200"
    ],
    "rambagh" => [
        "https://images.pexels.com/photos/1838640/pexels-photo-1838640.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/271618/pexels-photo-271618.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/338504/pexels-photo-338504.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/189296/pexels-photo-189296.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/261102/pexels-photo-261102.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/271639/pexels-photo-271639.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/1457842/pexels-photo-1457842.jpeg?auto=compress&cs=tinysrgb&w=1200"
    ],
    "st. regis" => [
        "https://images.pexels.com/photos/1134176/pexels-photo-1134176.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/271624/pexels-photo-271624.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/261395/pexels-photo-261395.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/262047/pexels-photo-262047.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/271619/pexels-photo-271619.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/237371/pexels-photo-237371.jpeg?auto=compress&cs=tinysrgb&w=1200"
    ],
    "tamara coorg" => [
        "https://images.pexels.com/photos/1450353/pexels-photo-1450353.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/271618/pexels-photo-271618.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/271643/pexels-photo-271643.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/1329711/pexels-photo-1329711.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/271639/pexels-photo-271639.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/261395/pexels-photo-261395.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/1838554/pexels-photo-1838554.jpeg?auto=compress&cs=tinysrgb&w=1200"
    ],
    "kumarakom" => [
        "https://images.pexels.com/photos/1268855/pexels-photo-1268855.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/271624/pexels-photo-271624.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/271618/pexels-photo-271618.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/1134176/pexels-photo-1134176.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/237371/pexels-photo-237371.jpeg?auto=compress&cs=tinysrgb&w=1200",
        "https://images.pexels.com/photos/1457842/pexels-photo-1457842.jpeg?auto=compress&cs=tinysrgb&w=1200"
    ]
];
$defaultFallback = [
    "https://images.pexels.com/photos/261102/pexels-photo-261102.jpeg?auto=compress&cs=tinysrgb&w=1200",
    "https://images.pexels.com/photos/271618/pexels-photo-271618.jpeg?auto=compress&cs=tinysrgb&w=1200",
    "https://images.pexels.com/photos/271624/pexels-photo-271624.jpeg?auto=compress&cs=tinysrgb&w=1200",
    "https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg?auto=compress&cs=tinysrgb&w=1200",
    "https://images.pexels.com/photos/271639/pexels-photo-271639.jpeg?auto=compress&cs=tinysrgb&w=1200",
    "https://images.pexels.com/photos/237371/pexels-photo-237371.jpeg?auto=compress&cs=tinysrgb&w=1200",
    "https://images.pexels.com/photos/338504/pexels-photo-338504.jpeg?auto=compress&cs=tinysrgb&w=1200"
];
$fallbackGallery = $defaultFallback;
foreach ($fallbackByHotel as $namePart => $images) {
    if ($namePart !== "" && strpos($hotelKey, $namePart) !== false) {
        $fallbackGallery = $images;
        break;
    }
}

foreach ($fallbackGallery as $fallback) {
    if (count($galleryImages) >= 7) {
        break;
    }
    if (!in_array($fallback, $galleryImages, true)) {
        $galleryImages[] = $fallback;
    }
}

$galleryImages = array_slice($galleryImages, 0, 7);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $checkInDate = trim($_POST["check_in_date"] ?? "");
    $checkOutDate = trim($_POST["check_out_date"] ?? "");
    $today = date("Y-m-d");

    if ($checkInDate === "" || $checkOutDate === "") {
        $message = "Please select both check-in and check-out dates.";
    } elseif ($checkInDate < $today) {
        $message = "Check-in date cannot be in the past.";
    } elseif ($checkOutDate <= $checkInDate) {
        $message = "Check-out date must be after check-in date.";
    } else {
        $insertSql = "INSERT INTO bookings (user_id, hotel_id, date, check_in_date, check_out_date) VALUES (?, ?, ?, ?, ?)";
        $insertStmt = mysqli_prepare($conn, $insertSql);
        if ($insertStmt) {
            mysqli_stmt_bind_param($insertStmt, "iisss", $userId, $hotelId, $checkInDate, $checkInDate, $checkOutDate);
            try {
                if (mysqli_stmt_execute($insertStmt)) {
                    $bookingId = (int)mysqli_insert_id($conn);
                    header("Location: " . BASE_URL . "/user/payment.php?booking_id=" . $bookingId);
                    exit;
                } else {
                    $message = "Could not save booking. Please try again.";
                }
            } catch (mysqli_sql_exception $e) {
                $message = "Booking failed due to account/session mismatch. Please login again.";
            }
            mysqli_stmt_close($insertStmt);
        } else {
            $message = "Server error. Please try again.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Hotel | Stay India</title>
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
            <a class="btn btn-outline-light btn-sm" href="<?= BASE_URL; ?>/user/dashboard.php">Back</a>
        </div>
    </nav>

    <main class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="glass-panel p-4 p-md-5">
                    <h3 class="section-title">Confirm Your Booking</h3>
                    <?php if ($message !== ""): ?>
                        <div class="alert alert-<?= $type; ?> auto-dismiss"><?= htmlspecialchars($message); ?></div>
                    <?php endif; ?>

                    <div id="hotelGalleryCarousel" class="carousel slide booking-carousel mb-4" data-bs-ride="carousel">
                        <div class="carousel-indicators">
                            <?php foreach ($galleryImages as $index => $img): ?>
                                <button type="button" data-bs-target="#hotelGalleryCarousel" data-bs-slide-to="<?= $index; ?>" class="<?= $index === 0 ? "active" : ""; ?>" aria-current="<?= $index === 0 ? "true" : "false"; ?>" aria-label="Slide <?= $index + 1; ?>"></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="carousel-inner">
                            <?php foreach ($galleryImages as $index => $img): ?>
                                <div class="carousel-item <?= $index === 0 ? "active" : ""; ?>">
                                    <img src="<?= htmlspecialchars($img); ?>" class="d-block w-100 booking-carousel-image" alt="Hotel photo <?= $index + 1; ?>" onerror="this.onerror=null;this.src='https://picsum.photos/seed/stayindia-fallback-<?= (int)$hotelId; ?>-<?= $index + 1; ?>/1200/800';">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($galleryImages) > 1): ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#hotelGalleryCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#hotelGalleryCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="row g-4">
                        <div class="col-12">
                            <h4><?= htmlspecialchars($hotel["name"]); ?></h4>
                            <p class="meta-text mb-1">City: <?= htmlspecialchars($hotel["city"]); ?></p>
                            <p class="price-tag mb-3">Rs. <?= number_format((float)$hotel["price"], 2); ?> / night</p>

                            <div class="booking-info-grid mb-3">
                                <div class="booking-info-item">
                                    <strong>Rating</strong>
                                    <span><?= htmlspecialchars((string)($hotel["rating"] ?: "Not available")); ?><?= !empty($hotel["rating"]) ? " / 5" : ""; ?></span>
                                </div>
                                <div class="booking-info-item">
                                    <strong>Room Type</strong>
                                    <span><?= htmlspecialchars($hotel["room_type"] ?: "Not available"); ?></span>
                                </div>
                                <div class="booking-info-item">
                                    <strong>Address</strong>
                                    <span><?= htmlspecialchars($hotel["address"] ?: "Not available"); ?></span>
                                </div>
                                <div class="booking-info-item">
                                    <strong>Timings</strong>
                                    <span>Check-in <?= htmlspecialchars($hotel["check_in_time"] ?: "NA"); ?> | Check-out <?= htmlspecialchars($hotel["check_out_time"] ?: "NA"); ?></span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <strong>Description</strong>
                                <p class="meta-text mb-0 mt-1"><?= nl2br(htmlspecialchars($hotel["description"] ?: "Not available")); ?></p>
                            </div>

                            <div class="mb-3">
                                <strong>Hotel Rules</strong>
                                <div class="mt-2">
                                    <?php
                                        $rulesRaw = trim((string)($hotel["hotel_rules"] ?? ""));
                                        $rulesArr = [];
                                        if ($rulesRaw !== "") {
                                            $rulesArr = preg_split("/\r\n|\n|\r|(?<=\.)\s+(?=[A-Z0-9])/", $rulesRaw);
                                            $rulesArr = array_values(array_filter(array_map("trim", $rulesArr)));
                                        }
                                    ?>
                                    <?php if (count($rulesArr) > 0): ?>
                                        <ul class="meta-text ps-3 mb-0">
                                            <?php foreach ($rulesArr as $rule): ?>
                                                <li><?= htmlspecialchars($rule); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="meta-text mb-0">Rules will be shared at check-in.</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <strong>Amenities</strong>
                                <div class="amenities-chip-wrap mt-2">
                                    <?php
                                        $amenitiesArr = [];
                                        if (!empty($hotel["amenities"])) {
                                            $amenitiesArr = array_filter(array_map("trim", explode(",", $hotel["amenities"])));
                                        }
                                        if (count($amenitiesArr) === 0) {
                                            $amenitiesArr = ["Not available"];
                                        }
                                    ?>
                                    <?php foreach ($amenitiesArr as $amenity): ?>
                                        <span class="amenity-chip"><?= htmlspecialchars($amenity); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Check-in Date</label>
                                    <input type="date" name="check_in_date" class="form-control" min="<?= date("Y-m-d"); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Check-out Date</label>
                                    <input type="date" name="check_out_date" class="form-control" min="<?= date("Y-m-d", strtotime("+1 day")); ?>" required>
                                </div>
                                <button type="submit" class="btn btn-gradient px-4">Confirm Booking</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="<?= BASE_URL; ?>/assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
