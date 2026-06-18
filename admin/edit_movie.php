<?php
$page_title = 'Edit Movie';
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
$movie = $pdo->prepare("SELECT * FROM movies WHERE movie_id = ?");
$movie->execute([$id]);
$movie = $movie->fetch();
if(!$movie) die('Movie not found');

$err = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    $genre = trim($_POST['genre']);
    $language = trim($_POST['language']);
    $duration = intval($_POST['duration']);
    $release_date = $_POST['release_date'] ?: null; // Standard YYYY-MM-DD
    $poster = ltrim(trim($_POST['poster']), '/');

    if(isset($_FILES['poster_file']) && $_FILES['poster_file']['error'] == 0){
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext = strtolower(pathinfo($_FILES['poster_file']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, $allowed)){
            if(!is_dir('../assets/uploads')) mkdir('../assets/uploads', 0755, true);
            $new_poster = 'assets/uploads/' . time() . '_' . basename($_FILES['poster_file']['name']);
            if(move_uploaded_file($_FILES['poster_file']['tmp_name'], '../' . $new_poster)){
                $poster = $new_poster;
            }
        } else {
            $err = "Invalid file type. Only JPG, PNG, GIF, WEBP allowed.";
        }
    }

    if(!$title) {
        $err = 'Title is required';
    } elseif(!$release_date) {
        $err = 'Release date is required';
    } elseif(!$err) {
        $upd = $pdo->prepare("UPDATE movies SET title=?, description=?, genre=?, language=?, duration=?, poster=?, release_date=? WHERE movie_id = ?");
        $upd->execute([$title, $desc, $genre, $language, $duration, $poster, $release_date, $id]);
        $success = 'Movie updated successfully!';
        
        $movie = $pdo->prepare("SELECT * FROM movies WHERE movie_id = ?");
        $movie->execute([$id]);
        $movie = $movie->fetch();
    }
}

require_once 'includes/admin_header.php';
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

<?php if($success): ?>
    <div class="alert alert-success" style="padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; background: #d1fae5; color: #065f46; border-left: 4px solid #10b981;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 6L9 17l-5-5"/>
        </svg>
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>Edit Movie: <?= htmlspecialchars($movie['title']) ?></h3>
        <a href="movies.php" class="btn btn-outline">Back to Movies</a>
    </div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <div class="form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem;">Movie Title *</label>
                    <input type="text" name="title" class="form-input" style="width: 100%; padding: 0.625rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem;" value="<?= htmlspecialchars($movie['title']) ?>" required>
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem;">Genre</label>
                    <input type="text" name="genre" class="form-input" style="width: 100%; padding: 0.625rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem;" value="<?= htmlspecialchars($movie['genre']) ?>">
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem;">Language</label>
                    <input type="text" name="language" class="form-input" style="width: 100%; padding: 0.625rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem;" value="<?= htmlspecialchars($movie['language']) ?>">
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem;">Duration (minutes)</label>
                    <input type="number" name="duration" class="form-input" style="width: 100%; padding: 0.625rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem;" value="<?= $movie['duration'] ?>">
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem;">Release Date *</label>
                    <input type="date" name="release_date" class="form-input" style="width: 100%; padding: 0.625rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem;" value="<?= $movie['release_date'] ?>" required>
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem;">Poster URL / Manual Path</label>
                    <input type="text" name="poster" id="posterUrl" class="form-input" style="width: 100%; padding: 0.625rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem;" value="<?= htmlspecialchars($movie['poster']) ?>" placeholder="assets/uploads/filename.jpg or .webp">
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem;">Upload New Poster (Optional)</label>
                    <input type="file" name="poster_file" class="form-input" style="width: 100%; padding: 0.625rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem;" accept="image/*">
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem;">Description</label>
                <textarea name="description" class="form-textarea" style="width: 100%; padding: 0.625rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem; resize: vertical; min-height: 120px;" rows="6"><?= htmlspecialchars($movie['description']) ?></textarea>
            </div>
            <div class="form-actions" style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0;">
                <a href="movies.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    const baseUrl = '<?= BASE_URL ?>';
    const posterInput = document.getElementById('posterUrl');
    const previewImg = document.getElementById('posterPreview');
    const noImageMsg = document.getElementById('noImageMsg');

    function getFullUrl(path) {
        if (!path || path.trim() === '') return null;
        if (path.match(/^https?:\/\//i)) return path;
        return baseUrl + '/' + path.replace(/^\/+/, '');
    }

    function updatePreview() {
        if (!posterInput) return;
        const path = posterInput.value.trim();

        if (!path) {
            if (previewImg) previewImg.style.display = 'none';
            if (noImageMsg) {
                noImageMsg.style.display = 'block';
                noImageMsg.innerHTML = 'Enter a poster URL to see preview';
            }
            return;
        }

        const fullUrl = getFullUrl(path);
        if (previewImg) {
            previewImg.src = fullUrl;
            previewImg.style.display = 'block';
        }
        if (noImageMsg) noImageMsg.style.display = 'none';

        if (previewImg) {
            previewImg.onerror = function() {
                previewImg.style.display = 'none';
                if (noImageMsg) {
                    noImageMsg.style.display = 'block';
                    noImageMsg.innerHTML = 'Image not found or invalid URL';
                }
            };
        }
    }

    if (posterInput) posterInput.addEventListener('input', updatePreview);
    updatePreview();
</script>

<?php require_once 'includes/admin_footer.php'; ?>
