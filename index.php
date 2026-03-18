<?php require_once __DIR__ . "/config/db.php"; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stay India | Premium Stay</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-premium">
        <div class="container">
            <a class="navbar-brand" href="<?= BASE_URL; ?>/index.php">Stay India</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
                    <li class="nav-item"><a class="nav-link text-white" href="<?= BASE_URL; ?>/user/login.php">Login</a></li>
                    <li class="nav-item"><a class="btn btn-gradient px-3" href="<?= BASE_URL; ?>/user/register.php">Register</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <div class="container">
            <div class="hero-card">
                <h1 class="display-5 fw-bold">Luxury stays. Easy booking.</h1>
                <p class="lead mt-3 mb-4">Discover elegant hotels, compare city prices, and reserve your next comfortable stay in a few clicks.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= BASE_URL; ?>/user/register.php" class="btn btn-gradient px-4 py-2">Start Booking</a>
                    <a href="<?= BASE_URL; ?>/user/login.php" class="btn btn-outline-light px-4 py-2">Login</a>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
