<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$movie_id = intval($_POST['movie_id'] ?? 0);
$showtime_id = intval($_POST['showtime_id'] ?? 0);
$selected = trim($_POST['selected_seats'] ?? '');

if (!isLoggedIn()) {
    $_SESSION['after_login_redirect'] = "payment.php?id=" . $movie_id . "&showtime=" . $showtime_id;
    header('Location: ../login.php');
    exit;
}

if (!$movie_id || !$showtime_id || !$selected) {
    die('No seats selected.');
}

$customer_id = currentUserId();

$seatLabels = array_values(array_unique(array_filter(array_map('trim', explode(',', $selected)))));

if (empty($seatLabels)) {
    die('No valid seats selected.');
}

$priceStmt = $pdo->prepare("
    SELECT base_price 
    FROM showtimes 
    WHERE showtime_id = ? 
    AND movie_id = ?
");
$priceStmt->execute([$showtime_id, $movie_id]);
$base_price = $priceStmt->fetchColumn();

if (!$base_price) {
    die('Invalid showtime.');
}

$total = count($seatLabels) * $base_price;

try {
    $pdo->beginTransaction();

    // Release expired reservations first
    $pdo->prepare("
        UPDATE seats
        SET status = 'available',
            reserved_until = NULL,
            reserved_by_customer_id = NULL
        WHERE status = 'reserved'
        AND reserved_until IS NOT NULL
        AND reserved_until < NOW()
    ")->execute();

    $checkSeat = $pdo->prepare("
        SELECT status, reserved_by_customer_id
        FROM seats
        WHERE showtime_id = ?
        AND seat_label = ?
        FOR UPDATE
    ");

    $reserveSeat = $pdo->prepare("
        UPDATE seats
        SET status = 'reserved',
            reserved_until = DATE_ADD(NOW(), INTERVAL 2 MINUTE),
            reserved_by_customer_id = ?
        WHERE showtime_id = ?
        AND seat_label = ?
    ");

    foreach ($seatLabels as $seat) {
        $checkSeat->execute([$showtime_id, $seat]);
        $row = $checkSeat->fetch();

        if (!$row) {
            throw new Exception("Seat $seat does not exist.");
        }

        if ($row['status'] === 'booked') {
            throw new Exception("Seat $seat is already booked.");
        }

        // If another user reserved it, block booking
        if (
            $row['status'] === 'reserved' &&
            intval($row['reserved_by_customer_id']) !== intval($customer_id)
        ) {
            throw new Exception("Seat $seat is reserved by another user.");
        }

        // If available, reserve it for current user
        if ($row['status'] === 'available') {
            $reserveSeat->execute([$customer_id, $showtime_id, $seat]);
        }

        // If reserved by same user, allow it to proceed
    }

    $insertBooking = $pdo->prepare("
        INSERT INTO bookings
        (customer_id, showtime_id, seat_label, amount, status, payment_status, movie_id)
        VALUES (?, ?, ?, ?, 'Pending', 'Unpaid', ?)
    ");

    $bookingIds = [];

    foreach ($seatLabels as $seat) {
        $insertBooking->execute([
            $customer_id,
            $showtime_id,
            $seat,
            $base_price,
            $movie_id
        ]);

        $bookingIds[] = $pdo->lastInsertId();
    }

    if (empty($bookingIds)) {
        throw new Exception('Booking could not be created.');
    }

    $firstBookingId = $bookingIds[0];

    $insertPayment = $pdo->prepare("
        INSERT INTO payments
        (booking_id, amount, payment_method, payment_status)
        VALUES (?, ?, 'eSewa', 'Pending')
    ");
    $insertPayment->execute([$firstBookingId, $total]);

    $pdo->commit();

    header("Location: ../payment.php?booking_id=" . $firstBookingId);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "<h3>Booking failed</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<a href='javascript:history.back()'>Go Back</a>";
    exit;
}