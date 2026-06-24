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

$showtime_id = intval($_GET['id'] ?? 0);
$err = '';

if (!$showtime_id) {
    header('Location: showtimes.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT s.*, m.title
    FROM showtimes s
    INNER JOIN movies m ON s.movie_id = m.movie_id
    WHERE s.showtime_id = ?
");
$stmt->execute([$showtime_id]);
$showtime = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$showtime) {
    die('Showtime not found.');
}

/*
|--------------------------------------------------------------------------
| Prevent editing confirmed booked showtime
|--------------------------------------------------------------------------
*/

$confirmedCheck = $pdo->prepare("
    SELECT COUNT(*)
    FROM bookings
    WHERE showtime_id = ?
    AND status = 'Confirmed'
");
$confirmedCheck->execute([$showtime_id]);
$confirmedBookings = $confirmedCheck->fetchColumn();

$isPast = ($showtime['show_date'] < $today) || ($showtime['show_date'] === $today && $showtime['show_time'] <= $currentTime);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $show_date = trim($_POST['show_date'] ?? '');
    $show_time = trim($_POST['show_time'] ?? '');
    $screen = trim($_POST['screen'] ?? 'Screen 1');
    $price = floatval($_POST['base_price'] ?: 250);

    if ($confirmedBookings > 0) {
        $err = 'This showtime has confirmed bookings. You cannot edit it.';
    } elseif (!$show_date || !$show_time) {
        $err = 'Please fill all required fields.';
    } elseif ($show_date < $today) {
        $err = 'You cannot set showtime to a past date.';
    } elseif ($show_date > $maxDate) {
        $err = 'Showtime can only be set within the next 7 days.';
    } elseif (strtotime($show_date . ' ' . $show_time) <= time()) {
        $err = 'You cannot set showtime to a past time.';
    } else {
        $dup = $pdo->prepare("
            SELECT COUNT(*)
            FROM showtimes
            WHERE movie_id = ?
            AND show_date = ?
            AND show_time = ?
            AND screen = ?
            AND showtime_id != ?
        ");
        $dup->execute([
            $showtime['movie_id'],
            $show_date,
            $show_time,
            $screen,
            $showtime_id
        ]);

        if ($dup->fetchColumn() > 0) {
            $err = 'Another showtime already exists for this movie, date, time and screen.';
        } else {
            try {
                $pdo->beginTransaction();

                $update = $pdo->prepare("
                    UPDATE showtimes
                    SET show_date = ?,
                        show_time = ?,
                        screen = ?,
                        base_price = ?
                    WHERE showtime_id = ?
                ");
                $update->execute([
                    $show_date,
                    $show_time,
                    $screen,
                    $price,
                    $showtime_id
                ]);

                /*
                |--------------------------------------------------------------------------
                | Clear temporary reservations after editing time
                |--------------------------------------------------------------------------
                */

                $clearReserved = $pdo->prepare("
                    UPDATE seats
                    SET status = 'available',
                        reserved_until = NULL,
                        reserved_by_customer_id = NULL
                    WHERE showtime_id = ?
                    AND status = 'reserved'
                ");
                $clearReserved->execute([$showtime_id]);

                $pdo->commit();

                header('Location: showtimes.php?msg=updated');
                exit;

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $err = $e->getMessage();
            }
        }
    }
}

$page_title = 'Edit Showtime';
require_once 'includes/admin_header.php';
?>

<div style="max-width: 850px; margin:0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:1rem; margin-bottom:2rem;">
        <div>
            <h2 style="font-size:1.875rem; font-weight:700; color:#0f172a;">Edit Showtime</h2>
            <p style="color:#64748b; margin-top:0.25rem;">
                Movie: <strong><?= htmlspecialchars($showtime['title']) ?></strong>
            </p>
        </div>

        <a href="showtimes.php" class="btn btn-outline">Back to Showtimes</a>
    </div>

    <?php if ($confirmedBookings > 0): ?>
        <div style="padding:1rem; background:#fff7ed; color:#9a3412; border:1px solid #fed7aa; border-radius:0.75rem; margin-bottom:1rem;">
            This showtime has <?= intval($confirmedBookings) ?> confirmed booking(s). Editing is disabled.
        </div>
    <?php endif; ?>

    <?php if ($isPast): ?>
        <div style="padding:1rem; background:#fef2f2; color:#991b1b; border:1px solid #fecaca; border-radius:0.75rem; margin-bottom:1rem;">
            This showtime has already expired.
        </div>
    <?php endif; ?>

    <?php if ($err): ?>
        <div style="padding:1rem; background:#fef2f2; color:#991b1b; border:1px solid #fecaca; border-radius:0.75rem; margin-bottom:1rem;">
            <?= htmlspecialchars($err) ?>
        </div>
    <?php endif; ?>

    <form method="post" class="card">
        <div class="card-body" style="padding:2.5rem;">
            <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:2rem;">
                <div>
                    <label style="display:block; margin-bottom:0.75rem; font-weight:600;">Show Date *</label>
                    <input 
                        type="date"
                        name="show_date"
                        value="<?= htmlspecialchars($_POST['show_date'] ?? $showtime['show_date']) ?>"
                        min="<?= $today ?>"
                        max="<?= $maxDate ?>"
                        style="width:100%; padding:0.8rem; border:1px solid #e2e8f0; border-radius:0.75rem;"
                        <?= ($confirmedBookings > 0) ? 'disabled' : '' ?>
                        required
                    >
                    <small style="color:#64748b;">Allowed: <?= $today ?> to <?= $maxDate ?></small>
                </div>

                <div>
                    <label style="display:block; margin-bottom:0.75rem; font-weight:600;">Show Time *</label>
                    <input 
                        type="time"
                        name="show_time"
                        value="<?= htmlspecialchars(substr($_POST['show_time'] ?? $showtime['show_time'], 0, 5)) ?>"
                        style="width:100%; padding:0.8rem; border:1px solid #e2e8f0; border-radius:0.75rem;"
                        <?= ($confirmedBookings > 0) ? 'disabled' : '' ?>
                        required
                    >
                </div>

                <div>
                    <label style="display:block; margin-bottom:0.75rem; font-weight:600;">Screen</label>
                    <input 
                        type="text"
                        name="screen"
                        value="<?= htmlspecialchars($_POST['screen'] ?? $showtime['screen']) ?>"
                        style="width:100%; padding:0.8rem; border:1px solid #e2e8f0; border-radius:0.75rem;"
                        <?= ($confirmedBookings > 0) ? 'disabled' : '' ?>
                    >
                </div>

                <div>
                    <label style="display:block; margin-bottom:0.75rem; font-weight:600;">Base Price</label>
                    <input 
                        type="number"
                        name="base_price"
                        value="<?= htmlspecialchars($_POST['base_price'] ?? $showtime['base_price']) ?>"
                        step="10"
                        min="0"
                        style="width:100%; padding:0.8rem; border:1px solid #e2e8f0; border-radius:0.75rem;"
                        <?= ($confirmedBookings > 0) ? 'disabled' : '' ?>
                    >
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:2.5rem; padding-top:2rem; border-top:1px solid #f1f5f9;">
                <a href="showtimes.php" class="btn btn-outline">Cancel</a>

                <?php if ($confirmedBookings == 0): ?>
                    <button type="submit" class="btn btn-primary">Update Showtime</button>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<?php require_once 'includes/admin_footer.php'; ?>