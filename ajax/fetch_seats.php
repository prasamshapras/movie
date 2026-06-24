<?php
require '../includes/config.php';

date_default_timezone_set('Asia/Kathmandu');

header('Content-Type: application/json');

$showtime_id = intval($_GET['showtime_id'] ?? 0);

if (!$showtime_id) {
    echo json_encode([]);
    exit;
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

$stmt = $pdo->prepare("
    SELECT 
        seat_label, 
        status, 
        reserved_by_customer_id,
        CASE 
            WHEN reserved_until IS NULL THEN 0
            ELSE TIMESTAMPDIFF(SECOND, NOW(), reserved_until)
        END AS seconds_left
    FROM seats
    WHERE showtime_id = ?
    ORDER BY LEFT(seat_label, 1), CAST(SUBSTRING(seat_label, 2) AS UNSIGNED)
");
$stmt->execute([$showtime_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));