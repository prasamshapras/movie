<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/recommendation.php';

$page_title = 'Discover Movies';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_genre = isset($_GET['genre']) ? trim($_GET['genre']) : '';
$filter_language = isset($_GET['language']) ? trim($_GET['language']) : '';

// Pagination settings
$limit = 8;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Base query for counting total results
$countQuery = "SELECT COUNT(*) FROM movies m WHERE 1=1";
$params = [];

if (!empty($search)) {
    $countQuery .= " AND (m.title LIKE ? OR m.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filter_genre)) {
    $countQuery .= " AND m.genre LIKE ?";
    $params[] = "%$filter_genre%";
}

if (!empty($filter_language)) {
    $countQuery .= " AND m.language = ?";
    $params[] = $filter_language;
}

$stmtCount = $pdo->prepare($countQuery);
$stmtCount->execute($params);
$totalResults = $stmtCount->fetchColumn();
$totalPages = ceil($totalResults / $limit);

// Build main query with filters and pagination
$query = "SELECT m.*, 
          (SELECT COUNT(*) FROM showtimes s WHERE s.movie_id = m.movie_id AND s.show_date >= CURDATE()) as upcoming_showtimes
          FROM movies m WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (m.title LIKE ? OR m.description LIKE ?)";
}

if (!empty($filter_genre)) {
    $query .= " AND m.genre LIKE ?";
}

if (!empty($filter_language)) {
    $query .= " AND m.language = ?";
}

$query .= " ORDER BY m.created_at DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$moviesRaw = $stmt->fetchAll();

// Get unique genres and languages for filters
$allGenresRaw = $pdo->query("SELECT DISTINCT genre FROM movies WHERE genre IS NOT NULL AND genre != ''")->fetchAll();
$allLanguagesRaw = $pdo->query("SELECT DISTINCT language FROM movies WHERE language IS NOT NULL AND language != ''")->fetchAll();

$uniqueGenres = [];
foreach($allGenresRaw as $g) {
    $parts = explode(',', $g['genre']);
    foreach($parts as $p) {
        $p = trim($p);
        if($p && !in_array($p, $uniqueGenres)) $uniqueGenres[] = $p;
    }
}
sort($uniqueGenres);

$recommendedMovies = getRecommendedMovies($pdo, currentUserId(), 4, ['time' => date('H:i:s')]);

include 'includes/header.php';
?>

<!-- Hero Section - Clean without stats -->
<section style="background: linear-gradient(135deg, var(--primary-600) 0%, var(--primary-800) 100%); padding: var(--spacing-3xl) 0; margin-bottom: var(--spacing-3xl);">
    <div class="container">
        <div style="max-width: 800px; margin: 0 auto; text-align: center;">
            <h1 style="color: white; font-size: 3rem; margin-bottom: var(--spacing-lg);">Experience Cinema Like Never Before</h1>
            <p style="color: rgba(255,255,255,0.9); font-size: var(--font-size-lg); margin-bottom: var(--spacing-xl);">Book your favorite movies, choose your perfect seats, and enjoy a seamless booking experience with Ticketly.</p>
            <a href="#movies" class="btn" style="background: white; color: var(--primary-700); padding: var(--spacing-md) var(--spacing-xl); font-size: var(--font-size-lg);">Browse Movies →</a>
        </div>
    </div>
</section>

