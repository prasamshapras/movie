<?php
require '../includes/config.php';

date_default_timezone_set('Asia/Kathmandu');

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Please login first.'
    ]);
    exit;
}

if (isAdminLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Admin cannot book seats.'
    ]);
    exit;
}

$showtime_id = intval($_POST['showtime_id'] ?? 0);
$seat_label = trim($_POST['seat_label'] ?? '');
$customer_id = currentUserId();

$today = date('Y-m-d');
$currentTime = date('H:i:s');

if (!$showtime_id || !$seat_label) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid seat request.'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    $showCheck = $pdo->prepare("
        SELECT showtime_id
        FROM showtimes
        WHERE showtime_id = ?
        AND (
            show_date > ?
            OR (
                show_date = ?
                AND show_time > ?
            )
        )
        FOR UPDATE
    ");
    $showCheck->execute([$showtime_id, $today, $today, $currentTime]);

    if (!$showCheck->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception('This showtime has already passed.');
    }

    $pdo->prepare("
        UPDATE seats
        SET status = 'available',
            reserved_until = NULL,
            reserved_by_customer_id = NULL
        WHERE status = 'reserved'
        AND reserved_until IS NOT NULL
        AND reserved_until < NOW()
    ")->execute();

    $seatCheck = $pdo->prepare("
        SELECT status, reserved_by_customer_id
        FROM seats
        WHERE showtime_id = ?
        AND seat_label = ?
        FOR UPDATE
    ");
    $seatCheck->execute([$showtime_id, $seat_label]);
    $seat = $seatCheck->fetch(PDO::FETCH_ASSOC);

    if (!$seat) {
        throw new Exception('Seat not found.');
    }

    if ($seat['status'] === 'booked') {
        throw new Exception('This seat is already booked.');
    }

    if (
        $seat['status'] === 'reserved' &&
        intval($seat['reserved_by_customer_id']) !== intval($customer_id)
    ) {
        throw new Exception('This seat is reserved by another user.');
    }

    $reserve = $pdo->prepare("
        UPDATE seats
        SET status = 'reserved',
            reserved_until = DATE_ADD(NOW(), INTERVAL 2 MINUTE),
            reserved_by_customer_id = ?
        WHERE showtime_id = ?
        AND seat_label = ?
    ");
    $reserve->execute([$customer_id, $showtime_id, $seat_label]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Seat reserved.'
    ]);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}