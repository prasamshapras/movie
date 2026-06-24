<?php
$page_title = 'Dashboard';

require_once 'includes/admin_header.php';

date_default_timezone_set('Asia/Kathmandu');

/*
|--------------------------------------------------------------------------
| Dashboard Counts
|--------------------------------------------------------------------------
*/

$totalMovies = $pdo->query("
    SELECT COUNT(*)
    FROM movies
")->fetchColumn();

$nowShowingCount = $pdo->query("
    SELECT COUNT(*)
    FROM movies
    WHERE release_date <= CURDATE()
")->fetchColumn();

$upcomingMoviesCount = $pdo->query("
    SELECT COUNT(*)
    FROM movies
    WHERE release_date > CURDATE()
")->fetchColumn();

$bookingCount = $pdo->query("
    SELECT COUNT(*)
    FROM bookings
    WHERE status = 'Confirmed'
")->fetchColumn();

$revenue = $pdo->query("
    SELECT COALESCE(SUM(amount), 0)
    FROM bookings
    WHERE status = 'Confirmed'
    AND LOWER(payment_status) = 'paid'
")->fetchColumn();

$totalShowtimes = $pdo->query("
    SELECT COUNT(*)
    FROM showtimes
")->fetchColumn();

$upcomingShowtimes = $pdo->query("
    SELECT COUNT(*)
    FROM showtimes
    WHERE show_date > CURDATE()
    OR (
        show_date = CURDATE()
        AND show_time > CURTIME()
    )
")->fetchColumn();

/*
|--------------------------------------------------------------------------
| Recent Bookings
|--------------------------------------------------------------------------
| Shows recent booking history regardless of show date.
| LEFT JOIN is used so booking remains visible even if movie/showtime removed.
*/

$recentBookings = $pdo->query("
    SELECT 
        b.booking_id,
        b.customer_id,
        b.movie_id,
        b.showtime_id,
        b.seat_label,
        b.amount,
        b.status,
        b.payment_status,
        b.created_at,
        b.transaction_uuid,

        COALESCE(c.name, 'Unknown Customer') AS customer_name,
        COALESCE(c.email, 'No email') AS customer_email,

        COALESCE(m.title, 'Movie Removed') AS movie_title,

        s.show_date,
        s.show_time,
        s.screen
    FROM bookings b
    LEFT JOIN customers c ON b.customer_id = c.customer_id
    LEFT JOIN movies m ON b.movie_id = m.movie_id
    LEFT JOIN showtimes s ON b.showtime_id = s.showtime_id
    ORDER BY b.created_at DESC, b.booking_id DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .badge-danger {
        background: #fee2e2;
        color: #991b1b;
    }

    .badge-muted {
        background: #e2e8f0;
        color: #475569;
    }
</style>

<div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <div class="stat-label" style="font-size: 0.875rem; color: #64748b; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">
            Now Showing
        </div>
        <div class="stat-value" style="font-size: 1.875rem; font-weight: 700; color: #10b981;">
            <?= intval($nowShowingCount) ?>
        </div>
        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">
            Released Movies
        </div>
    </div>

    <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <div class="stat-label" style="font-size: 0.875rem; color: #64748b; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">
            Upcoming Movies
        </div>
        <div class="stat-value" style="font-size: 1.875rem; font-weight: 700; color: #f59e0b;">
            <?= intval($upcomingMoviesCount) ?>
        </div>
        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">
            Future Releases
        </div>
    </div>

    <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <div class="stat-label" style="font-size: 0.875rem; color: #64748b; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">
            Total Bookings
        </div>
        <div class="stat-value" style="font-size: 1.875rem; font-weight: 700; color: #3b82f6;">
            <?= intval($bookingCount) ?>
        </div>
        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">
            Confirmed Tickets
        </div>
    </div>

    <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <div class="stat-label" style="font-size: 0.875rem; color: #64748b; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">
            Total Revenue
        </div>
        <div class="stat-value" style="font-size: 1.875rem; font-weight: 700; color: #1e293b;">
            NPR <?= number_format($revenue ?? 0, 0) ?>
        </div>
        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">
            From confirmed payments
        </div>
    </div>

    <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <div class="stat-label" style="font-size: 0.875rem; color: #64748b; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">
            Total Showtimes
        </div>
        <div class="stat-value" style="font-size: 1.875rem; font-weight: 700; color: #4f46e5;">
            <?= intval($totalShowtimes) ?>
        </div>
        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">
            All showtime records
        </div>
    </div>

    <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <div class="stat-label" style="font-size: 0.875rem; color: #64748b; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">
            Upcoming Showtimes
        </div>
        <div class="stat-value" style="font-size: 1.875rem; font-weight: 700; color: #16a34a;">
            <?= intval($upcomingShowtimes) ?>
        </div>
        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">
            Available for booking
        </div>
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
            <a href="showtimes.php" class="btn btn-secondary">Manage Showtimes</a>
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
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Movie</th>
                        <th>Show</th>
                        <th>Seat</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Booked Date</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($recentBookings)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:2rem; color:#64748b;">
                                No recent bookings found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentBookings as $booking): ?>
                            <?php
                            if ($booking['status'] === 'Confirmed') {
                                $badgeClass = 'badge-success';
                            } elseif ($booking['status'] === 'Pending') {
                                $badgeClass = 'badge-warning';
                            } elseif ($booking['status'] === 'Cancelled') {
                                $badgeClass = 'badge-danger';
                            } else {
                                $badgeClass = 'badge-muted';
                            }
                            ?>

                            <tr>
                                <td>#<?= intval($booking['booking_id']) ?></td>

                                <td>
                                    <strong><?= htmlspecialchars($booking['customer_name']) ?></strong>
                                    <br>
                                    <small style="color:#64748b;">
                                        <?= htmlspecialchars($booking['customer_email']) ?>
                                    </small>
                                </td>

                                <td><?= htmlspecialchars($booking['movie_title']) ?></td>

                                <td>
                                    <?php if (!empty($booking['show_date']) && !empty($booking['show_time'])): ?>
                                        <?= date('M d, Y', strtotime($booking['show_date'])) ?>
                                        <br>
                                        <small style="color:#64748b;">
                                            <?= date('h:i A', strtotime($booking['show_time'])) ?>
                                            <?= !empty($booking['screen']) ? ' - ' . htmlspecialchars($booking['screen']) : '' ?>
                                        </small>
                                    <?php else: ?>
                                        <span style="color:#64748b;">Showtime removed</span>
                                    <?php endif; ?>
                                </td>

                                <td><?= htmlspecialchars($booking['seat_label']) ?></td>

                                <td>NPR <?= number_format((float)$booking['amount'], 2) ?></td>

                                <td>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= htmlspecialchars($booking['status']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= !empty($booking['created_at']) ? date('M d, H:i', strtotime($booking['created_at'])) : 'N/A' ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>