<div class="container">
    <!-- Search and Filter Bar -->
    <section class="card" style="margin-top: -4rem; margin-bottom: var(--spacing-2xl); position: relative; z-index: 10;">
        <div class="card-body">
            <form method="GET" style="display: grid; grid-template-columns: 1fr auto auto auto; gap: var(--spacing-md); align-items: flex-end;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Search Movies</label>
                    <input type="text" name="search" class="form-input" placeholder="Search by title or description..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Genre</label>
                    <select name="genre" class="form-select" style="min-width: 150px;">
                        <option value="">All Genres</option>
                        <?php foreach($uniqueGenres as $g): ?>
                            <option value="<?= htmlspecialchars($g) ?>" <?= $filter_genre == $g ? 'selected' : '' ?>><?= htmlspecialchars($g) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Language</label>
                    <select name="language" class="form-select" style="min-width: 150px;">
                        <option value="">All Languages</option>
                        <?php foreach($allLanguagesRaw as $l): ?>
                            <option value="<?= htmlspecialchars($l['language']) ?>" <?= $filter_language == $l['language'] ? 'selected' : '' ?>><?= htmlspecialchars($l['language']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="height: 42px; padding: 0 var(--spacing-lg);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Search
                </button>
            </form>
            <?php if(!empty($search) || !empty($filter_genre) || !empty($filter_language)): ?>
                <div style="margin-top: var(--spacing-md); font-size: var(--font-size-sm); color: var(--gray-500);">
                    Found <strong><?= $totalResults ?></strong> results. <a href="index.php" style="color: var(--primary-600); text-decoration: none; margin-left: 8px;">Clear all filters</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php 
    $isSearching = !empty($search) || !empty($filter_genre) || !empty($filter_language);
    
    // Function to render movie sections
    $renderMovies = function($movies, $title, $showUpcomingBadge = false, $isRec = false) {
        if (empty($movies)) return '';
        ob_start();
        ?>
        <section id="<?= $isRec ? 'recommended' : 'movies' ?>" style="margin-bottom: var(--spacing-3xl);">
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: var(--spacing-xl);">
                <h2><?= $title ?></h2>
                <?php if ($isRec): ?>
                    <span style="color: var(--primary-600); font-size: var(--font-size-sm); font-weight: 600;">
                        <?= isLoggedIn() ? 'Based on your preferences' : 'Curated for this time of day' ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="movie-grid">
                <?php foreach ($movies as $movie): ?>
                    <div class="movie-card">
                        <div class="movie-poster-wrapper">
                            <img src="<?= getMoviePoster($movie['poster']) ?>" class="movie-poster" alt="<?= htmlspecialchars($movie['title']) ?>">
                            <?php if ($showUpcomingBadge && $movie['upcoming_showtimes'] == 0): ?>
                                <div style="position: absolute; top: var(--spacing-md); right: var(--spacing-md); background: var(--gray-900); color: white; padding: 0.25rem 0.75rem; border-radius: var(--radius-sm); font-size: var(--font-size-xs); font-weight: 600;">Coming Soon</div>
                            <?php endif; ?>
                        </div>
                        <div class="movie-info">
                            <h3 class="movie-title"><?= htmlspecialchars($movie['title']) ?></h3>
                            <div class="movie-meta">
                                <?php 
                                $genres = explode(',', $movie['genre']);
                                $limit = $isRec ? 2 : count($genres);
                                foreach(array_slice($genres, 0, $limit) as $genre): ?>
                                    <span class="badge"><?= htmlspecialchars(trim($genre)) ?></span>
                                <?php endforeach; ?>
                                <span class="badge"><?= htmlspecialchars($movie['language']) ?></span>
                            </div>
                            <div class="movie-details">
                                <span><?= $movie['duration'] ?> min</span>
                                <span>•</span>
                                <span><?= date('M d, Y', strtotime($movie['release_date'])) ?></span>
                            </div>
                            <div class="movie-price">
                                <span class="price">From NPR 250</span>
                                <a href="movie.php?id=<?= $movie['movie_id'] ?>" class="btn btn-primary" style="<?= $isRec ? 'padding: 0.5rem 1rem;' : '' ?>">Book Now</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    };

    $allMoviesTitle = $isSearching ? "Search Results" : "Now Showing";
    $recTitle = isLoggedIn() ? 'Recommended For You' : 'Trending Now';

    $allMoviesHtml = $renderMovies($moviesRaw, $allMoviesTitle, true, false);
    $recHtml = $renderMovies($recommendedMovies, $recTitle, false, true);

    if ($isSearching) {
        echo $allMoviesHtml;
        renderPagination($page, $totalPages, $search, $filter_genre, $filter_language);
        echo $recHtml;
    } else {
        echo $recHtml;
        echo $allMoviesHtml;
        renderPagination($page, $totalPages, $search, $filter_genre, $filter_language);
    }

    function renderPagination($currentPage, $totalPages, $search, $genre, $language) {
        if ($totalPages <= 1) return;
        
        $queryParams = [];
        if ($search) $queryParams['search'] = $search;
        if ($genre) $queryParams['genre'] = $genre;
        if ($language) $queryParams['language'] = $language;
        
        $buildUrl = function($p) use ($queryParams) {
            $params = $queryParams;
            $params['page'] = $p;
            return '?' . http_build_query($params);
        };

        echo '<div class="pagination" style="display: flex; justify-content: center; gap: 0.5rem; margin-top: -1rem; margin-bottom: var(--spacing-3xl);">';
        
        // Previous Button
        if ($currentPage > 1) {
            echo '<a href="' . $buildUrl($currentPage - 1) . '" class="btn btn-outline" style="padding: 0.5rem 1rem;">&laquo; Previous</a>';
        }

        // Page Numbers
        for ($i = 1; $i <= $totalPages; $i++) {
            $activeStyle = ($i == $currentPage) ? 'background: var(--primary-600); color: white; border-color: var(--primary-600);' : '';
            echo '<a href="' . $buildUrl($i) . '" class="btn btn-outline" style="padding: 0.5rem 1rem; ' . $activeStyle . '">' . $i . '</a>';
        }

        // Next Button
        if ($currentPage < $totalPages) {
            echo '<a href="' . $buildUrl($currentPage + 1) . '" class="btn btn-outline" style="padding: 0.5rem 1rem;">Next &raquo;</a>';
        }

        echo '</div>';
    }
    ?>
</div>

<?php include 'includes/footer.php'; ?>
