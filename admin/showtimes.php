<?php
require_once '../includes/config.php';

date_default_timezone_set('Asia/Kathmandu');

if (!isAdminLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$today = date('Y-m-d');
$currentTime = date('H:i:s');
$maxDate = date('Y-m-d', strtotime('+7 days'));
$msg = '';
$err = '';

/*
|--------------------------------------------------------------------------
| Delete Showtime
|--------------------------------------------------------------------------
| Only delete if no confirmed bookings exist.
*/

if (isset($_GET['delete'])) {
    $showtime_id = intval($_GET['delete']);

    try {
        $pdo->beginTransaction();

        $checkConfirmed = $pdo->prepare("
            SELECT COUNT(*)
            FROM bookings
            WHERE showtime_id = ?
            AND status = 'Confirmed'
        ");
        $checkConfirmed->execute([$showtime_id]);

        if ($checkConfirmed->fetchColumn() > 0) {
            throw new Exception('Cannot delete this showtime because confirmed bookings exist.');
        }

        $deletePayments = $pdo->prepare("
            DELETE p
            FROM payments p
            INNER JOIN bookings b ON p.booking_id = b.booking_id
            WHERE b.showtime_id = ?
        ");
        $deletePayments->execute([$showtime_id]);

        $deleteBookings = $pdo->prepare("DELETE FROM bookings WHERE showtime_id = ?");
        $deleteBookings->execute([$showtime_id]);

        $deleteSeats = $pdo->prepare("DELETE FROM seats WHERE showtime_id = ?");
        $deleteSeats->execute([$showtime_id]);

        $deleteShowtime = $pdo->prepare("DELETE FROM showtimes WHERE showtime_id = ?");
        $deleteShowtime->execute([$showtime_id]);

        $pdo->commit();

        header('Location: showtimes.php?msg=deleted');
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $err = $e->getMessage();
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $msg = 'Showtime deleted successfully.';
}

if (isset($_GET['msg']) && $_GET['msg'] === 'updated') {
    $msg = 'Showtime updated successfully.';
}

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$movieFilter = intval($_GET['movie_id'] ?? 0);

$movies = $pdo->query("
    SELECT movie_id, title
    FROM movies
    ORDER BY title ASC
")->fetchAll(PDO::FETCH_ASSOC);

$params = [];
$where = "WHERE 1=1";

if ($movieFilter > 0) {
    $where .= " AND s.movie_id = ?";
    $params[] = $movieFilter;
}

$stmt = $pdo->prepare("
    SELECT 
        s.showtime_id,
        s.movie_id,
        s.show_date,
        s.show_time,
        s.screen,
        s.base_price,
        m.title,
        (
            SELECT COUNT(*) 
            FROM bookings b 
            WHERE b.showtime_id = s.showtime_id 
            AND b.status = 'Confirmed'
        ) AS confirmed_bookings,
        (
            SELECT COUNT(*) 
            FROM seats st 
            WHERE st.showtime_id = s.showtime_id
        ) AS seat_count
    FROM showtimes s
    INNER JOIN movies m ON s.movie_id = m.movie_id
    $where
    ORDER BY s.show_date ASC, s.show_time ASC
");
$stmt->execute($params);
$showtimes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Manage Showtimes';
require_once 'includes/admin_header.php';
?>

<div style="max-width: 1200px; margin: 0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:1rem; margin-bottom:2rem;">
        <div>
            <h2 style="font-size:1.875rem; font-weight:700; color:#0f172a;">Manage Showtimes</h2>
            <p style="color:#64748b; margin-top:0.25rem;">
                Edit or delete movie showtimes. Customer side shows only next 7 days.
            </p>
        </div>

        <a href="add_showtime.php" class="btn btn-primary">+ Add Showtime</a>
    </div>

    <?php if ($msg): ?>
        <div style="padding:1rem; background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; border-radius:0.75rem; margin-bottom:1rem;">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <?php if ($err): ?>
        <div style="padding:1rem; background:#fef2f2; color:#991b1b; border:1px solid #fecaca; border-radius:0.75rem; margin-bottom:1rem;">
            <?= htmlspecialchars($err) ?>
        </div>
    <?php endif; ?>

    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-body">
            <form method="get" style="display:flex; gap:1rem; align-items:end; flex-wrap:wrap;">
                <div style="min-width:260px;">
                    <label style="display:block; margin-bottom:0.5rem; font-weight:600;">Filter by Movie</label>
                    <select name="movie_id" style="width:100%; padding:0.75rem; border:1px solid #e2e8f0; border-radius:0.5rem;">
                        <option value="0">All Movies</option>
                        <?php foreach ($movies as $m): ?>
                            <option value="<?= $m['movie_id'] ?>" <?= $movieFilter === intval($m['movie_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="showtimes.php" class="btn btn-outline">Reset</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>All Showtimes</h3>
        </div>

        <div class="card-body">
            <?php if (empty($showtimes)): ?>
                <p style="text-align:center; color:#64748b; padding:2rem;">No showtimes found.</p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f8fafc;">
                                <th style="padding:0.9rem; text-align:left;">Movie</th>
                                <th style="padding:0.9rem; text-align:left;">Date</th>
                                <th style="padding:0.9rem; text-align:left;">Time</th>
                                <th style="padding:0.9rem; text-align:left;">Screen</th>
                                <th style="padding:0.9rem; text-align:left;">Price</th>
                                <th style="padding:0.9rem; text-align:left;">Seats</th>
                                <th style="padding:0.9rem; text-align:left;">Bookings</th>
                                <th style="padding:0.9rem; text-align:left;">Status</th>
                                <th style="padding:0.9rem; text-align:left;">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($showtimes as $s): ?>
                                <?php
                                $isPast = ($s['show_date'] < $today) || ($s['show_date'] === $today && $s['show_time'] <= $currentTime);
                                $tooFar = ($s['show_date'] > $maxDate);
                                ?>
                                <tr style="border-bottom:1px solid #e2e8f0;">
                                    <td style="padding:0.9rem;">
                                        <strong><?= htmlspecialchars($s['title']) ?></strong>
                                    </td>

                                    <td style="padding:0.9rem;">
                                        <?= date('M d, Y', strtotime($s['show_date'])) ?>
                                    </td>

                                    <td style="padding:0.9rem;">
                                        <?= date('h:i A', strtotime($s['show_time'])) ?>
                                    </td>

                                    <td style="padding:0.9rem;">
                                        <?= htmlspecialchars($s['screen']) ?>
                                    </td>

                                    <td style="padding:0.9rem;">
                                        NPR <?= number_format($s['base_price'], 2) ?>
                                    </td>

                                    <td style="padding:0.9rem;">
                                        <?= intval($s['seat_count']) ?>
                                    </td>

                                    <td style="padding:0.9rem;">
                                        <?= intval($s['confirmed_bookings']) ?>
                                    </td>

                                    <td style="padding:0.9rem;">
                                        <?php if ($isPast): ?>
                                            <span style="color:#dc2626; font-weight:700;">Expired</span>
                                        <?php elseif ($tooFar): ?>
                                            <span style="color:#b45309; font-weight:700;">Too Far</span>
                                        <?php else: ?>
                                            <span style="color:#16a34a; font-weight:700;">Active</span>
                                        <?php endif; ?>
                                    </td>

                                    <td style="padding:0.9rem; display:flex; gap:0.5rem; flex-wrap:wrap;">
                                        <a href="edit_showtime.php?id=<?= $s['showtime_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>

                                        <a 
                                            href="showtimes.php?delete=<?= $s['showtime_id'] ?>" 
                                            class="btn btn-danger btn-sm"
                                            onclick="return confirm('Delete this showtime? This will delete seats and pending bookings for this showtime.')"
                                        >
                                            Delete
                                        </a>

                                        <a href="../movie.php?id=<?= $s['movie_id'] ?>&showtime=<?= $s['showtime_id'] ?>" class="btn btn-outline btn-sm">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>