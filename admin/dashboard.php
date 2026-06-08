<?php
$page_title = 'Dashboard';
require_once 'includes/admin_header.php';

$movieCount = $pdo->query("SELECT COUNT(*) FROM movies")->fetchColumn();
$showCount = $pdo->query("SELECT COUNT(*) FROM showtimes WHERE show_date >= CURDATE()")->fetchColumn();
$bookingCount = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'Confirmed'")->fetchColumn();
$revenue = $pdo->query("SELECT SUM(amount) FROM bookings WHERE status = 'Confirmed' AND payment_status = 'paid'")->fetchColumn();

$recentBookings = $pdo->query("
    SELECT b.*, c.name, m.title 
    FROM bookings b
    JOIN customers c ON b.customer_id = c.customer_id
    JOIN showtimes s ON b.showtime_id = s.showtime_id
    JOIN movies m ON s.movie_id = m.movie_id
    ORDER BY b.created_at DESC LIMIT 10
")->fetchAll();
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Movies</div>
        <div class="stat-value"><?= $movieCount ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Upcoming Shows</div>
        <div class="stat-value"><?= $showCount ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Bookings</div>
        <div class="stat-value"><?= $bookingCount ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Revenue</div>
        <div class="stat-value">NPR <?= number_format($revenue ?? 0, 2) ?></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Quick Actions</h3>
    </div>
    <div class="card-body">
        <div class="btn-group">
            <a href="add_movie.php" class="btn btn-primary">Add Movie</a>
            <a href="add_showtime.php" class="btn btn-primary">Add Showtime</a>
            <a href="movies.php" class="btn btn-secondary">Manage Movies</a>
            <a href="bookings.php" class="btn btn-secondary">View Bookings</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Recent Bookings</h3>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table>
                <thead>
                    <tr><th>ID</th><th>Customer</th><th>Movie</th><th>Seat</th><th>Amount</th><th>Status</th><th>Date</th></tr>
                </thead>
                <tbody>
                    <?php foreach($recentBookings as $booking): ?>
                    <tr>
                        <td>#<?= $booking['booking_id'] ?></td>
                        <td><?= htmlspecialchars($booking['name']) ?></td>
                        <td><?= htmlspecialchars($booking['title']) ?></td>
                        <td><?= $booking['seat_label'] ?></td>
                        <td>NPR <?= number_format($booking['amount'], 2) ?></td>
                        <td><span class="badge <?= $booking['status'] == 'Confirmed' ? 'badge-success' : 'badge-warning' ?>"><?= $booking['status'] ?></span></td>
                        <td><?= date('M d, H:i', strtotime($booking['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>