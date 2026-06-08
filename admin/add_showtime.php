<?php
$page_title = 'Add Showtime';
require_once 'includes/admin_header.php';

$movies = $pdo->query("SELECT movie_id, title FROM movies ORDER BY title")->fetchAll();
$err='';
if($_SERVER['REQUEST_METHOD']=='POST'){
    $movie_id = intval($_POST['movie_id']);
    $show_date = $_POST['show_date'];
    $show_time = $_POST['show_time'];
    $screen = trim($_POST['screen']);
    $price = floatval($_POST['base_price'] ?: 250);

    if(!$movie_id || !$show_date || !$show_time) $err = 'Please fill required fields';
    else {
        $ins = $pdo->prepare("INSERT INTO showtimes (movie_id, show_date, show_time, screen, base_price) VALUES (?,?,?,?,?)");
        $ins->execute([$movie_id, $show_date, $show_time, $screen, $price]);
        $sid = $pdo->lastInsertId();
        header('Location: seed_seats.php?showtime_id=' . $sid); exit;
    }
}
?>

<?php if($err): ?>
    <div class="alert alert-error" style="padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <?= htmlspecialchars($err) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>Showtime Information</h3>
        <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
    </div>
    <div class="card-body">
        <form method="post">
            <div class="form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem;">Movie *</label>
                    <select name="movie_id" class="form-select" style="width: 100%; padding: 0.625rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem;" required>
                        <option value="">Select Movie</option>
                        <?php foreach($movies as $m): ?>
                            <option value="<?= $m['movie_id'] ?>"><?= htmlspecialchars($m['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem;">Show Date *</label>
                    <input type="date" name="show_date" class="form-input" style="width: 100%; padding: 0.625rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem;" required>
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem;">Show Time *</label>
                    <input type="time" name="show_time" class="form-input" style="width: 100%; padding: 0.625rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem;" required>
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem;">Screen</label>
                    <input type="text" name="screen" class="form-input" style="width: 100%; padding: 0.625rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem;" placeholder="Screen 1, Screen 2, etc.">
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem;">Base Price (NPR)</label>
                    <input type="number" name="base_price" class="form-input" style="width: 100%; padding: 0.625rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem;" value="250" step="10">
                </div>
            </div>
            <div class="form-actions" style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0;">
                <a href="dashboard.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Showtime</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>