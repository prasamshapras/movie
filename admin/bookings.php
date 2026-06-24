<?php
$page_title = 'Manage Bookings';

require_once '../includes/config.php';
require_once '../includes/automation.php';

date_default_timezone_set('Asia/Kathmandu');

if (!isAdminLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Safe cleanup only
|--------------------------------------------------------------------------
| This will only release expired reserved seats.
| It will NOT delete past bookings.
*/

cleanupPastShows($pdo);

/*
|--------------------------------------------------------------------------
| Cancel Booking
|--------------------------------------------------------------------------
*/

if (isset($_GET['cancel_id'])) {
    $id = intval($_GET['cancel_id']);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT booking_id, showtime_id, seat_label, status
            FROM bookings
            WHERE booking_id = ?
            FOR UPDATE
        ");
        $stmt->execute([$id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($booking) {
            if ($booking['status'] !== 'Cancelled') {
                $pdo->prepare("
                    UPDATE bookings
                    SET status = 'Cancelled',
                        payment_status = 'Cancelled'
                    WHERE booking_id = ?
                ")->execute([$id]);

                /*
                |--------------------------------------------------------------------------
                | Free the seat only if it was not a past show issue
                |--------------------------------------------------------------------------
                | This still keeps booking history.
                */

                $pdo->prepare("
                    UPDATE seats
                    SET status = 'available',
                        reserved_until = NULL,
                        reserved_by_customer_id = NULL
                    WHERE showtime_id = ?
                    AND seat_label = ?
                    AND status != 'booked'
                ")->execute([
                    $booking['showtime_id'],
                    $booking['seat_label']
                ]);
            }
        }

        $pdo->commit();

        header('Location: bookings.php?msg=cancelled');
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        die('Cancel failed: ' . htmlspecialchars($e->getMessage()));
    }
}

/*
|--------------------------------------------------------------------------
| Delete Booking
|--------------------------------------------------------------------------
| This deletes only selected booking record.
| Use carefully. Cancel is better than delete for history.
*/

if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT booking_id, showtime_id, seat_label
            FROM bookings
            WHERE booking_id = ?
            FOR UPDATE
        ");
        $stmt->execute([$id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($booking) {
            $pdo->prepare("
                UPDATE seats
                SET status = 'available',
                    reserved_until = NULL,
                    reserved_by_customer_id = NULL
                WHERE showtime_id = ?
                AND seat_label = ?
                AND status != 'booked'
            ")->execute([
                $booking['showtime_id'],
                $booking['seat_label']
            ]);

            $pdo->prepare("
                DELETE FROM payments
                WHERE booking_id = ?
            ")->execute([$id]);

            $pdo->prepare("
                DELETE FROM bookings
                WHERE booking_id = ?
            ")->execute([$id]);
        }

        $pdo->commit();

        header('Location: bookings.php?msg=deleted');
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        die('Delete failed: ' . htmlspecialchars($e->getMessage()));
    }
}

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';
$filter_movie = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : 0;
$date_filter = isset($_GET['date_filter']) ? trim($_GET['date_filter']) : 'all';

$today = date('Y-m-d');
$currentTime = date('H:i:s');

/*
|--------------------------------------------------------------------------
| Fetch Bookings
|--------------------------------------------------------------------------
| Important:
| There is NO default today-only filter.
| This page shows all bookings: past, today, and upcoming.
*/

$sql = "
    SELECT 
        b.booking_id,
        b.customer_id,
        b.movie_id,
        b.showtime_id,
        b.seat_label AS selected_seats,
        b.amount,
        b.created_at,
        b.status,
        b.payment_status,
        b.payment_ref,
        b.transaction_uuid,

        COALESCE(m.title, 'Movie Removed') AS movie_title,

        COALESCE(c.name, 'Unknown Customer') AS customer_name,
        COALESCE(c.email, 'No email') AS customer_email,

        s.show_date,
        s.show_time,
        s.screen
    FROM bookings b
    LEFT JOIN movies m ON b.movie_id = m.movie_id
    LEFT JOIN customers c ON b.customer_id = c.customer_id
    LEFT JOIN showtimes s ON b.showtime_id = s.showtime_id
    WHERE 1=1
";

$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            c.name LIKE ?
            OR c.email LIKE ?
            OR CAST(b.booking_id AS CHAR) LIKE ?
            OR b.transaction_uuid LIKE ?
            OR b.payment_ref LIKE ?
            OR b.seat_label LIKE ?
            OR m.title LIKE ?
        )
    ";

    $like = "%$search%";

    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($filter_status !== '') {
    $sql .= " AND b.status = ?";
    $params[] = $filter_status;
}

if ($filter_movie > 0) {
    $sql .= " AND b.movie_id = ?";
    $params[] = $filter_movie;
}

if ($date_filter === 'today') {
    $sql .= " AND s.show_date = ?";
    $params[] = $today;
} elseif ($date_filter === 'past') {
    $sql .= "
        AND (
            s.show_date < ?
            OR (
                s.show_date = ?
                AND s.show_time <= ?
            )
        )
    ";
    $params[] = $today;
    $params[] = $today;
    $params[] = $currentTime;
} elseif ($date_filter === 'upcoming') {
    $sql .= "
        AND (
            s.show_date > ?
            OR (
                s.show_date = ?
                AND s.show_time > ?
            )
        )
    ";
    $params[] = $today;
    $params[] = $today;
    $params[] = $currentTime;
}

$sql .= " ORDER BY b.created_at DESC, b.booking_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Movies for Filter
|--------------------------------------------------------------------------
*/

$movies = $pdo->query("
    SELECT movie_id, title
    FROM movies
    ORDER BY title ASC
")->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Stats
|--------------------------------------------------------------------------
*/

$totalBookings = count($bookings);
$totalRevenue = 0;
$confirmedCount = 0;
$pendingCount = 0;
$cancelledCount = 0;

foreach ($bookings as $b) {
    if ($b['status'] === 'Confirmed') {
        $confirmedCount++;

        if (strtolower($b['payment_status'] ?? '') === 'paid') {
            $totalRevenue += floatval($b['amount']);
        }
    } elseif ($b['status'] === 'Pending') {
        $pendingCount++;
    } elseif ($b['status'] === 'Cancelled') {
        $cancelledCount++;
    }
}

require_once 'includes/admin_header.php';
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

    @media print {
        .admin-sidebar,
        .admin-header,
        .search-section,
        .btn-group,
        .card-header button {
            display: none !important;
        }

        .admin-main {
            margin-left: 0 !important;
        }

        .admin-content {
            padding: 0 !important;
        }

        .card {
            box-shadow: none !important;
            border: none !important;
        }

        .table-container {
            overflow: visible !important;
        }

        th {
            background: #eee !important;
            color: black !important;
        }
    }
</style>

<?php if (isset($_GET['msg'])): ?>
    <div style="padding: 1rem; margin-bottom: 1rem; border-radius: 0.5rem; background: #dcfce7; color: #166534; font-weight: 600;">
        <?php if ($_GET['msg'] === 'cancelled'): ?>
            Booking cancelled successfully.
        <?php elseif ($_GET['msg'] === 'deleted'): ?>
            Booking deleted successfully.
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Displayed Bookings</div>
        <div class="stat-value"><?= number_format($totalBookings) ?></div>
        <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;">
            Past, today and upcoming
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Displayed Revenue</div>
        <div class="stat-value" style="color: #10b981;">
            NPR <?= number_format($totalRevenue, 2) ?>
        </div>
        <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;">
            Confirmed and paid only
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Confirmed</div>
        <div class="stat-value" style="color: #3b82f6;">
            <?= number_format($confirmedCount) ?>
        </div>
        <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;">
            Successfully confirmed
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Pending / Cancelled</div>
        <div class="stat-value" style="color: #f59e0b;">
            <?= number_format($pendingCount) ?>
            <span style="font-size: 1rem; color: #ef4444;">
                / <?= number_format($cancelledCount) ?>
            </span>
        </div>
        <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;">
            Awaiting payment or cancelled
        </div>
    </div>
</div>

<div class="search-section">
    <div class="search-header">
        <h3>Filter Bookings</h3>
    </div>

    <div class="search-body">
        <form method="GET" class="search-form">
            <div class="search-group">
                <label class="search-label">Search</label>
                <input 
                    type="text" 
                    name="search" 
                    class="search-input" 
                    placeholder="Customer, email, movie, booking ID, seat, transaction..." 
                    value="<?= htmlspecialchars($search) ?>"
                >
            </div>

            <div class="search-group">
                <label class="search-label">Movie</label>
                <select name="movie_id" class="search-select">
                    <option value="">All Movies</option>
                    <?php foreach ($movies as $m): ?>
                        <option value="<?= $m['movie_id'] ?>" <?= $filter_movie == $m['movie_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="search-group">
                <label class="search-label">Date</label>
                <select name="date_filter" class="search-select">
                    <option value="all" <?= $date_filter === 'all' ? 'selected' : '' ?>>All Dates</option>
                    <option value="today" <?= $date_filter === 'today' ? 'selected' : '' ?>>Today Only</option>
                    <option value="past" <?= $date_filter === 'past' ? 'selected' : '' ?>>Past Shows</option>
                    <option value="upcoming" <?= $date_filter === 'upcoming' ? 'selected' : '' ?>>Upcoming Shows</option>
                </select>
            </div>

            <div class="search-group">
                <label class="search-label">Status</label>
                <select name="status" class="search-select">
                    <option value="">All Statuses</option>
                    <option value="Confirmed" <?= $filter_status === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                    <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Cancelled" <?= $filter_status === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>

            <div class="search-actions" style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="bookings.php" class="btn btn-outline">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Booking Records</h3>

        <div style="display: flex; gap: 0.5rem;">
            <button onclick="window.print()" class="btn btn-outline">
                Export Report
            </button>
        </div>
    </div>

    <div class="card-body" style="padding: 0;">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="padding-left: 1.5rem;">Booking Details</th>
                        <th>Customer</th>
                        <th>Show Information</th>
                        <th>Seats</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Show Status</th>
                        <th style="padding-right: 1.5rem; text-align: right;">Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!$bookings): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 3rem; color: #64748b;">
                                No bookings found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $b): ?>
                            <?php
                            $paymentStatus = strtolower($b['payment_status'] ?? 'unpaid');

                            $showDate = $b['show_date'] ?? null;
                            $showTime = $b['show_time'] ?? null;

                            if (empty($showDate) || empty($showTime)) {
                                $showStatus = 'Showtime Removed';
                                $showStatusColor = '#64748b';
                            } elseif ($showDate < $today || ($showDate == $today && $showTime <= $currentTime)) {
                                $showStatus = 'Past Show';
                                $showStatusColor = '#64748b';
                            } else {
                                $showStatus = 'Upcoming';
                                $showStatusColor = '#16a34a';
                            }

                            if ($b['status'] === 'Confirmed') {
                                $bookingBadge = 'badge-success';
                            } elseif ($b['status'] === 'Pending') {
                                $bookingBadge = 'badge-warning';
                            } elseif ($b['status'] === 'Cancelled') {
                                $bookingBadge = 'badge-danger';
                            } else {
                                $bookingBadge = 'badge-muted';
                            }
                            ?>

                            <tr>
                                <td style="padding-left: 1.5rem;">
                                    <div style="font-weight: 700; color: #0f172a;">
                                        #BK-<?= htmlspecialchars($b['booking_id']) ?>
                                    </div>

                                    <div style="font-size: 0.75rem; color: #64748b;">
                                        <?= !empty($b['created_at']) ? date('M d, Y h:i A', strtotime($b['created_at'])) : 'No date' ?>
                                    </div>

                                    <?php if (!empty($b['transaction_uuid'])): ?>
                                        <div style="font-size: 0.7rem; color: #94a3b8; margin-top: 0.25rem;">
                                            <?= htmlspecialchars($b['transaction_uuid']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div style="font-weight: 600; color: #334155;">
                                        <?= htmlspecialchars($b['customer_name']) ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #64748b;">
                                        <?= htmlspecialchars($b['customer_email']) ?>
                                    </div>
                                </td>

                                <td>
                                    <div style="font-weight: 600; color: #4f46e5;">
                                        <?= htmlspecialchars($b['movie_title']) ?>
                                    </div>

                                    <div style="font-size: 0.75rem; color: #64748b;">
                                        <?php if (!empty($showDate) && !empty($showTime)): ?>
                                            <?= date('D, M d, Y', strtotime($showDate)) ?>
                                            at
                                            <?= date('h:i A', strtotime($showTime)) ?>
                                            <?php if (!empty($b['screen'])): ?>
                                                <br><?= htmlspecialchars($b['screen']) ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            Showtime not available
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td>
                                    <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                        <?php
                                        $seats = explode(',', $b['selected_seats'] ?? '');

                                        foreach ($seats as $seat):
                                            if (trim($seat) === '') {
                                                continue;
                                            }
                                        ?>
                                            <span style="display: inline-block; padding: 0.125rem 0.375rem; background: #f1f5f9; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 700;">
                                                <?= htmlspecialchars(trim($seat)) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>

                                <td>
                                    <div style="font-weight: 700; color: #0f172a;">
                                        NPR <?= number_format((float)$b['amount'], 2) ?>
                                    </div>

                                    <div style="font-size: 0.75rem; color: <?= $paymentStatus === 'paid' ? '#10b981' : '#f59e0b' ?>; font-weight: 600; text-transform: uppercase;">
                                        <?= htmlspecialchars($b['payment_status'] ?: 'Unpaid') ?>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge <?= $bookingBadge ?>">
                                        <?= htmlspecialchars($b['status']) ?>
                                    </span>
                                </td>

                                <td>
                                    <span style="font-weight: 700; color: <?= $showStatusColor ?>;">
                                        <?= htmlspecialchars($showStatus) ?>
                                    </span>
                                </td>

                                <td style="padding-right: 1.5rem; text-align: right;">
                                    <div class="btn-group" style="justify-content: flex-end; display: flex; gap: 0.5rem;">
                                        <a 
                                            href="view_booking.php?booking_id=<?= $b['booking_id'] ?>" 
                                            class="btn btn-outline" 
                                            style="padding: 0.25rem 0.5rem; font-size: 0.75rem;"
                                            title="View Booking"
                                        >
                                            View
                                        </a>

                                        <?php if ($b['status'] === 'Cancelled'): ?>
                                            <button 
                                                type="button"
                                                class="btn"
                                                style="padding: 0.25rem 0.5rem; font-size: 0.75rem; background: #e5e7eb; color: #64748b; cursor: not-allowed; border: none;"
                                                disabled
                                            >
                                                Cancelled
                                            </button>
                                        <?php else: ?>
                                            <a 
                                                href="bookings.php?cancel_id=<?= $b['booking_id'] ?>" 
                                                class="btn btn-secondary" 
                                                style="padding: 0.25rem 0.5rem; font-size: 0.75rem;"
                                                title="Cancel Booking"
                                                onclick="return confirm('Mark this booking as Cancelled?')"
                                            >
                                                Cancel
                                            </a>
                                        <?php endif; ?>

                                        <a 
                                            href="bookings.php?delete_id=<?= $b['booking_id'] ?>" 
                                            class="btn btn-danger" 
                                            style="padding: 0.25rem 0.5rem; font-size: 0.75rem;"
                                            title="Delete Record"
                                            onclick="return confirm('Permanently delete this booking record?')"
                                        >
                                            Delete
                                        </a>
                                    </div>
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