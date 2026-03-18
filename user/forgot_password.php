<?php
require_once __DIR__ . "/../config/db.php";

function smtpReadResponse($socket)
{
    $response = "";
    while ($line = fgets($socket, 515)) {
        $response .= $line;
        if (strlen($line) < 4 || $line[3] === " ") {
            break;
        }
    }
    return $response;
}

function smtpExpect($response, $codes)
{
    foreach ($codes as $code) {
        if (strpos($response, (string)$code) === 0) {
            return true;
        }
    }
    return false;
}

function smtpSendCommand($socket, $command, $okCodes)
{
    fwrite($socket, $command . "\r\n");
    $response = smtpReadResponse($socket);
    return smtpExpect($response, $okCodes) ? "" : $response;
}

function sendOtpEmail($toEmail, $subject, $body)
{
    $transport = (SMTP_ENCRYPTION === "ssl" ? "ssl://" : "") . SMTP_HOST . ":" . SMTP_PORT;
    $socket = @stream_socket_client($transport, $errno, $errstr, SMTP_TIMEOUT);
    if (!$socket) {
        return "Socket error: " . $errstr;
    }

    stream_set_timeout($socket, SMTP_TIMEOUT);
    $greeting = smtpReadResponse($socket);
    if (!smtpExpect($greeting, [220])) {
        fclose($socket);
        return $greeting;
    }

    $hostName = "localhost";
    $error = smtpSendCommand($socket, "EHLO " . $hostName, [250]);
    if ($error !== "") {
        fclose($socket);
        return $error;
    }

    if (SMTP_ENCRYPTION === "tls") {
        $error = smtpSendCommand($socket, "STARTTLS", [220]);
        if ($error !== "") {
            fclose($socket);
            return $error;
        }

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return "Failed to enable TLS encryption.";
        }

        $error = smtpSendCommand($socket, "EHLO " . $hostName, [250]);
        if ($error !== "") {
            fclose($socket);
            return $error;
        }
    }

    $error = smtpSendCommand($socket, "AUTH LOGIN", [334]);
    if ($error !== "") {
        fclose($socket);
        return $error;
    }

    $error = smtpSendCommand($socket, base64_encode(SMTP_USERNAME), [334]);
    if ($error !== "") {
        fclose($socket);
        return $error;
    }

    $error = smtpSendCommand($socket, base64_encode(SMTP_PASSWORD), [235]);
    if ($error !== "") {
        fclose($socket);
        return $error;
    }

    $error = smtpSendCommand($socket, "MAIL FROM:<" . SMTP_FROM . ">", [250]);
    if ($error !== "") {
        fclose($socket);
        return $error;
    }

    $error = smtpSendCommand($socket, "RCPT TO:<" . $toEmail . ">", [250, 251]);
    if ($error !== "") {
        fclose($socket);
        return $error;
    }

    $error = smtpSendCommand($socket, "DATA", [354]);
    if ($error !== "") {
        fclose($socket);
        return $error;
    }

    $headers = [];
    $headers[] = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">";
    $headers[] = "To: <" . $toEmail . ">";
    $headers[] = "Subject: " . $subject;
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: text/plain; charset=UTF-8";

    $messageData = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
    fwrite($socket, $messageData . "\r\n");
    $response = smtpReadResponse($socket);
    if (!smtpExpect($response, [250])) {
        fclose($socket);
        return $response;
    }

    smtpSendCommand($socket, "QUIT", [221]);
    fclose($socket);
    return "";
}

$message = "";
$type = "danger";
$step = "request_email";
$alertClass = "auto-dismiss";

if (!isset($_SESSION["fp"])) {
    $_SESSION["fp"] = [
        "email" => "",
        "otp" => "",
        "expires_at" => 0,
        "verified" => false
    ];
}

