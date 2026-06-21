<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

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
$transaction_uuid = $_GET['transaction_uuid'] ?? null;

if (!$booking_id) {
    die('Invalid booking.');
}

// Fetch the main booking
$stmt = $pdo->prepare("
    SELECT 
        b.*, 
        s.show_date, 
        s.show_time, 
        s.screen, 
        m.title, 
        m.poster, 
        m.duration, 
        m.language
    FROM bookings b
    JOIN showtimes s ON b.showtime_id = s.showtime_id
    JOIN movies m ON s.movie_id = m.movie_id
    WHERE b.booking_id = ?
    AND b.customer_id = ?
");
$stmt->execute([$booking_id, currentUserId()]);
$mainBooking = $stmt->fetch();

if (!$mainBooking) {
    die('Booking not found.');
}

// If eSewa returns data, verify the real eSewa payment response
// If local simulation returns transaction_uuid, confirm directly for demo
if (($transaction_uuid || isset($_GET['data'])) && $mainBooking['status'] === 'Pending') {
    try {
        $pdo->beginTransaction();

        /*
        |--------------------------------------------------------------------------
        | eSewa V2 Success Response Verification
        |--------------------------------------------------------------------------
        | eSewa redirects to success.php?booking_id=...&data=BASE64_RESPONSE
        | Local simulation redirects with transaction_uuid only.
        */

        if (isset($_GET['data'])) {
            $eSewaData = verifyEsewaResponse($_GET['data']);

            if (!$eSewaData) {
                throw new Exception("eSewa signature verification failed.");
            }

            if (($eSewaData['status'] ?? '') !== 'COMPLETE') {
                throw new Exception("eSewa payment is not complete.");
            }

            if (($eSewaData['product_code'] ?? '') !== ESEWA_PRODUCT_CODE) {
                throw new Exception("Invalid eSewa product code.");
            }

            $paidAmount = (float) str_replace(',', '', $eSewaData['total_amount']);

            // Expected amount check
            if (isset($ESEWA_TEST_MODE) && $ESEWA_TEST_MODE) {
                $expectedAmount = 1.00;
            } else {
                $payStmt = $pdo->prepare("
                    SELECT amount 
                    FROM payments 
                    WHERE booking_id = ? 
                    ORDER BY payment_id DESC 
                    LIMIT 1
                ");
                $payStmt->execute([$booking_id]);
                $expectedAmount = (float) $payStmt->fetchColumn();

                if (!$expectedAmount) {
                    $expectedAmount = (float) $mainBooking['amount'];
                }
            }

            if (abs($paidAmount - $expectedAmount) > 0.01) {
                throw new Exception("Amount mismatch. Expected: $expectedAmount, Paid: $paidAmount");
            }

            $transaction_uuid = $eSewaData['transaction_uuid'];
            $transaction_code = $eSewaData['transaction_code'] ?? null;
        } else {
            // Local simulation transaction code
            $transaction_code = $transaction_uuid;
        }

        if (!$transaction_uuid) {
            throw new Exception("Missing transaction UUID.");
        }

        /*
        |--------------------------------------------------------------------------
        | Booking Limit Check
        |--------------------------------------------------------------------------
        | Keeps your previous rule: max 10 confirmed movies per customer.
        */

        $stmtLimit = $pdo->prepare("
            SELECT COUNT(DISTINCT s.movie_id)
            FROM bookings b
            JOIN showtimes s ON b.showtime_id = s.showtime_id
            WHERE b.customer_id = ?
            AND b.status = 'Confirmed'
        ");
        $stmtLimit->execute([currentUserId()]);
        $totalConfirmedMovies = $stmtLimit->fetchColumn();

        if ($totalConfirmedMovies >= 10) {
            $stmtThisMovie = $pdo->prepare("
                SELECT COUNT(*)
                FROM bookings b
                JOIN showtimes s ON b.showtime_id = s.showtime_id
                WHERE b.customer_id = ?
                AND s.movie_id = ?
                AND b.status = 'Confirmed'
            ");
            $stmtThisMovie->execute([currentUserId(), $mainBooking['movie_id'] ?? 0]);

            if ($stmtThisMovie->fetchColumn() == 0) {
                throw new Exception("You have reached the maximum booking limit of 10 movies.");
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Find Pending Bookings to Confirm
        |--------------------------------------------------------------------------
        | First try transaction_uuid.
        | If transaction_uuid was not saved before payment, confirm current booking.
        */

        $allStmt = $pdo->prepare("
            SELECT *
            FROM bookings
            WHERE transaction_uuid = ?
            AND customer_id = ?
            AND status = 'Pending'
        ");
        $allStmt->execute([$transaction_uuid, currentUserId()]);
        $allBookings = $allStmt->fetchAll();

        // Fallback: confirm current booking if transaction_uuid was not saved earlier
        if (!$allBookings) {
            $allStmt = $pdo->prepare("
                SELECT *
                FROM bookings
                WHERE booking_id = ?
                AND customer_id = ?
                AND status = 'Pending'
            ");
            $allStmt->execute([$booking_id, currentUserId()]);
            $allBookings = $allStmt->fetchAll();
        }

        if (!$allBookings) {
            throw new Exception("No pending booking found to confirm.");
        }

        /*
        |--------------------------------------------------------------------------
        | Confirm Booking, Seat and Payment
        |--------------------------------------------------------------------------
        */

        foreach ($allBookings as $booking) {
            // Update booking status
            $updateBooking = $pdo->prepare("
                UPDATE bookings 
                SET 
                    status = 'Confirmed',
                    payment_status = 'paid',
                    payment_ref = ?,
                    transaction_uuid = ?
                WHERE booking_id = ?
                AND customer_id = ?
            ");
            $updateBooking->execute([
                $transaction_uuid,
                $transaction_uuid,
                $booking['booking_id'],
                currentUserId()
            ]);

            // Update seat status
            $updateSeat = $pdo->prepare("
                UPDATE seats 
                SET 
                    status = 'booked',
                    reserved_until = NULL,
                    reserved_by_customer_id = NULL
                WHERE showtime_id = ?
                AND seat_label = ?
            ");
            $updateSeat->execute([
                $booking['showtime_id'],
                $booking['seat_label']
            ]);

            // Update payment status
            $updatePayment = $pdo->prepare("
                UPDATE payments
                SET 
                    payment_status = 'Completed',
                    transaction_uuid = ?,
                    transaction_code = ?
                WHERE booking_id = ?
            ");
            $updatePayment->execute([
                $transaction_uuid,
                $transaction_code,
                $booking['booking_id']
            ]);
        }

        $pdo->commit();

        // Refresh main booking after update
        $stmt->execute([$booking_id, currentUserId()]);
        $mainBooking = $stmt->fetch();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        die('Update failed: ' . $e->getMessage());
    }
}

// Fetch all confirmed seats for this booking/payment
$seatStmt = $pdo->prepare("
    SELECT seat_label, amount 
    FROM bookings 
    WHERE customer_id = ?
    AND status = 'Confirmed'
    AND (
        payment_ref = ?
        OR transaction_uuid = ?
        OR booking_id = ?
    )
");
$seatStmt->execute([
    currentUserId(),
    $transaction_uuid,
    $transaction_uuid,
    $booking_id
]);
$allSeats = $seatStmt->fetchAll();

// Fallback: if allSeats is still empty, show current booking seat
if (!$allSeats && $mainBooking['status'] === 'Confirmed') {
    $allSeats = [
        [
            'seat_label' => $mainBooking['seat_label'],
            'amount' => $mainBooking['amount']
        ]
    ];
}

$page_title = 'Booking Success';
include 'includes/header.php';
?>

<div class="container" style="padding: var(--spacing-3xl) 0;">
    <div style="max-width: 800px; margin: 0 auto;">
        
        <div style="text-align: center; margin-bottom: var(--spacing-2xl);">
            <div style="background: var(--success); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--spacing-lg); color: white; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);">
                <svg style="width: 40px; height: 40px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <?php if ($mainBooking['status'] === 'Confirmed'): ?>
                <h1 style="font-size: var(--font-size-3xl); font-weight: 800; margin-bottom: var(--spacing-sm);">
                    Booking Confirmed!
                </h1>
                <p class="text-muted">Your ticket has been booked successfully. Enjoy your movie!</p>
            <?php else: ?>
                <h1 style="font-size: var(--font-size-3xl); font-weight: 800; margin-bottom: var(--spacing-sm);">
                    Booking Not Confirmed
                </h1>
                <p class="text-muted">Your booking status is still <?= htmlspecialchars($mainBooking['status']) ?>.</p>
            <?php endif; ?>
        </div>

        <!-- Ticket Visual -->
        <div class="ticket">
            <div class="ticket-main">
                <div class="ticket-movie-info">
                    <img src="<?= getMoviePoster($mainBooking['poster']) ?>" alt="<?= htmlspecialchars($mainBooking['title']) ?>" class="ticket-poster">
                    <div>
                        <div class="ticket-badge">E-TICKET</div>
                        <h2 class="ticket-title"><?= htmlspecialchars($mainBooking['title']) ?></h2>
                        <div class="ticket-meta">
                            <span><?= htmlspecialchars($mainBooking['language']) ?></span> • 
                            <span><?= htmlspecialchars($mainBooking['duration']) ?> Min</span>
                        </div>
                    </div>
                </div>

                <div class="ticket-grid">
                    <div class="ticket-item">
                        <div class="ticket-label">DATE</div>
                        <div class="ticket-value"><?= date('D, M d, Y', strtotime($mainBooking['show_date'])) ?></div>
                    </div>
                    <div class="ticket-item">
                        <div class="ticket-label">TIME</div>
                        <div class="ticket-value"><?= date('h:i A', strtotime($mainBooking['show_time'])) ?></div>
                    </div>
                    <div class="ticket-item">
                        <div class="ticket-label">SCREEN</div>
                        <div class="ticket-value"><?= htmlspecialchars($mainBooking['screen']) ?></div>
                    </div>
                    <div class="ticket-item">
                        <div class="ticket-label">SEATS</div>
                        <div class="ticket-value">
                            <?= htmlspecialchars(implode(', ', array_column($allSeats, 'seat_label'))) ?>
                        </div>
                    </div>
                    <div class="ticket-item">
                        <div class="ticket-label">STATUS</div>
                        <div class="ticket-value">
                            <?= htmlspecialchars($mainBooking['status']) ?>
                        </div>
                    </div>
                    <div class="ticket-item">
                        <div class="ticket-label">PAYMENT REF</div>
                        <div class="ticket-value" style="font-size: 12px;">
                            <?= htmlspecialchars($transaction_uuid ?? $mainBooking['payment_ref'] ?? 'N/A') ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ticket-side">
                <div style="text-align: center;">
                    <div class="ticket-label">ORDER ID</div>
                    <div class="ticket-value" style="font-size: var(--font-size-sm);">
                        #TK-<?= str_pad($booking_id, 6, '0', STR_PAD_LEFT) ?>
                    </div>
                </div>
                <div class="ticket-price">
                    NPR <?= number_format(array_sum(array_column($allSeats, 'amount')), 2) ?>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: var(--spacing-md); justify-content: center; margin-top: var(--spacing-2xl);">
            <a href="dashboard.php" class="btn btn-outline">My Bookings</a>
            <button onclick="window.print()" class="btn btn-primary">
                <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Print Ticket
            </button>
            <a href="index.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>
</div>

<style>
.ticket {
    background: white;
    display: flex;
    border-radius: var(--radius-2xl);
    overflow: hidden;
    box-shadow: var(--shadow-xl);
    border: 1px solid var(--gray-200);
}

.ticket-main {
    flex: 1;
    padding: var(--spacing-2xl);
    border-right: 2px dashed var(--gray-200);
    position: relative;
}

.ticket-main::before, .ticket-main::after {
    content: '';
    position: absolute;
    right: -10px;
    width: 20px;
    height: 20px;
    background: var(--gray-100);
    border-radius: 50%;
}

.ticket-main::before { 
    top: -10px; 
}

.ticket-main::after { 
    bottom: -10px; 
}

.ticket-side {
    width: 220px;
    padding: var(--spacing-2xl);
    background: var(--gray-50);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.ticket-movie-info {
    display: flex;
    gap: var(--spacing-xl);
    margin-bottom: var(--spacing-2xl);
}

.ticket-poster {
    width: 100px;
    height: 140px;
    object-fit: cover;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-md);
}

.ticket-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: var(--primary-100);
    color: var(--primary-700);
    border-radius: var(--radius-sm);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.1em;
    margin-bottom: var(--spacing-sm);
}

.ticket-title {
    font-size: var(--font-size-2xl);
    font-weight: 800;
    margin-bottom: var(--spacing-xs);
    color: var(--gray-900);
}

.ticket-meta {
    color: var(--gray-500);
    font-size: var(--font-size-sm);
    font-weight: 500;
}

.ticket-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--spacing-xl);
}

.ticket-label {
    font-size: 10px;
    font-weight: 700;
    color: var(--gray-400);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 4px;
}

.ticket-value {
    font-weight: 700;
    color: var(--gray-800);
}

.ticket-price {
    margin-top: auto;
    font-size: var(--font-size-xl);
    font-weight: 800;
    color: var(--primary-600);
}

@media (max-width: 768px) {
    .ticket { 
        flex-direction: column; 
    }

    .ticket-main { 
        border-right: none; 
        border-bottom: 2px dashed var(--gray-200); 
    }

    .ticket-main::before, 
    .ticket-main::after { 
        display: none; 
    }

    .ticket-side { 
        width: 100%; 
    }
}

@media print {
    .header, .btn, .nav, footer { 
        display: none !important; 
    }

    body { 
        background: white; 
    }

    .container { 
        padding: 0; 
    }

    .ticket { 
        box-shadow: none; 
        border: 1px solid #eee; 
    }
}
</style>

<?php include 'includes/footer.php'; ?>