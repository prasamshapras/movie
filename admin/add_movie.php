<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    $genre = trim($_POST['genre']);
    $language = trim($_POST['language']);
    $duration = intval($_POST['duration']);
    $release_date = $_POST['release_date'] ?: null; // Proper YYYY-MM-DD from HTML5 date input
    $poster = '';

    if (isset($_FILES['poster_file']) && $_FILES['poster_file']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['poster_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $err = "Invalid file type. Only JPG, PNG, GIF, WEBP allowed.";
        } else {
            if (!is_dir('../assets/uploads')) mkdir('../assets/uploads', 0755, true);
            $poster = 'assets/uploads/' . time() . '_' . basename($_FILES['poster_file']['name']);
            $poster = str_replace('\\', '/', $poster);
            $poster = ltrim($poster, '/');
            move_uploaded_file($_FILES['poster_file']['tmp_name'], '../' . $poster);
        }
    } else {
        $err = 'Poster image is required.';
    }

    if (!$title && !$err) $err = 'Title is required';
    if (!$release_date && !$err) $err = 'Release date is required for automatic Now Showing/Upcoming logic';
    
    if (!$err) {
        // SQL insertion uses standard DATE format
        $ins = $pdo->prepare("INSERT INTO movies (title, description, genre, language, duration, poster, release_date) VALUES (?,?,?,?,?,?,?)");
        $ins->execute([$title, $desc, $genre, $language, $duration, $poster, $release_date]);
        header('Location: movies.php');
        exit;
    }
}

$page_title = 'Add Movie';
require_once 'includes/admin_header.php';
?>

<?php if ($err): ?>
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
        <h3>Movie Information</h3>
        <a href="movies.php" class="btn btn-outline">Back to Movies</a>
    </div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <div class="form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem;">Movie Title *</label>
                    <input type="text" name="title" class="form-input" style="width: 100%; padding: 0.625rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem;" required>
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem;">Genre</label>
                    <input type="text" name="genre" class="form-input" style="width: 100%; padding: 0.625rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem;" placeholder="Action, Drama, Comedy">
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem;">Language</label>
                    <input type="text" name="language" class="form-input" style="width: 100%; padding: 0.625rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem;" placeholder="English, Hindi, Nepali">
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem;">Duration (minutes)</label>
                    <input type="number" name="duration" class="form-input" style="width: 100%; padding: 0.625rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem;" min="1" max="300">
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem;">Release Date *</label>
                    <input type="date" name="release_date" class="form-input" style="width: 100%; padding: 0.625rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem;" required>
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem;">Poster Image *</label>
                    <input type="file" name="poster_file" class="form-input" style="width: 100%; padding: 0.625rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem;" accept="image/*" required>
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem;">Description</label>
                <textarea name="description" class="form-textarea" style="width: 100%; padding: 0.625rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem; resize: vertical; min-height: 120px;" rows="6" placeholder="Enter movie description..."></textarea>
            </div>
            <div class="form-actions" style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0;">
                <a href="movies.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Movie</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
