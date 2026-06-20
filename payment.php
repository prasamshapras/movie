<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
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
$failure_url = BASE_URL . "/failure.php?booking_id=" . $booking_id;

$local_success_url = "success.php?booking_id=" . $booking_id . "&transaction_uuid=LOCALTEST" . time();

include 'includes/header.php';
?>

<div class="card" style="max-width: 500px; margin: 40px auto; text-align: center;">
    <h2>Payment Page</h2>

    <p><strong>Movie:</strong> <?= htmlspecialchars($booking['title']) ?></p>
    <p><strong>Seat:</strong> <?= htmlspecialchars($booking['seat_label']) ?></p>
    <p><strong>Amount:</strong> NPR <?= htmlspecialchars($amount) ?></p>

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

        <button class="btn esewa" type="submit" style="background: #60bb46; color: white; width: 100%; padding: 14px; border-radius: 8px; border: none; cursor: pointer; font-size: 16px; font-weight: bold;">Pay with eSewa Sandbox</button>
    </form>

    <a class="btn" href="<?= htmlspecialchars($local_success_url) ?>" style="display: block; margin-top: 15px; background: #0b5cff; color: white; padding: 14px; border-radius: 8px; text-decoration: none; font-weight: bold;">
        Simulate Successful Payment
    </a>

    <a class="btn btn-muted" href="failure.php?booking_id=<?= $booking_id ?>" style="display: none; margin-top: 15px; padding: 14px; border-radius: 8px; text-decoration: none; font-weight: bold;">
        Simulate Failed Payment
    </a>

    <div class="small" style="margin-top: 15px;">
        If eSewa sandbox shows 404 or service unavailable, use Simulate Successful Payment for localhost demo.
    </div>
</div>

<?php
include 'includes/footer.php';
?>