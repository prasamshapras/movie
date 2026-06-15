<?php
require_once '../includes/config.php';
require_once '../includes/automation.php';

if (!isAdminLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $movie_id = intval($_POST['movie_id']);
    $show_date = $_POST['show_date'];
    $show_time = $_POST['show_time'];
    $screen = trim($_POST['screen']);
    $price = floatval($_POST['base_price'] ?: 250);

    if (!$movie_id || !$show_date || !$show_time) {
        $err = 'Please fill required fields';
    } else {
        $ins = $pdo->prepare("INSERT INTO showtimes (movie_id, show_date, show_time, screen, base_price) VALUES (?,?,?,?,?)");
        $ins->execute([$movie_id, $show_date, $show_time, $screen, $price]);
        $sid = $pdo->lastInsertId();
        header('Location: seed_seats.php?showtime_id=' . $sid);
        exit;
    }
}

$movies = $pdo->query("SELECT movie_id, title FROM movies ORDER BY title")->fetchAll();
$page_title = 'Add Showtime';
require_once 'includes/admin_header.php';
?>

<style>
    .form-container {
        max-width: 900px;
        margin: 0 auto;
    }
    .form-section-title {
        font-size: 0.875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        color: #64748b;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .form-section-title::after {
        content: "";
        flex: 1;
        height: 1px;
        background: #e2e8f0;
    }
    .input-wrapper {
        position: relative;
    }
    .input-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        pointer-events: none;
    }
    .form-input-with-icon {
        padding-left: 2.75rem !important;
    }
    .required-dot {
        color: #ef4444;
        margin-left: 0.125rem;
    }
    .form-help {
        font-size: 0.75rem;
        color: #94a3b8;
        margin-top: 0.375rem;
    }
    .btn-lg {
        padding: 0.75rem 2rem;
        font-size: 1rem;
    }
    .movie-select-option {
        padding: 0.5rem;
    }
</style>

<div class="form-container">
    <div class="admin-page-header" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <nav style="margin-bottom: 0.5rem;">
                <ol style="display: flex; list-style: none; padding: 0; margin: 0; font-size: 0.875rem; color: #64748b;">
                    <li><a href="dashboard.php" style="color: #4f46e5; text-decoration: none;">Dashboard</a></li>
                    <li style="margin: 0 0.5rem;">/</li>
                    <li>Add Showtime</li>
                </ol>
            </nav>
            <h2 style="font-size: 1.875rem; font-weight: 700; color: #0f172a;">Create New Showtime</h2>
            <p style="color: #64748b; margin-top: 0.25rem;">Schedule a movie screening and manage ticket pricing.</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Dashboard
        </a>
    </div>

    <?php if($err): ?>
        <div class="alert alert-error" style="padding: 1rem; border-radius: 0.75rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem; background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <div style="background: #fee2e2; padding: 0.5rem; border-radius: 0.5rem; display: flex;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <div>
                <span style="font-weight: 600;">Action Required</span>
                <p style="font-size: 0.875rem; opacity: 0.9;"><?= htmlspecialchars($err) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <form method="post" class="card" style="border: none; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);">
        <div class="card-body" style="padding: 2.5rem;">
            <div class="form-section-title">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                </svg>
                Movie & Schedule
            </div>
            
            <div class="form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem;">
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label" style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: #334155; font-size: 0.9375rem;">
                        Select Movie <span class="required-dot">*</span>
                    </label>
                    <div class="input-wrapper">
                        <svg class="input-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <select name="movie_id" class="form-select form-input-with-icon" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e2e8f0; border-radius: 0.75rem; font-size: 1rem; transition: border-color 0.2s; cursor: pointer; background-color: #fff;" required>
                            <option value="">Search or select a movie...</option>
                            <?php foreach($movies as $m): ?>
                                <option value="<?= $m['movie_id'] ?>" class="movie-select-option"><?= htmlspecialchars($m['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: #334155; font-size: 0.9375rem;">
                        Show Date <span class="required-dot">*</span>
                    </label>
                    <div class="input-wrapper">
                        <svg class="input-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <input type="date" name="show_date" class="form-input form-input-with-icon" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e2e8f0; border-radius: 0.75rem; font-size: 1rem;" required min="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: #334155; font-size: 0.9375rem;">
                        Show Time <span class="required-dot">*</span>
                    </label>
                    <div class="input-wrapper">
                        <svg class="input-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <input type="time" name="show_time" class="form-input form-input-with-icon" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e2e8f0; border-radius: 0.75rem; font-size: 1rem;" required>
                    </div>
                </div>
            </div>

            <div class="form-section-title" style="margin-top: 3rem;">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                Venue & Pricing
            </div>

            <div class="form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem;">
                <div class="form-group">
                    <label class="form-label" style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: #334155; font-size: 0.9375rem;">Screen Name</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                        </svg>
                        <input type="text" name="screen" class="form-input form-input-with-icon" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e2e8f0; border-radius: 0.75rem; font-size: 1rem;" placeholder="e.g. Audi 1, Screen 2">
                    </div>
                    <p class="form-help">Optional: Specify which theater room.</p>
                </div>

                <div class="form-group">
                    <label class="form-label" style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: #334155; font-size: 0.9375rem;">Base Ticket Price (NPR)</label>
                    <div class="input-wrapper">
                        <div class="input-icon" style="font-weight: 700; font-size: 0.875rem; color: #64748b;">Rs.</div>
                        <input type="number" name="base_price" class="form-input form-input-with-icon" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e2e8f0; border-radius: 0.75rem; font-size: 1rem;" value="250" step="10" min="0">
                    </div>
                    <p class="form-help">Default price for standard seating.</p>
                </div>
            </div>

            <div class="form-actions" style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 3.5rem; padding-top: 2rem; border-top: 1.5px solid #f1f5f9;">
                <a href="dashboard.php" class="btn btn-outline" style="padding: 0.75rem 1.5rem;">Cancel</a>
                <button type="submit" class="btn btn-primary btn-lg" style="box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.4);">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Create Showtime
                </button>
            </div>
        </div>
    </form>
</div>

<?php require_once 'includes/admin_footer.php'; ?>