// Always start from email step on fresh page load.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION["fp"] = [
        "email" => "",
        "otp" => "",
        "expires_at" => 0,
        "verified" => false
    ];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "send_otp") {
        $email = trim($_POST["email"] ?? "");

        if ($email === "") {
            $message = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Invalid email format.";
        } else {
            $checkSql = "SELECT user_id FROM users WHERE email = ?";
            $checkStmt = mysqli_prepare($conn, $checkSql);

            if ($checkStmt) {
                mysqli_stmt_bind_param($checkStmt, "s", $email);
                mysqli_stmt_execute($checkStmt);
                $result = mysqli_stmt_get_result($checkStmt);
                $user = mysqli_fetch_assoc($result);
                mysqli_stmt_close($checkStmt);

                if ($user) {
                    $otp = (string)random_int(100000, 999999);
                    $subject = "Stay India Password Reset OTP";
                    $body = "Your OTP for password reset is: " . $otp . "\nThis OTP is valid for 10 minutes.";
                    $smtpError = sendOtpEmail($email, $subject, $body);
                    $mailSent = ($smtpError === "");

                    $_SESSION["fp"]["email"] = $email;
                    $_SESSION["fp"]["otp"] = $otp;
                    $_SESSION["fp"]["expires_at"] = time() + 600;
                    $_SESSION["fp"]["verified"] = false;
                    $type = "success";
                    $step = "verify_otp";

                    if ($mailSent) {
                        $message = "OTP sent to your email. Please check inbox/spam.";
                        $alertClass = "auto-dismiss";
                    } else {
                        // Local dev fallback when SMTP is not configured.
                        $message = "Temporary OTP: " . $otp;
                        $alertClass = "";
                    }
                } else {
                    $message = "Email not found.";
                    $alertClass = "auto-dismiss";
                }
            } else {
                $message = "Server error. Try again.";
                $alertClass = "auto-dismiss";
            }
        }
    }

    if ($action === "verify_otp") {
        $enteredOtp = trim($_POST["otp"] ?? "");
        $email = $_SESSION["fp"]["email"] ?? "";
        $savedOtp = $_SESSION["fp"]["otp"] ?? "";
        $expiresAt = (int)($_SESSION["fp"]["expires_at"] ?? 0);

        if ($email === "" || $savedOtp === "") {
            $message = "Session expired. Request OTP again.";
            $alertClass = "auto-dismiss";
            $_SESSION["fp"] = ["email" => "", "otp" => "", "expires_at" => 0, "verified" => false];
            $step = "request_email";
        } elseif ($enteredOtp === "") {
            $message = "Please enter OTP.";
            $alertClass = "auto-dismiss";
            $step = "verify_otp";
        } elseif (time() > $expiresAt) {
            $message = "OTP expired. Request a new OTP.";
            $alertClass = "auto-dismiss";
            $_SESSION["fp"]["otp"] = "";
            $_SESSION["fp"]["expires_at"] = 0;
            $_SESSION["fp"]["verified"] = false;
            $step = "request_email";
        } elseif ($enteredOtp !== $savedOtp) {
            $message = "Invalid OTP.";
            $alertClass = "auto-dismiss";
            $step = "verify_otp";
        } else {
            $_SESSION["fp"]["verified"] = true;
            $message = "OTP verified. Set your new password.";
            $type = "success";
            $alertClass = "auto-dismiss";
            $step = "reset_password";
        }
    }

    if ($action === "reset_password") {
        $email = $_SESSION["fp"]["email"] ?? "";
        $verified = (bool)($_SESSION["fp"]["verified"] ?? false);
        $newPassword = trim($_POST["new_password"] ?? "");
        $confirmPassword = trim($_POST["confirm_password"] ?? "");

        if ($email === "" || !$verified) {
            $message = "Verification required. Please complete OTP verification.";
            $alertClass = "auto-dismiss";
            $step = "request_email";
        } elseif ($newPassword === "" || $confirmPassword === "") {
            $message = "Both password fields are required.";
            $alertClass = "auto-dismiss";
            $step = "reset_password";
        } elseif (strlen($newPassword) < 6) {
            $message = "Password must be at least 6 characters.";
            $alertClass = "auto-dismiss";
            $step = "reset_password";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "Passwords do not match.";
            $alertClass = "auto-dismiss";
            $step = "reset_password";
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateSql = "UPDATE users SET password = ? WHERE email = ?";
            $updateStmt = mysqli_prepare($conn, $updateSql);
            if ($updateStmt) {
                mysqli_stmt_bind_param($updateStmt, "ss", $hash, $email);
                if (mysqli_stmt_execute($updateStmt)) {
                    $_SESSION["fp"] = ["email" => "", "otp" => "", "expires_at" => 0, "verified" => false];
                    $message = "Password reset successful. Login with the new password.";
                    $type = "success";
                    $alertClass = "auto-dismiss";
                    $step = "done";
                } else {
                    $message = "Unable to reset password right now.";
                    $alertClass = "auto-dismiss";
                    $step = "reset_password";
                }
                mysqli_stmt_close($updateStmt);
            } else {
                $message = "Server error. Try again.";
                $alertClass = "auto-dismiss";
                $step = "reset_password";
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Stay India</title>
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
            <a class="btn btn-outline-light btn-sm" href="<?= BASE_URL; ?>/user/login.php">Back to Login</a>
        </div>
    </nav>
    <section class="auth-wrapper py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5">
                    <div class="glass-panel p-4 p-md-5">
                        <h3 class="section-title">Reset Password</h3>
                        <p class="meta-text">Enter email, verify OTP, then set a new password.</p>
                        <?php if ($message !== ""): ?>
                            <div class="alert alert-<?= $type; ?> <?= $alertClass; ?>"><?= htmlspecialchars($message); ?></div>
                        <?php endif; ?>
                        <?php if ($step === "request_email"): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="send_otp">
                                <div class="mb-3">
                                    <label class="form-label">Registered Email</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-gradient w-100 py-2">Send OTP</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($step === "verify_otp"): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="verify_otp">
                                <div class="mb-3">
                                    <label class="form-label">Enter OTP</label>
                                    <input type="text" name="otp" class="form-control" maxlength="6" required>
                                </div>
                                <button type="submit" class="btn btn-gradient w-100 py-2">Verify OTP</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($step === "reset_password"): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="reset_password">
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-gradient w-100 py-2">Reset Password</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($step === "done"): ?>
                            <a href="<?= BASE_URL; ?>/user/login.php" class="btn btn-gradient w-100 py-2">Go To Login</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script src="<?= BASE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
