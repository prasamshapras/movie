<?php
require '../includes/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if (isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Admin cannot book tickets.']);
    exit;
}

$showtime_id = intval($_POST['showtime_id'] ?? 0);
$seat_label = trim($_POST['seat_label'] ?? '');
$customer_id = currentUserId();

if (!$showtime_id || !$seat_label) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $pdo->beginTransaction();

    $pdo->prepare("
        UPDATE seats
        SET status='available',
            reserved_until=NULL,
            reserved_by_customer_id=NULL
        WHERE status='reserved'
        AND reserved_until IS NOT NULL
        AND reserved_until < NOW()
    ")->execute();

    $stmt = $pdo->prepare("
        SELECT status, reserved_by_customer_id
        FROM seats
        WHERE showtime_id = ?
        AND seat_label = ?
        FOR UPDATE
    ");
    $stmt->execute([$showtime_id, $seat_label]);
    $seat = $stmt->fetch();

    if (!$seat) {
        throw new Exception('Seat not found');
    }

    if ($seat['status'] === 'booked') {
        throw new Exception('Seat already booked');
    }

    if ($seat['status'] === 'reserved' && intval($seat['reserved_by_customer_id']) !== intval($customer_id)) {
        throw new Exception('Seat reserved by another user');
    }

    $update = $pdo->prepare("
        UPDATE seats
        SET status='reserved',
            reserved_until=DATE_ADD(NOW(), INTERVAL 2 MINUTE),
            reserved_by_customer_id=?
        WHERE showtime_id=?
        AND seat_label=?
    ");
    $update->execute([$customer_id, $showtime_id, $seat_label]);

    $pdo->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}