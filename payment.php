<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (isAdminLoggedIn()) {
    $_SESSION['error'] = "Admin cannot book tickets. Please use a customer account.";
    header("Location: admin/dashboard.php");
    exit;
}

$booking_id = intval($_GET['booking_id'] ?? 0);

if (!$booking_id) {
    die('Invalid booking.');
}

$stmt = $pdo->prepare("
    SELECT b.*, m.title
    FROM bookings b
    JOIN showtimes s ON b.showtime_id = s.showtime_id
    JOIN movies m ON s.movie_id = m.movie_id
    WHERE b.booking_id = ?
    AND b.customer_id = ?
");
$stmt->execute([$booking_id, currentUserId()]);
$booking = $stmt->fetch();

if (!$booking) {
    die('Booking not found.');
}

$payStmt = $pdo->prepare("
    SELECT amount
    FROM payments
    WHERE booking_id = ?
    ORDER BY payment_id DESC
    LIMIT 1
");
$payStmt->execute([$booking_id]);
$totalAmount = $payStmt->fetchColumn();

if (!$totalAmount) {
    $totalAmount = $booking['amount'];
}

$amount = number_format((float)$totalAmount, 2, '.', '');
$taxAmount = "0";
$serviceCharge = "0";
$deliveryCharge = "0";
$totalAmount = $amount;

$transaction_uuid = "TXN" . $booking_id . time();

$signature = generateEsewaSignature($totalAmount, $transaction_uuid);

$success_url = BASE_URL . "/success.php?booking_id=" . $booking_id . "&transaction_uuid=" . urlencode($transaction_uuid);
$failure_url = BASE_URL . "/failure.php?booking_id=" . $booking_id . "&movie_id=" . $booking['movie_id'] . "&showtime_id=" . $booking['showtime_id'];

$local_success_url = "success.php?booking_id=" . $booking_id . "&transaction_uuid=LOCALTEST" . time();

include 'includes/header.php';
?>

<div class="container" style="display: flex; justify-content: center; align-items: center; min-height: 70vh; padding: 20px;">
    <div class="card" style="width: 100%; max-width: 450px; background: #ffffff; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #eef2f7;">
        <div style="background: #f8faff; padding: 24px; border-bottom: 1px solid #eef2f7; text-align: center;">
            <h2 style="margin: 0; color: #1a1f36; font-size: 24px; font-weight: 700;">Payment Details</h2>
            <p style="margin: 8px 0 0; color: #697386; font-size: 14px;">Complete your booking for <?= htmlspecialchars($booking['title']) ?></p>
        </div>

        <div style="padding: 32px;">
            <div style="background: #fcfdfe; border: 1px dashed #d1d9e2; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                    <span style="color: #697386; font-size: 14px;">Movie</span>
                    <span style="color: #1a1f36; font-weight: 600; font-size: 14px;"><?= htmlspecialchars($booking['title']) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                    <span style="color: #697386; font-size: 14px;">Seat(s)</span>
                    <span style="color: #1a1f36; font-weight: 600; font-size: 14px;"><?= htmlspecialchars($booking['seat_label']) ?></span>
                </div>
                <div style="border-top: 1px solid #eef2f7; margin-top: 12px; padding-top: 12px; display: flex; justify-content: space-between;">
                    <span style="color: #1a1f36; font-weight: 700; font-size: 16px;">Total Amount</span>
                    <span style="color: #0b5cff; font-weight: 700; font-size: 18px;">NPR <?= htmlspecialchars($amount) ?></span>
                </div>
            </div>

            <form action="<?= ESEWA_PAYMENT_URL ?>" method="POST">
                <input type="hidden" name="amount" value="<?= htmlspecialchars($amount) ?>">
                <input type="hidden" name="tax_amount" value="<?= htmlspecialchars($taxAmount) ?>">
                <input type="hidden" name="total_amount" value="<?= htmlspecialchars($totalAmount) ?>">
                <input type="hidden" name="transaction_uuid" value="<?= htmlspecialchars($transaction_uuid) ?>">
                <input type="hidden" name="product_code" value="<?= htmlspecialchars(ESEWA_PRODUCT_CODE) ?>">
                <input type="hidden" name="product_service_charge" value="<?= htmlspecialchars($serviceCharge) ?>">
                <input type="hidden" name="product_delivery_charge" value="<?= htmlspecialchars($deliveryCharge) ?>">
                <input type="hidden" name="success_url" value="<?= htmlspecialchars($success_url) ?>">
                <input type="hidden" name="failure_url" value="<?= htmlspecialchars($failure_url) ?>">
                <input type="hidden" name="signed_field_names" value="total_amount,transaction_uuid,product_code">
                <input type="hidden" name="signature" value="<?= htmlspecialchars($signature) ?>">

                <button class="btn esewa" type="submit" style="background: #60bb46; color: white; width: 100%; padding: 16px; border-radius: 12px; border: none; cursor: pointer; font-size: 16px; font-weight: 700; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 6px rgba(96, 187, 70, 0.2);">
                    Pay with eSewa
                </button>
            </form>

            <div style="margin: 20px 0; display: flex; align-items: center; text-align: center; color: #a3acb9;">
                <div style="flex: 1; height: 1px; background: #eef2f7;"></div>
                <span style="padding: 0 10px; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">OR</span>
                <div style="flex: 1; height: 1px; background: #eef2f7;"></div>
            </div>

            <a class="btn" href="<?= htmlspecialchars($local_success_url) ?>" style="display: block; text-align: center; background: #ffffff; color: #0b5cff; border: 2px solid #0b5cff; padding: 14px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 15px; transition: all 0.2s;">
                Quick Demo: Confirm Payment
            </a>

            <p style="margin-top: 24px; color: #697386; font-size: 12px; text-align: center; line-height: 1.5;">
                Secure payment processing. If the sandbox environment is unavailable, use <strong>Quick Demo</strong> to complete your booking.
            </p>
        </div>
    </div>
</div>

<style>
.btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}
.btn.esewa:hover {
    box-shadow: 0 6px 12px rgba(96, 187, 70, 0.3);
}
</style>

<?php
include 'includes/footer.php';
?>