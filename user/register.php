<?php
require_once __DIR__ . "/../config/db.php";

if (isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "/user/dashboard.php");
    exit;
}

$message = "";
$type = "danger";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $city = trim($_POST["city"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $gender = trim($_POST["gender"] ?? "");
    $dob = trim($_POST["dob"] ?? "");

    if ($name === "" || $email === "" || $password === "" || $phone === "" || $city === "") {
        $message = "Name, email, password, phone and city are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email.";
    } elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $message = "Phone number must be 10 to 15 digits.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
    } elseif ($dob !== "" && $dob > date("Y-m-d")) {
        $message = "Date of birth cannot be in the future.";
    } else {
        $checkSql = "SELECT user_id FROM users WHERE email = ?";
        $checkStmt = mysqli_prepare($conn, $checkSql);

        if ($checkStmt) {
            mysqli_stmt_bind_param($checkStmt, "s", $email);
            mysqli_stmt_execute($checkStmt);
            mysqli_stmt_store_result($checkStmt);

            if (mysqli_stmt_num_rows($checkStmt) > 0) {
                $message = "Email already registered. Please login.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $insertSql = "INSERT INTO users (name, email, password, phone, city, address, gender, dob) VALUES (?, ?, ?, ?, ?, ?, ?, NULLIF(?, ''))";
                $insertStmt = mysqli_prepare($conn, $insertSql);

                if ($insertStmt) {
                    mysqli_stmt_bind_param($insertStmt, "ssssssss", $name, $email, $hash, $phone, $city, $address, $gender, $dob);
                    if (mysqli_stmt_execute($insertStmt)) {
                        header("Location: " . BASE_URL . "/user/login.php?registered=1");
                        exit;
                    }
                }
                $message = "Unable to register right now. Try again.";
            }
            mysqli_stmt_close($checkStmt);
        } else {
            $message = "Server error. Please try again later.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Stay India</title>
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
            <a class="btn btn-outline-light btn-sm" href="<?= BASE_URL; ?>/user/login.php">Login</a>
        </div>
    </nav>

    <section class="auth-wrapper py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="glass-panel p-4 p-md-5">
                        <h3 class="section-title">Create Your Account</h3>
                        <?php if ($message !== ""): ?>
                            <div class="alert alert-<?= $type; ?> auto-dismiss"><?= htmlspecialchars($message); ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control" placeholder="10 to 15 digits" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Gender</label>
                                    <select name="gender" class="form-select">
                                        <option value="">Select</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" name="dob" class="form-control" max="<?= date("Y-m-d"); ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-gradient w-100 py-2 mt-3">Register</button>
                        </form>
                        <p class="mt-3 mb-0">Already have an account? <a href="<?= BASE_URL; ?>/user/login.php">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script src="<?= BASE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
