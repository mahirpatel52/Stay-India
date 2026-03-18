<?php
require_once __DIR__ . "/../config/db.php";

if (isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] === true) {
    header("Location: " . BASE_URL . "/admin/dashboard.php");
    exit;
}

if (isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "/user/dashboard.php");
    exit;
}

$message = "";
$type = "danger";

if (isset($_GET["registered"])) {
    $message = "Registration successful. Please login.";
    $type = "success";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($email === "" || $password === "") {
        $message = "Email and password are required.";
        $type = "danger";
    } elseif ($email === ADMIN_EMAIL && $password === ADMIN_PASSWORD) {
        unset($_SESSION["user_id"], $_SESSION["user_name"]);
        $_SESSION["is_admin"] = true;
        $_SESSION["admin_email"] = ADMIN_EMAIL;
        header("Location: " . BASE_URL . "/admin/dashboard.php");
        exit;
    } else {
        $sql = "SELECT user_id, name, password FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if ($user && password_verify($password, $user["password"])) {
                unset($_SESSION["is_admin"], $_SESSION["admin_email"]);
                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["user_name"] = $user["name"];
                header("Location: " . BASE_URL . "/user/dashboard.php");
                exit;
            } else {
                $message = "Invalid credentials.";
                $type = "danger";
            }
        } else {
            $message = "Server error. Please retry.";
            $type = "danger";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Stay India</title>
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
            <a class="btn btn-outline-light btn-sm" href="<?= BASE_URL; ?>/user/register.php">Register</a>
        </div>
    </nav>

    <section class="auth-wrapper py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5">
                    <div class="glass-panel p-4 p-md-5">
                        <h3 class="section-title">Welcome Back</h3>
                        <?php if ($message !== ""): ?>
                            <div class="alert alert-<?= $type; ?> auto-dismiss"><?= htmlspecialchars($message); ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-gradient w-100 py-2">Login</button>
                        </form>
                        <div class="d-flex justify-content-between mt-3">
                            <a href="<?= BASE_URL; ?>/user/forgot_password.php">Forgot Password?</a>
                            <a href="<?= BASE_URL; ?>/user/register.php">Create account</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script src="<?= BASE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
