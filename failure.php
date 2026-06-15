<?php
require 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$booking_id = intval($_GET['booking_id'] ?? 0);

if (!$booking_id) {
    die('Invalid booking.');
}

$stmt = $pdo->prepare("
    SELECT 
        b.*,
        s.movie_id
    FROM bookings b
    JOIN showtimes s ON b.showtime_id = s.showtime_id
    WHERE b.booking_id = ?
    AND b.customer_id = ?
");
$stmt->execute([$booking_id, currentUserId()]);
$mainBooking = $stmt->fetch();

if (!$mainBooking) {
    die('Booking not found.');
}

try {
    $pdo->beginTransaction();

    $allStmt = $pdo->prepare("
        SELECT *
        FROM bookings
        WHERE customer_id = ?
        AND showtime_id = ?
        AND status = 'Pending'
        AND payment_status = 'Unpaid'
    ");
    $allStmt->execute([currentUserId(), $mainBooking['showtime_id']]);
    $bookings = $allStmt->fetchAll();

    if (!$bookings) {
        $bookings = [$mainBooking];
    }

    foreach ($bookings as $booking) {
        $pdo->prepare("
            UPDATE bookings
            SET status = 'Cancelled',
                payment_status = 'failed'
            WHERE booking_id = ?
            AND customer_id = ?
        ")->execute([$booking['booking_id'], currentUserId()]);

        $pdo->prepare("
            UPDATE seats
            SET status = 'reserved',
                reserved_until = DATE_ADD(NOW(), INTERVAL 2 MINUTE),
                reserved_by_customer_id = ?
            WHERE showtime_id = ?
            AND seat_label = ?
            AND status != 'booked'
        ")->execute([
            currentUserId(),
            $booking['showtime_id'],
            $booking['seat_label']
        ]);
    }

    $pdo->prepare("
        UPDATE payments
        SET payment_status = 'Failed'
        WHERE booking_id = ?
    ")->execute([$booking_id]);

    $pdo->commit();

    } catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die('Cancel failed: ' . $e->getMessage());
    }

    // Redirect back to the movie selection/seat selection page
    header(
    "Location: movie.php?id=" .
    $mainBooking['movie_id'] .
    "&showtime=" .
    $mainBooking['showtime_id']
    );
    exit;