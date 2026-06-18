<?php
$page_title = 'Dashboard';
require_once 'includes/admin_header.php';

$totalMovies = $pdo->query("SELECT COUNT(*) FROM movies")->fetchColumn();
$nowShowingCount = $pdo->query("SELECT COUNT(*) FROM movies WHERE release_date <= CURDATE()")->fetchColumn();
$upcomingMoviesCount = $pdo->query("SELECT COUNT(*) FROM movies WHERE release_date > CURDATE()")->fetchColumn();
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

<div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <div class="stat-label" style="font-size: 0.875rem; color: #64748b; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">Now Showing</div>
        <div class="stat-value" style="font-size: 1.875rem; font-weight: 700; color: #10b981;"><?= $nowShowingCount ?></div>
        <div style="font-size: 0.75rem; color: #94a3b8; mt: 0.25rem;">Released Movies</div>
    </div>
    <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <div class="stat-label" style="font-size: 0.875rem; color: #64748b; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">Upcoming Movies</div>
        <div class="stat-value" style="font-size: 1.875rem; font-weight: 700; color: #f59e0b;"><?= $upcomingMoviesCount ?></div>
        <div style="font-size: 0.75rem; color: #94a3b8; mt: 0.25rem;">Future Releases</div>
    </div>
    <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <div class="stat-label" style="font-size: 0.875rem; color: #64748b; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">Total Bookings</div>
        <div class="stat-value" style="font-size: 1.875rem; font-weight: 700; color: #3b82f6;"><?= $bookingCount ?></div>
        <div style="font-size: 0.75rem; color: #94a3b8; mt: 0.25rem;">Confirmed Tickets</div>
    </div>
    <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <div class="stat-label" style="font-size: 0.875rem; color: #64748b; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">Total Revenue</div>
        <div class="stat-value" style="font-size: 1.875rem; font-weight: 700; color: #1e293b;">NPR <?= number_format($revenue ?? 0, 0) ?></div>
        <div style="font-size: 0.75rem; color: #94a3b8; mt: 0.25rem;">From Confirmed Payments</div>
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