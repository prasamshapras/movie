<?php
require '../includes/config.php';

date_default_timezone_set('Asia/Kathmandu');

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false]);
    exit;
}

if (isAdminLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Admin cannot manage reservations.'
    ]);
    exit;
}

$showtime_id = intval($_POST['showtime_id'] ?? 0);
$seat_label = trim($_POST['seat_label'] ?? '');
$customer_id = currentUserId();

if (!$showtime_id || !$seat_label) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $pdo->prepare("
    UPDATE seats
    SET status = 'available',
        reserved_until = NULL,
        reserved_by_customer_id = NULL
    WHERE showtime_id = ?
    AND seat_label = ?
    AND status = 'reserved'
    AND reserved_by_customer_id = ?
");
$stmt->execute([$showtime_id, $seat_label, $customer_id]);

echo json_encode(['success' => true]);