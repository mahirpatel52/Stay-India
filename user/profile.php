<?php
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "/user/login.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];
$message = "";
$type = "danger";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_profile"])) {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $city = trim($_POST["city"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $gender = trim($_POST["gender"] ?? "");
    $dob = trim($_POST["dob"] ?? "");

    if ($name === "" || $email === "" || $phone === "" || $city === "") {
        $message = "Name, email, phone and city are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email.";
    } elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $message = "Phone number must be 10 to 15 digits.";
    } elseif ($dob !== "" && $dob > date("Y-m-d")) {
        $message = "Date of birth cannot be in the future.";
    } else {
        $checkSql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $checkStmt = mysqli_prepare($conn, $checkSql);
        if ($checkStmt) {
            mysqli_stmt_bind_param($checkStmt, "si", $email, $userId);
            mysqli_stmt_execute($checkStmt);
            $checkRes = mysqli_stmt_get_result($checkStmt);
            $exists = mysqli_fetch_assoc($checkRes);
            mysqli_stmt_close($checkStmt);

            if ($exists) {
                $message = "Email is already used by another account.";
            } else {
                $updateSql = "UPDATE users
                              SET name = ?, email = ?, phone = ?, city = ?, address = ?, gender = ?, dob = NULLIF(?, '')
                              WHERE user_id = ?";
                $updateStmt = mysqli_prepare($conn, $updateSql);
                if ($updateStmt) {
                    mysqli_stmt_bind_param($updateStmt, "sssssssi", $name, $email, $phone, $city, $address, $gender, $dob, $userId);
                    if (mysqli_stmt_execute($updateStmt)) {
                        $_SESSION["user_name"] = $name;
                        $message = "Profile updated successfully.";
                        $type = "success";
                    } else {
                        $message = "Unable to update profile.";
                    }
                    mysqli_stmt_close($updateStmt);
                }
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["change_password"])) {
    $currentPassword = trim($_POST["current_password"] ?? "");
    $newPassword = trim($_POST["new_password"] ?? "");
    $confirmPassword = trim($_POST["confirm_password"] ?? "");

    if ($currentPassword === "" || $newPassword === "" || $confirmPassword === "") {
        $message = "All password fields are required.";
    } elseif (strlen($newPassword) < 6) {
        $message = "New password must be at least 6 characters.";
    } elseif ($newPassword !== $confirmPassword) {
        $message = "New password and confirm password do not match.";
    } else {
        $passSql = "SELECT password FROM users WHERE user_id = ?";
        $passStmt = mysqli_prepare($conn, $passSql);
        if ($passStmt) {
            mysqli_stmt_bind_param($passStmt, "i", $userId);
            mysqli_stmt_execute($passStmt);
            $passRes = mysqli_stmt_get_result($passStmt);
            $row = mysqli_fetch_assoc($passRes);
            mysqli_stmt_close($passStmt);

            if ($row && password_verify($currentPassword, $row["password"])) {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updatePassSql = "UPDATE users SET password = ? WHERE user_id = ?";
                $updatePassStmt = mysqli_prepare($conn, $updatePassSql);
                if ($updatePassStmt) {
                    mysqli_stmt_bind_param($updatePassStmt, "si", $newHash, $userId);
                    if (mysqli_stmt_execute($updatePassStmt)) {
                        $message = "Password changed successfully.";
                        $type = "success";
                    } else {
                        $message = "Could not change password.";
                    }
                    mysqli_stmt_close($updatePassStmt);
                }
            } else {
                $message = "Current password is incorrect.";
            }
        }
    }
}

$user = null;
$userSql = "SELECT name, email, phone, city, address, gender, dob FROM users WHERE user_id = ?";
$userStmt = mysqli_prepare($conn, $userSql);
if ($userStmt) {
    mysqli_stmt_bind_param($userStmt, "i", $userId);
    mysqli_stmt_execute($userStmt);
    $userRes = mysqli_stmt_get_result($userStmt);
    $user = mysqli_fetch_assoc($userRes);
    mysqli_stmt_close($userStmt);
}

$stats = [
    "bookings" => 0,
    "last_booking" => "No bookings yet"
];
$statsSql = "SELECT COUNT(*) AS total, MAX(COALESCE(check_in_date, date)) AS last_date FROM bookings WHERE user_id = ?";
$statsStmt = mysqli_prepare($conn, $statsSql);
if ($statsStmt) {
    mysqli_stmt_bind_param($statsStmt, "i", $userId);
    mysqli_stmt_execute($statsStmt);
    $statsRes = mysqli_stmt_get_result($statsStmt);
    $statsRow = mysqli_fetch_assoc($statsRes);
    mysqli_stmt_close($statsStmt);

    $stats["bookings"] = (int)($statsRow["total"] ?? 0);
    if (!empty($statsRow["last_date"])) {
        $stats["last_booking"] = $statsRow["last_date"];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Stay India</title>
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
                <a class="btn btn-outline-light btn-sm" href="<?= BASE_URL; ?>/user/dashboard.php">Dashboard</a>
                <a class="btn btn-outline-light btn-sm" href="<?= BASE_URL; ?>/user/logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <h3 class="section-title">My Profile</h3>
        <?php if ($message !== ""): ?>
            <div class="alert alert-<?= $type; ?> auto-dismiss"><?= htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="profile-summary p-4 h-100">
                    <h5 class="mb-3"><?= htmlspecialchars($user["name"] ?? "User"); ?></h5>
                    <p class="meta-text mb-2"><?= htmlspecialchars($user["email"] ?? ""); ?></p>
                    <p class="meta-text mb-2"><?= htmlspecialchars($user["phone"] ?? ""); ?></p>
                    <p class="meta-text mb-2"><?= htmlspecialchars($user["city"] ?? ""); ?></p>
                    <p class="meta-text mb-2"><?= htmlspecialchars($user["gender"] ?? ""); ?></p>
                    <p class="meta-text mb-2"><?= htmlspecialchars($user["dob"] ?? ""); ?></p>
                    <p class="meta-text mb-2"><?= htmlspecialchars($user["address"] ?? ""); ?></p>
                    <hr>
                    <p class="mb-2"><strong>Total Bookings:</strong> <?= $stats["bookings"]; ?></p>
                    <p class="mb-0"><strong>Last Booking Date:</strong> <?= htmlspecialchars($stats["last_booking"]); ?></p>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="glass-panel p-4 mb-4">
                    <h5 class="mb-3">Update Profile</h5>
                    <form method="POST">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user["name"] ?? ""); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user["email"] ?? ""); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user["phone"] ?? ""); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($user["city"] ?? ""); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="">Select</option>
                                    <option value="Male" <?= (($user["gender"] ?? "") === "Male") ? "selected" : ""; ?>>Male</option>
                                    <option value="Female" <?= (($user["gender"] ?? "") === "Female") ? "selected" : ""; ?>>Female</option>
                                    <option value="Other" <?= (($user["gender"] ?? "") === "Other") ? "selected" : ""; ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($user["dob"] ?? ""); ?>" max="<?= date("Y-m-d"); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($user["address"] ?? ""); ?></textarea>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-gradient px-4">Save Profile</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="glass-panel p-4">
                    <h5 class="mb-3">Change Password</h5>
                    <form method="POST">
                        <input type="hidden" name="change_password" value="1">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-gradient px-4">Update Password</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <script src="<?= BASE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
