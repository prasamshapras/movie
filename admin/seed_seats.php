<?php
require_once '../includes/config.php';

if (!isAdminLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$showtime_id = intval($_GET['showtime_id'] ?? 0);
if (!$showtime_id) {
    header('Location: dashboard.php');
    exit;
}

// Fetch showtime details for the UI
$stmt = $pdo->prepare("
    SELECT s.*, m.title 
    FROM showtimes s 
    JOIN movies m ON s.movie_id = m.movie_id 
    WHERE s.showtime_id = ?
");
$stmt->execute([$showtime_id]);
$showtime = $stmt->fetch();

if (!$showtime) {
    header('Location: dashboard.php');
    exit;
}

$success = false;
// ALWAYS clear existing seats before seeding to ensure fresh start and no duplicates
$pdo->prepare("DELETE FROM seats WHERE showtime_id = ?")->execute([$showtime_id]);

$rows = ['A', 'B', 'C', 'D']; 
$perRow = 10;
$ins = $pdo->prepare("INSERT INTO seats (showtime_id, seat_label, status) VALUES (?, ?, 'available')");

$pdo->beginTransaction();
try {
    foreach ($rows as $r) {
        for ($i = 1; $i <= $perRow; $i++) {
            $label = $r . $i;
            $ins->execute([$showtime_id, $label]);
        }
    }
    $pdo->commit();
    $success = true;
} catch (Exception $e) {
    $pdo->rollBack();
    die("Error seeding seats: " . $e->getMessage());
}

$page_title = 'Seed Seats';
require_once 'includes/admin_header.php';
?>

<style>
    .seed-container {
        max-width: 800px;
        margin: 2rem auto;
        text-align: center;
    }
    .success-card {
        background: white;
        border-radius: 1.5rem;
        padding: 3rem;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        border: 1px solid #e2e8f0;
    }
    .status-icon {
        width: 80px;
        height: 80px;
        background: #ecfdf5;
        color: #10b981;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 2rem;
    }
    .showtime-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: #f1f5f9;
        color: #475569;
        border-radius: 2rem;
        font-size: 0.875rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
    }
    .seat-preview {
        display: grid;
        grid-template-columns: repeat(10, 1fr);
        gap: 0.5rem;
        max-width: 300px;
        margin: 2rem auto;
        padding: 1.5rem;
        background: #f8fafc;
        border-radius: 1rem;
        border: 1px dashed #cbd5e1;
    }
    .seat-dot {
        width: 12px;
        height: 12px;
        background: #10b981;
        border-radius: 3px;
        opacity: 0.6;
    }
    .btn-group-center {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-top: 2.5rem;
    }
    .stats-row {
        display: flex;
        justify-content: center;
        gap: 3rem;
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid #f1f5f9;
    }
    .stat-item h4 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.25rem;
    }
    .stat-item p {
        font-size: 0.875rem;
        color: #64748b;
    }
</style>

<div class="seed-container">
    <div class="success-card">
        <div class="status-icon">
            <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <div class="showtime-badge">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
            </svg>
            <?= htmlspecialchars($showtime['title']) ?>
        </div>

        <h2 style="font-size: 1.75rem; font-weight: 800; color: #0f172a; margin-bottom: 1rem;">
            <?= $success ? 'Seats Successfully Generated!' : 'Seats Already Generated' ?>
        </h2>
        
        <p style="color: #64748b; font-size: 1.125rem; max-width: 500px; margin: 0 auto;">
            The seating arrangement for this showtime has been configured and is now ready for bookings.
        </p>

        <div class="seat-preview">
            <?php for($i=0; $i<40; $i++): ?>
                <div class="seat-dot"></div>
            <?php endfor; ?>
        </div>

        <div class="stats-row">
            <div class="stat-item">
                <h4>40</h4>
                <p>Total Seats</p>
            </div>
            <div class="stat-item">
                <h4>4</h4>
                <p>Rows</p>
            </div>
            <div class="stat-item">
                <h4>10</h4>
                <p>Cols</p>
            </div>
        </div>

        <div class="btn-group-center">
            <a href="dashboard.php" class="btn btn-primary" style="padding: 0.75rem 2rem;">
                Go to Dashboard
            </a>
            <a href="movies.php" class="btn btn-outline" style="padding: 0.75rem 2rem;">
                Manage Movies
            </a>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
