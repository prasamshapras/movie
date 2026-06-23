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

/*
|--------------------------------------------------------------------------
| Fetch selected/first booking
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT 
        b.*, 
        m.title
    FROM bookings b
    JOIN showtimes s ON b.showtime_id = s.showtime_id
    JOIN movies m ON s.movie_id = m.movie_id
    WHERE b.booking_id = ?
    AND b.customer_id = ?
");
$stmt->execute([$booking_id, currentUserId()]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    die('Booking not found.');
}

if ($booking['status'] !== 'Pending') {
    die('This booking is already processed.');
}

/*
|--------------------------------------------------------------------------
| IMPORTANT FIX
|--------------------------------------------------------------------------
| Do not create new transaction_uuid here.
| Use the transaction_uuid already saved by booking_process.php.
*/

$transaction_uuid = $booking['transaction_uuid'] ?? '';

if (!$transaction_uuid) {
    die('Transaction UUID missing. Please select seats again.');
}

/*
|--------------------------------------------------------------------------
| Fetch all selected seats under same transaction_uuid
|--------------------------------------------------------------------------
*/

$seatStmt = $pdo->prepare("
    SELECT 
        booking_id,
        seat_label,
        amount
    FROM bookings
    WHERE transaction_uuid = ?
    AND customer_id = ?
    AND status = 'Pending'
    ORDER BY booking_id ASC
");
$seatStmt->execute([$transaction_uuid, currentUserId()]);
$selectedSeats = $seatStmt->fetchAll(PDO::FETCH_ASSOC);

if (!$selectedSeats) {
    die('No pending seats found for this booking.');
}

$seatLabels = array_column($selectedSeats, 'seat_label');
$actualTotalAmount = array_sum(array_column($selectedSeats, 'amount'));

/*
|--------------------------------------------------------------------------
| Payment Amount
|--------------------------------------------------------------------------
| If sandbox test mode is true, eSewa charges NPR 1.
| If false, eSewa charges real total amount.
*/

if (isset($ESEWA_TEST_MODE) && $ESEWA_TEST_MODE) {
    $amountToPay = 1.00;
} else {
    $amountToPay = $actualTotalAmount;
}

$amount = number_format((float)$amountToPay, 2, '.', '');
$taxAmount = "0";
$serviceCharge = "0";
$deliveryCharge = "0";
$totalAmount = $amount;

/*
|--------------------------------------------------------------------------
| Update payment row for whole seat group
|--------------------------------------------------------------------------
*/

$payStmt = $pdo->prepare("
    SELECT payment_id
    FROM payments
    WHERE booking_id = ?
    ORDER BY payment_id DESC
    LIMIT 1
");
$payStmt->execute([$booking_id]);
$payment_id = $payStmt->fetchColumn();

if ($payment_id) {
    $updatePayment = $pdo->prepare("
        UPDATE payments
        SET 
            amount = ?,
            payment_status = 'Pending',
            transaction_uuid = ?
        WHERE payment_id = ?
    ");
    $updatePayment->execute([
        $totalAmount,
        $transaction_uuid,
        $payment_id
    ]);
} else {
    $insertPayment = $pdo->prepare("
        INSERT INTO payments
        (
            booking_id,
            amount,
            payment_method,
            payment_status,
            transaction_uuid
        )
        VALUES (?, ?, 'eSewa', 'Pending', ?)
    ");
    $insertPayment->execute([
        $booking_id,
        $totalAmount,
        $transaction_uuid
    ]);
}

/*
|--------------------------------------------------------------------------
| eSewa Signature
|--------------------------------------------------------------------------
*/

$signature = generateEsewaSignature($totalAmount, $transaction_uuid);

$success_url = BASE_URL . "/success.php?booking_id=" . $booking_id;
$failure_url = BASE_URL . "/failure.php?booking_id=" . $booking_id;

/*
|--------------------------------------------------------------------------
| Local Simulation
|--------------------------------------------------------------------------
| Use same transaction_uuid so all selected seats are confirmed.
*/

$local_success_url = "success.php?booking_id=" . $booking_id . "&transaction_uuid=" . urlencode($transaction_uuid);

include 'includes/header.php';
?>

<div class="card" style="max-width: 500px; margin: 40px auto; text-align: center;">
    <h2>Payment Page</h2>

    <p><strong>Movie:</strong> <?= htmlspecialchars($booking['title']) ?></p>

    <p>
        <strong>Seats:</strong>
        <?= htmlspecialchars(implode(', ', $seatLabels)) ?>
    </p>

    <p>
        <strong>Ticket Total:</strong>
        NPR <?= number_format((float)$actualTotalAmount, 2) ?>
    </p>

    <?php if (isset($ESEWA_TEST_MODE) && $ESEWA_TEST_MODE): ?>
        <p style="color: #f59e0b; font-size: 14px;">
            Sandbox test mode is enabled. eSewa will charge NPR <?= htmlspecialchars($amount) ?> only.
        </p>
    <?php else: ?>
        <p>
            <strong>Amount:</strong>
            NPR <?= htmlspecialchars($amount) ?>
        </p>
    <?php endif; ?>

    <form action="<?= htmlspecialchars(ESEWA_PAYMENT_URL) ?>" method="POST">
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

        <button 
            class="btn esewa" 
            type="submit" 
            style="background: #60bb46; color: white; width: 100%; padding: 14px; border-radius: 8px; border: none; cursor: pointer; font-size: 16px; font-weight: bold;"
        >
            Pay with eSewa Sandbox
        </button>
    </form>

    <a 
        class="btn" 
        href="<?= htmlspecialchars($local_success_url) ?>" 
        style="display: block; margin-top: 15px; background: #0b5cff; color: white; padding: 14px; border-radius: 8px; text-decoration: none; font-weight: bold;"
    >
        Simulate Successful Payment
    </a>

    <a 
        class="btn btn-muted" 
        href="failure.php?booking_id=<?= $booking_id ?>" 
        style="display: none; margin-top: 15px; padding: 14px; border-radius: 8px; text-decoration: none; font-weight: bold;"
    >
        Simulate Failed Payment
    </a>

    <div class="small" style="margin-top: 15px;">
        If eSewa sandbox shows 404 or service unavailable, use Simulate Successful Payment for localhost demo.
    </div>
</div>

<?php
include 'includes/footer.php';
?>