<?php
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "/user/login.php");
    exit;
}

$search = trim($_GET["search"] ?? "");
$city = trim($_GET["city"] ?? "");
$maxPrice = isset($_GET["max_price"]) ? (float)$_GET["max_price"] : 0;
$sort = trim($_GET["sort"] ?? "latest");

$validSort = [
    "latest" => "hotel_id DESC",
    "price_low" => "price ASC",
    "price_high" => "price DESC",
    "name" => "name ASC"
];
$orderBy = $validSort[$sort] ?? $validSort["latest"];

$cities = [];
$cityResult = mysqli_query($conn, "SELECT DISTINCT city FROM hotels ORDER BY city ASC");
if ($cityResult) {
    while ($row = mysqli_fetch_assoc($cityResult)) {
        $cities[] = $row["city"];
    }
}

$hotels = [];
$conditions = [];
if ($search !== "") {
    $safeSearch = mysqli_real_escape_string($conn, $search);
    $conditions[] = "(name LIKE '%$safeSearch%' OR city LIKE '%$safeSearch%')";
}
if ($city !== "") {
    $safeCity = mysqli_real_escape_string($conn, $city);
    $conditions[] = "city = '$safeCity'";
}
if ($maxPrice > 0) {
    $conditions[] = "price <= " . $maxPrice;
}

$hotelSql = "SELECT hotel_id, name, city, price, image FROM hotels";
if (count($conditions) > 0) {
    $hotelSql .= " WHERE " . implode(" AND ", $conditions);
}
$hotelSql .= " ORDER BY $orderBy";
$hotelResult = mysqli_query($conn, $hotelSql);

if ($hotelResult) {
    while ($row = mysqli_fetch_assoc($hotelResult)) {
        $hotels[] = $row;
    }
}

$resolveCardImage = static function (array $hotel): string {
    $img = trim((string)($hotel["image"] ?? ""));
    $host = strtolower((string)parse_url($img, PHP_URL_HOST));
    if ($img !== "" && filter_var($img, FILTER_VALIDATE_URL) && $host !== "source.unsplash.com") {
        return $img;
    }
    return "https://images.pexels.com/photos/261102/pexels-photo-261102.jpeg?auto=compress&cs=tinysrgb&w=900";
};

$stats = [
    "matched" => count($hotels),
    "total" => 0,
    "lowest" => 0
];
$statSql = "SELECT COUNT(*) AS total, MIN(price) AS lowest FROM hotels";
$statRes = mysqli_query($conn, $statSql);
if ($statRes) {
    $statRow = mysqli_fetch_assoc($statRes);
    $stats["total"] = (int)($statRow["total"] ?? 0);
    $stats["lowest"] = (float)($statRow["lowest"] ?? 0);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | Stay India</title>
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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#userMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="userMenu">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
                    <li class="nav-item text-white me-2">Hi, <?= htmlspecialchars($_SESSION["user_name"] ?? "User"); ?></li>
                    <li class="nav-item"><a class="nav-link text-white" href="<?= BASE_URL; ?>/user/profile.php">Profile</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="<?= BASE_URL; ?>/user/my_bookings.php">My Bookings</a></li>
                    <li class="nav-item"><a class="btn btn-outline-light btn-sm" href="<?= BASE_URL; ?>/user/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h3 class="section-title mb-0">Available Hotels</h3>
            <a href="<?= BASE_URL; ?>/user/my_bookings.php" class="btn btn-gradient btn-sm">View My Bookings</a>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-4">
                <div class="stat-card">
                    <p class="meta-text mb-1">Total Hotels</p>
                    <h4 class="mb-0"><?= $stats["total"]; ?></h4>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="stat-card">
                    <p class="meta-text mb-1">Matching Results</p>
                    <h4 class="mb-0"><?= $stats["matched"]; ?></h4>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="stat-card">
                    <p class="meta-text mb-1">Starting From</p>
                    <h4 class="mb-0">Rs. <?= number_format($stats["lowest"], 2); ?></h4>
                </div>
            </div>
        </div>

        <div class="filter-wrap p-3 p-md-4 mb-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search Hotel / City</label>
                    <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search); ?>" placeholder="e.g. Taj, Udaipur">
                </div>
                <div class="col-md-3">
                    <label class="form-label">City</label>
                    <select name="city" class="form-select">
                        <option value="">All Cities</option>
                        <?php foreach ($cities as $cityName): ?>
                            <option value="<?= htmlspecialchars($cityName); ?>" <?= $city === $cityName ? "selected" : ""; ?>><?= htmlspecialchars($cityName); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Max Price (INR)</label>
                    <input type="number" min="1" step="0.01" name="max_price" value="<?= $maxPrice > 0 ? htmlspecialchars((string)$maxPrice) : ""; ?>" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sort</label>
                    <select name="sort" class="form-select">
                        <option value="latest" <?= $sort === "latest" ? "selected" : ""; ?>>Latest</option>
                        <option value="price_low" <?= $sort === "price_low" ? "selected" : ""; ?>>Price Low</option>
                        <option value="price_high" <?= $sort === "price_high" ? "selected" : ""; ?>>Price High</option>
                        <option value="name" <?= $sort === "name" ? "selected" : ""; ?>>Name</option>
                    </select>
                </div>
                <div class="col-md-1 d-grid">
                    <button class="btn btn-gradient">Go</button>
                </div>
                <div class="col-12">
                    <a href="<?= BASE_URL; ?>/user/dashboard.php" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
                </div>
            </form>
        </div>

        <div class="row g-4">
            <?php if (count($hotels) > 0): ?>
                <?php foreach ($hotels as $hotel): ?>
                    <div class="col-sm-6 col-lg-4">
                        <div class="card hotel-card h-100">
                            <img src="<?= htmlspecialchars($resolveCardImage($hotel)); ?>" alt="Hotel image" class="hotel-image" onerror="this.onerror=null;this.src='https://images.pexels.com/photos/271624/pexels-photo-271624.jpeg?auto=compress&cs=tinysrgb&w=900';">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($hotel["name"]); ?></h5>
                                <p class="meta-text mb-2"><strong>City:</strong> <?= htmlspecialchars($hotel["city"]); ?></p>
                                <p class="price-tag mb-3">Rs. <?= number_format((float)$hotel["price"], 2); ?> / night</p>
                                <a class="btn btn-gradient mt-auto" href="<?= BASE_URL; ?>/user/book.php?hotel_id=<?= (int)$hotel["hotel_id"]; ?>">Book Now</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">No hotels added yet. Please check back soon.</div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
