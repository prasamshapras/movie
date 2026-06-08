<?php
$page_title = 'Manage Bookings';

require_once '../includes/config.php';

if (!isAdminLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Cancel booking
if (isset($_GET['cancel_id'])) {
    $id = intval($_GET['cancel_id']);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT booking_id, showtime_id, seat_label
            FROM bookings
            WHERE booking_id = ?
            FOR UPDATE
        ");
        $stmt->execute([$id]);
        $booking = $stmt->fetch();

        if ($booking) {
            $pdo->prepare("
                UPDATE bookings
                SET status = 'Cancelled',
                    payment_status = 'Cancelled'
                WHERE booking_id = ?
            ")->execute([$id]);

            $pdo->prepare("
                UPDATE seats
                SET status = 'available',
                    reserved_until = NULL,
                    reserved_by_customer_id = NULL
                WHERE showtime_id = ?
                AND seat_label = ?
            ")->execute([
                $booking['showtime_id'],
                $booking['seat_label']
            ]);
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

// Delete booking
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
        $booking = $stmt->fetch();

        if ($booking) {
            $pdo->prepare("
                UPDATE seats
                SET status = 'available',
                    reserved_until = NULL,
                    reserved_by_customer_id = NULL
                WHERE showtime_id = ?
                AND seat_label = ?
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

// Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';
$filter_movie = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : 0;

// Fetch bookings
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
        m.title AS movie_title,
        c.name AS customer_name, 
        c.email AS customer_email,
        s.show_date,
        s.show_time
    FROM bookings b
    LEFT JOIN movies m ON b.movie_id = m.movie_id
    LEFT JOIN customers c ON b.customer_id = c.customer_id
    LEFT JOIN showtimes s ON b.showtime_id = s.showtime_id
    WHERE 1=1
";

$params = [];

if ($search !== '') {
    $sql .= " AND (c.name LIKE ? OR c.email LIKE ? OR b.booking_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_status !== '') {
    $sql .= " AND b.status = ?";
    $params[] = $filter_status;
}

if ($filter_movie > 0) {
    $sql .= " AND b.movie_id = ?";
    $params[] = $filter_movie;
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Movies for filter
$movies = $pdo->query("
    SELECT movie_id, title
    FROM movies
    ORDER BY title
")->fetchAll();

// Stats
$totalBookings = count($bookings);
$totalRevenue = 0;
$confirmedCount = 0;
$pendingCount = 0;
$cancelledCount = 0;

foreach ($bookings as $b) {
    if ($b['status'] === 'Confirmed') {
        $totalRevenue += $b['amount'];
        $confirmedCount++;
    } elseif ($b['status'] === 'Pending') {
        $pendingCount++;
    } elseif ($b['status'] === 'Cancelled') {
        $cancelledCount++;
    }
}

require_once 'includes/admin_header.php';
?>

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
        <div class="stat-label">Total Bookings</div>
        <div class="stat-value"><?= number_format($totalBookings) ?></div>
        <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;">Total requests received</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value" style="color: #10b981;">NPR <?= number_format($totalRevenue, 2) ?></div>
        <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;">From confirmed bookings</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Confirmed</div>
        <div class="stat-value" style="color: #3b82f6;"><?= number_format($confirmedCount) ?></div>
        <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;">Successfully paid/confirmed</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Pending / Cancelled</div>
        <div class="stat-value" style="color: #f59e0b;">
            <?= number_format($pendingCount) ?>
            <span style="font-size: 1rem; color: #ef4444;">/ <?= number_format($cancelledCount) ?></span>
        </div>
        <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;">Awaiting payment or cancelled</div>
    </div>
</div>

<div class="search-section">
    <div class="search-header">
        <h3>Filter Bookings</h3>
    </div>

    <div class="search-body">
        <form method="GET" class="search-form">
            <div class="search-group">
                <label class="search-label">Customer Name / Email / Booking ID</label>
                <input 
                    type="text" 
                    name="search" 
                    class="search-input" 
                    placeholder="Search customer, email or booking ID..." 
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
                        <th style="padding-right: 1.5rem; text-align: right;">Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!$bookings): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 3rem; color: #64748b;">
                                No bookings found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $b): ?>
                            <?php
                            $paymentStatus = strtolower($b['payment_status'] ?? 'unpaid');
                            ?>
                            <tr>
                                <td style="padding-left: 1.5rem;">
                                    <div style="font-weight: 700; color: #0f172a;">
                                        #BK-<?= htmlspecialchars($b['booking_id']) ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #64748b;">
                                        <?= date('M d, Y h:i A', strtotime($b['created_at'])) ?>
                                    </div>
                                </td>

                                <td>
                                    <div style="font-weight: 600; color: #334155;">
                                        <?= htmlspecialchars($b['customer_name'] ?? 'Unknown Customer') ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #64748b;">
                                        <?= htmlspecialchars($b['customer_email'] ?? 'No email') ?>
                                    </div>
                                </td>

                                <td>
                                    <div style="font-weight: 600; color: #4f46e5;">
                                        <?= htmlspecialchars($b['movie_title'] ?? 'Unknown Movie') ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #64748b;">
                                        <?php if (!empty($b['show_date']) && !empty($b['show_time'])): ?>
                                            <?= date('D, M d', strtotime($b['show_date'])) ?>
                                            at
                                            <?= date('h:i A', strtotime($b['show_time'])) ?>
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
                                    <span class="badge 
                                        <?= $b['status'] === 'Confirmed' ? 'badge-success' : ($b['status'] === 'Pending' ? 'badge-warning' : 'badge-danger') ?>">
                                        <?= htmlspecialchars($b['status']) ?>
                                    </span>
                                </td>

                                <td style="padding-right: 1.5rem; text-align: right;">
                                    <div class="btn-group" style="justify-content: flex-end;">

                                        <a 
                                            href="view_booking.php?booking_id=<?= $b['booking_id'] ?>" 
                                            class="btn btn-outline" 
                                            style="padding: 0.25rem 0.5rem; font-size: 0.75rem;"
                                            title="View Booking"
                                        >
                                            View
                                        </a>

                                        <?php if ($b['status'] !== 'Cancelled'): ?>
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

<style>
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

<?php require_once 'includes/admin_footer.php'; ?>