<?php
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "/user/login.php");
    exit;
}

$bookingId = isset($_GET["booking_id"]) ? (int)$_GET["booking_id"] : 0;
if ($bookingId <= 0) {
    header("Location: " . BASE_URL . "/user/my_bookings.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];
$message = "";
$type = "danger";

$bookingSql = "SELECT b.booking_id, b.date, b.check_in_date, b.check_out_date, h.name AS hotel_name, h.city, h.price
               FROM bookings b
               INNER JOIN hotels h ON b.hotel_id = h.hotel_id
               WHERE b.booking_id = ? AND b.user_id = ?";
$bookingStmt = mysqli_prepare($conn, $bookingSql);
$booking = null;
if ($bookingStmt) {
    mysqli_stmt_bind_param($bookingStmt, "ii", $bookingId, $userId);
    mysqli_stmt_execute($bookingStmt);
    $bookingRes = mysqli_stmt_get_result($bookingStmt);
    $booking = mysqli_fetch_assoc($bookingRes);
    mysqli_stmt_close($bookingStmt);
}

if (!$booking) {
    header("Location: " . BASE_URL . "/user/my_bookings.php");
    exit;
}

$paymentSql = "SELECT payment_id, booking_id, amount, payment_method, payer_reference, transaction_id, payment_status, paid_at
               FROM payments WHERE booking_id = ?";
$paymentStmt = mysqli_prepare($conn, $paymentSql);
$payment = null;
if ($paymentStmt) {
    mysqli_stmt_bind_param($paymentStmt, "i", $bookingId);
    mysqli_stmt_execute($paymentStmt);
    $paymentRes = mysqli_stmt_get_result($paymentStmt);
    $payment = mysqli_fetch_assoc($paymentRes);
    mysqli_stmt_close($paymentStmt);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $method = trim($_POST["payment_method"] ?? "");
    $payerReference = trim($_POST["payer_reference"] ?? "");
    $validMethods = ["UPI", "Pay on Arrival"];

    if (!in_array($method, $validMethods, true)) {
        $message = "Please select a valid payment method.";
    } elseif ($method === "UPI" && $payerReference === "") {
        $message = "Enter your UPI ID.";
    } else {
        $amount = (float)$booking["price"];
        $isUpi = $method === "UPI";
        $txnPrefix = $isUpi ? "TXN" : "POA";
        $txnId = $txnPrefix . date("YmdHis") . rand(1000, 9999);
        $status = $isUpi ? "Paid" : "Pending";
        $reference = $isUpi ? $payerReference : "Pay at Hotel";

        if ($payment) {
            $updateSql = "UPDATE payments
                          SET amount = ?, payment_method = ?, payer_reference = ?, transaction_id = ?, payment_status = ?, paid_at = " . ($isUpi ? "NOW()" : "NULL") . "
                          WHERE booking_id = ?";
            $updateStmt = mysqli_prepare($conn, $updateSql);
            if ($updateStmt) {
                mysqli_stmt_bind_param($updateStmt, "dssssi", $amount, $method, $reference, $txnId, $status, $bookingId);
                if (mysqli_stmt_execute($updateStmt)) {
                    if ($isUpi) {
                        $message = "UPI payment successful. Booking confirmed.";
                        $type = "success";
                    } else {
                        header("Location: " . BASE_URL . "/user/my_bookings.php?arrival=1");
                        exit;
                    }
                } else {
                    $message = "Payment update failed. Try again.";
                }
                mysqli_stmt_close($updateStmt);
            } else {
                $message = "Server error. Please retry.";
            }
        } else {
            $insertSql = "INSERT INTO payments (booking_id, amount, payment_method, payer_reference, transaction_id, payment_status, paid_at)
                          VALUES (?, ?, ?, ?, ?, ?, " . ($isUpi ? "NOW()" : "NULL") . ")";
            $insertStmt = mysqli_prepare($conn, $insertSql);
            if ($insertStmt) {
                mysqli_stmt_bind_param($insertStmt, "idssss", $bookingId, $amount, $method, $reference, $txnId, $status);
                if (mysqli_stmt_execute($insertStmt)) {
                    if ($isUpi) {
                        $message = "UPI payment successful. Booking confirmed.";
                        $type = "success";
                    } else {
                        header("Location: " . BASE_URL . "/user/my_bookings.php?arrival=1");
                        exit;
                    }
                } else {
                    $message = "Payment failed. Please try again.";
                }
                mysqli_stmt_close($insertStmt);
            } else {
                $message = "Server error. Please retry.";
            }
        }

        // Refresh latest payment details after submit.
        $paymentStmt = mysqli_prepare($conn, $paymentSql);
        if ($paymentStmt) {
            mysqli_stmt_bind_param($paymentStmt, "i", $bookingId);
            mysqli_stmt_execute($paymentStmt);
            $paymentRes = mysqli_stmt_get_result($paymentStmt);
            $payment = mysqli_fetch_assoc($paymentRes);
            mysqli_stmt_close($paymentStmt);
        }
    }
}

$paymentStatus = $payment["payment_status"] ?? "Pending";
$isPaid = $paymentStatus === "Paid";
$isPayOnArrival = ($payment["payment_method"] ?? "") === "Pay on Arrival";
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment | Stay India</title>
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
            <a class="btn btn-outline-light btn-sm" href="<?= BASE_URL; ?>/user/my_bookings.php">My Bookings</a>
        </div>
    </nav>

    <main class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="glass-panel p-4 p-md-5">
                    <h3 class="section-title">Secure Payment</h3>
                    <?php if ($message !== ""): ?>
                        <div class="alert alert-<?= $type; ?> auto-dismiss"><?= htmlspecialchars($message); ?></div>
                    <?php endif; ?>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Hotel:</strong> <?= htmlspecialchars($booking["hotel_name"]); ?></p>
                            <p class="mb-1"><strong>City:</strong> <?= htmlspecialchars($booking["city"]); ?></p>
                            <p class="mb-0"><strong>Stay:</strong> <?= htmlspecialchars($booking["check_in_date"] ?: $booking["date"]); ?> to <?= htmlspecialchars($booking["check_out_date"] ?: ($booking["date"] ?: "-")); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Amount:</strong> Rs. <?= number_format((float)$booking["price"], 2); ?></p>
                            <p class="mb-1"><strong>Status:</strong>
                                <span class="badge <?= $isPaid ? "text-bg-success" : "text-bg-warning"; ?>"><?= htmlspecialchars($paymentStatus); ?></span>
                            </p>
                            <?php if ($payment && !empty($payment["transaction_id"])): ?>
                                <p class="mb-0"><strong>Txn ID:</strong> <?= htmlspecialchars($payment["transaction_id"]); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!$isPaid && !$isPayOnArrival): ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" id="payment_method" class="form-select" required>
                                    <option value="UPI">UPI</option>
                                    <option value="Pay on Arrival">Pay on Arrival</option>
                                </select>
                            </div>
                            <div class="mb-3" id="upi_ref_group">
                                <label class="form-label">UPI ID</label>
                                <input type="text" id="payer_reference" name="payer_reference" class="form-control" placeholder="example@upi" required>
                            </div>
                            <button type="submit" class="btn btn-gradient px-4">Pay Now</button>
                        </form>
                    <?php elseif ($isPayOnArrival): ?>
                        <div class="alert alert-info mb-3">Pay on Arrival selected. Payment will be collected at hotel check-in.</div>
                        <a href="<?= BASE_URL; ?>/user/my_bookings.php" class="btn btn-gradient">Go To My Bookings</a>
                    <?php else: ?>
                        <a href="<?= BASE_URL; ?>/user/my_bookings.php" class="btn btn-gradient">Go To My Bookings</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <script src="<?= BASE_URL; ?>/assets/js/script.js"></script>
    <script>
        (function () {
            const methodSelect = document.getElementById("payment_method");
            const refGroup = document.getElementById("upi_ref_group");
            const refInput = document.getElementById("payer_reference");

            if (!methodSelect || !refGroup || !refInput) return;

            function toggleReference() {
                const isUpi = methodSelect.value === "UPI";
                refGroup.style.display = isUpi ? "block" : "none";
                refInput.required = isUpi;
                if (!isUpi) {
                    refInput.value = "";
                }
            }

            methodSelect.addEventListener("change", toggleReference);
            toggleReference();
        })();
    </script>
</body>
</html>
