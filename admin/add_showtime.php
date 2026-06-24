<?php
require_once '../includes/config.php';
require_once '../includes/automation.php';

date_default_timezone_set('Asia/Kathmandu');

if (!isAdminLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$err = '';
$today = date('Y-m-d');
$maxDate = date('Y-m-d', strtotime('+7 days'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movie_id = intval($_POST['movie_id'] ?? 0);
    $show_date = trim($_POST['show_date'] ?? '');
    $show_time = trim($_POST['show_time'] ?? '');
    $screen = trim($_POST['screen'] ?? 'Screen 1');
    $price = floatval($_POST['base_price'] ?: 250);

    if (!$movie_id || !$show_date || !$show_time) {
        $err = 'Please fill all required fields.';
    } elseif ($show_date < $today) {
        $err = 'You cannot add showtime for a past date.';
    } elseif ($show_date > $maxDate) {
        $err = 'Showtime can only be added within the next 7 days.';
    } else {
        $showDateTime = strtotime($show_date . ' ' . $show_time);

        if ($showDateTime <= time()) {
            $err = 'You cannot add a past showtime. Please select a future time.';
        } else {
            $movieCheck = $pdo->prepare("SELECT COUNT(*) FROM movies WHERE movie_id = ?");
            $movieCheck->execute([$movie_id]);

            if ($movieCheck->fetchColumn() == 0) {
                $err = 'Selected movie does not exist.';
            } else {
                $dup = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM showtimes
                    WHERE movie_id = ?
                    AND show_date = ?
                    AND show_time = ?
                    AND screen = ?
                ");
                $dup->execute([$movie_id, $show_date, $show_time, $screen]);

                if ($dup->fetchColumn() > 0) {
                    $err = 'This showtime already exists for the selected movie, date, time and screen.';
                } else {
                    $ins = $pdo->prepare("
                        INSERT INTO showtimes 
                        (movie_id, show_date, show_time, screen, base_price) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $ins->execute([$movie_id, $show_date, $show_time, $screen, $price]);

                    $sid = $pdo->lastInsertId();

                    header('Location: seed_seats.php?showtime_id=' . $sid);
                    exit;
                }
            }
        }
    }
}

$movies = $pdo->query("
    SELECT movie_id, title 
    FROM movies 
    ORDER BY title ASC
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Add Showtime';
require_once 'includes/admin_header.php';
?>

<div class="form-container" style="max-width: 900px; margin: 0 auto;">
    <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem;">
        <div>
            <h2 style="font-size: 1.875rem; font-weight: 700; color: #0f172a;">Create New Showtime</h2>
            <p style="color: #64748b; margin-top: 0.25rem;">
                Showtime can be added only from today to next 7 days.
            </p>
        </div>

        <div style="display:flex; gap:0.75rem;">
            <a href="showtimes.php" class="btn btn-outline">Manage Showtimes</a>
            <a href="dashboard.php" class="btn btn-outline">Dashboard</a>
        </div>
    </div>

    <?php if ($err): ?>
        <div style="padding: 1rem; border-radius: 0.75rem; margin-bottom: 2rem; background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2;">
            <strong>Error:</strong> <?= htmlspecialchars($err) ?>
        </div>
    <?php endif; ?>

    <form method="post" class="card" style="border: none; box-shadow: 0 10px 15px rgba(0,0,0,0.08);">
        <div class="card-body" style="padding: 2.5rem;">
            <div style="margin-bottom: 2rem;">
                <label style="display:block; margin-bottom:0.75rem; font-weight:600;">Select Movie *</label>
                <select name="movie_id" style="width:100%; padding:0.8rem; border:1.5px solid #e2e8f0; border-radius:0.75rem;" required>
                    <option value="">Select a movie...</option>
                    <?php foreach ($movies as $m): ?>
                        <option value="<?= $m['movie_id'] ?>" <?= (isset($_POST['movie_id']) && intval($_POST['movie_id']) === intval($m['movie_id'])) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap: 2rem;">
                <div>
                    <label style="display:block; margin-bottom:0.75rem; font-weight:600;">Show Date *</label>
                    <input 
                        type="date" 
                        name="show_date" 
                        value="<?= htmlspecialchars($_POST['show_date'] ?? '') ?>"
                        min="<?= $today ?>"
                        max="<?= $maxDate ?>"
                        style="width:100%; padding:0.8rem; border:1.5px solid #e2e8f0; border-radius:0.75rem;" 
                        required
                    >
                    <small style="color:#64748b;">Allowed: <?= $today ?> to <?= $maxDate ?></small>
                </div>

                <div>
                    <label style="display:block; margin-bottom:0.75rem; font-weight:600;">Show Time *</label>
                    <input 
                        type="time" 
                        name="show_time" 
                        value="<?= htmlspecialchars($_POST['show_time'] ?? '') ?>"
                        style="width:100%; padding:0.8rem; border:1.5px solid #e2e8f0; border-radius:0.75rem;" 
                        required
                    >
                </div>

                <div>
                    <label style="display:block; margin-bottom:0.75rem; font-weight:600;">Screen Name</label>
                    <input 
                        type="text" 
                        name="screen" 
                        value="<?= htmlspecialchars($_POST['screen'] ?? 'Screen 1') ?>"
                        placeholder="e.g. Screen 1, Audi 2"
                        style="width:100%; padding:0.8rem; border:1.5px solid #e2e8f0; border-radius:0.75rem;"
                    >
                </div>

                <div>
                    <label style="display:block; margin-bottom:0.75rem; font-weight:600;">Base Ticket Price NPR</label>
                    <input 
                        type="number" 
                        name="base_price" 
                        value="<?= htmlspecialchars($_POST['base_price'] ?? '250') ?>"
                        step="10"
                        min="0"
                        style="width:100%; padding:0.8rem; border:1.5px solid #e2e8f0; border-radius:0.75rem;"
                    >
                </div>
            </div>

            <div style="display:flex; gap:1rem; justify-content:flex-end; margin-top:3rem; padding-top:2rem; border-top:1px solid #f1f5f9;">
                <a href="showtimes.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Showtime</button>
            </div>
        </div>
    </form>
</div>

<?php require_once 'includes/admin_footer.php'; ?>