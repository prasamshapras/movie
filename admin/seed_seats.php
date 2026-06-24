<?php
require_once '../includes/config.php';

date_default_timezone_set('Asia/Kathmandu');

if (!isAdminLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$showtime_id = intval($_GET['showtime_id'] ?? 0);

if (!$showtime_id) {
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT s.*, m.title
    FROM showtimes s
    JOIN movies m ON s.movie_id = m.movie_id
    WHERE s.showtime_id = ?
");
$stmt->execute([$showtime_id]);
$showtime = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$showtime) {
    die('Showtime not found.');
}

try {
    $pdo->beginTransaction();

    $checkSeats = $pdo->prepare("SELECT COUNT(*) FROM seats WHERE showtime_id = ?");
    $checkSeats->execute([$showtime_id]);
    $seatCount = $checkSeats->fetchColumn();

    if ($seatCount == 0) {
        $rows = ['A', 'B', 'C', 'D'];
        $perRow = 10;

        $ins = $pdo->prepare("
            INSERT INTO seats (showtime_id, seat_label, status)
            VALUES (?, ?, 'available')
        ");

        foreach ($rows as $r) {
            for ($i = 1; $i <= $perRow; $i++) {
                $ins->execute([$showtime_id, $r . $i]);
            }
        }
    }

    $pdo->commit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die('Seat generation failed: ' . htmlspecialchars($e->getMessage()));
}

$page_title = 'Seats Generated';
require_once 'includes/admin_header.php';
?>

<div style="max-width: 700px; margin: 3rem auto; text-align: center;">
    <div class="card" style="padding: 3rem;">
        <h2>Seats Ready</h2>

        <p style="color: #64748b; margin-top: 1rem;">
            Seats have been generated for:
        </p>

        <h3 style="margin-top: 1rem;">
            <?= htmlspecialchars($showtime['title']) ?>
        </h3>

        <p style="color: #64748b;">
            <?= date('F d, Y', strtotime($showtime['show_date'])) ?> at 
            <?= date('h:i A', strtotime($showtime['show_time'])) ?>
        </p>

        <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: center;">
            <a href="../movie.php?id=<?= $showtime['movie_id'] ?>" class="btn btn-primary">View Movie Page</a>
            <a href="dashboard.php" class="btn btn-outline">Go to Dashboard</a>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>