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

$isSearching = !empty($search) || !empty($filter_genre) || !empty($filter_language);

// 1. Logic for Now Showing / Search Results
$params = [];
$conditions = [];

if (!empty($search)) {
    // Search ONLY in movie title, case-insensitive
    $conditions[] = "LOWER(m.title) LIKE LOWER(?)";
    $params[] = "%$search%";
}

if (!empty($filter_genre)) {
    // Genre filter - using exact match as requested
    $conditions[] = "m.genre LIKE ?";
    $params[] = "%$filter_genre%";
}

if (!empty($filter_language)) {
    // Language filter - using exact match as requested
    $conditions[] = "m.language = ?";
    $params[] = $filter_language;
}

// Base condition: Show anything released today or before.
$whereSql = "WHERE m.release_date IS NOT NULL AND m.release_date <= CURDATE()";

if (!empty($conditions)) {
    $whereSql .= " AND " . implode(" AND ", $conditions);
}

// Count query for pagination
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM movies m $whereSql");
$stmtCount->execute($params);
$totalResults = $stmtCount->fetchColumn();
$totalPages = ceil($totalResults / $limit);

// Build main query
$query = "SELECT m.*, 
          (SELECT COUNT(*) FROM showtimes s WHERE s.movie_id = m.movie_id AND s.show_date >= CURDATE()) as upcoming_showtimes
          FROM movies m 
          $whereSql
          ORDER BY m.release_date DESC, m.title ASC
          LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$moviesRaw = $stmt->fetchAll();

// 2. Logic for Upcoming Shows (only on home page first page, and only if not searching)
$upcomingMovies = [];
if (!$isSearching && $page == 1) {
    $stmtUp = $pdo->query("SELECT * FROM movies m 
                           WHERE m.release_date IS NOT NULL AND m.release_date > CURDATE() 
                           ORDER BY m.release_date ASC 
                           LIMIT 4");
    $upcomingMovies = $stmtUp->fetchAll();
}

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

// Function to render movie sections
$renderMovies = function($movies, $title, $isRec = false, $isUpcomingSection = false) {
    $today = date('Y-m-d');
    ob_start();
    ?>
    <section id="<?= $isRec ? 'recommended' : ($isUpcomingSection ? 'upcoming' : 'movies') ?>" style="margin-bottom: var(--spacing-3xl);">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: var(--spacing-xl);">
            <h2><?= $title ?></h2>
            <?php if ($isRec): ?>
                <span style="color: var(--primary-600); font-size: var(--font-size-sm); font-weight: 600;">
                    <?= isLoggedIn() ? 'Based on your preferences' : 'Curated for this time of day' ?>
                </span>
            <?php endif; ?>
        </div>

        <?php if (empty($movies)): ?>
            <div class="card" style="text-align: center; padding: var(--spacing-2xl); background: var(--gray-50); border: 1px dashed var(--gray-200);">
                <p class="text-muted" style="margin: 0;">No <?= strtolower($title) ?> available at the moment.</p>
            </div>
        <?php else: ?>
            <div class="movie-grid">
                <?php foreach ($movies as $movie): ?>
                    <?php $isUpcoming = ($movie['release_date'] > $today); ?>
                    <div class="movie-card">
                        <div class="movie-poster-wrapper">
                            <img src="<?= getMoviePoster($movie['poster']) ?>" class="movie-poster" alt="<?= htmlspecialchars($movie['title']) ?>">
                            <?php if ($isUpcoming || $isUpcomingSection): ?>
                                <div style="position: absolute; top: var(--spacing-md); right: var(--spacing-md); background: var(--warning); color: white; padding: 0.25rem 0.75rem; border-radius: var(--radius-sm); font-size: var(--font-size-xs); font-weight: 600;">Coming Soon</div>
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

                            <?php if ($isUpcomingSection || $isUpcoming): ?>
                                <!-- Upcoming movies show ONLY requested info and label -->
                            <?php else: ?>
                                <!-- Now Showing movies show Booking, Showtimes, and Seat Selection UI -->
                                <div style="margin-top: var(--spacing-md); padding-top: var(--spacing-md); border-top: 1px solid var(--gray-100);">
                                    <div style="display: flex; align-items: center; gap: var(--spacing-xs); color: var(--success); font-size: 0.75rem; font-weight: 600; margin-bottom: var(--spacing-sm);">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Showtimes & Seats Available
                                    </div>
                                    <div class="movie-price">
                                        <span class="price">NPR 250</span>
                                        <a href="movie.php?id=<?= $movie['movie_id'] ?>" class="btn btn-primary" style="padding: 0.5rem 1rem;">Book Ticket</a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php
    return ob_get_clean();
};

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
    
    if ($currentPage > 1) {
        echo '<a href="' . $buildUrl($currentPage - 1) . '" class="btn btn-outline" style="padding: 0.5rem 1rem;">&laquo; Previous</a>';
    }

    for ($i = 1; $i <= $totalPages; $i++) {
        $activeStyle = ($i == $currentPage) ? 'background: var(--primary-600); color: white; border-color: var(--primary-600);' : '';
        echo '<a href="' . $buildUrl($i) . '" class="btn btn-outline" style="padding: 0.5rem 1rem; ' . $activeStyle . '">' . $i . '</a>';
    }

    if ($currentPage < $totalPages) {
        echo '<a href="' . $buildUrl($currentPage + 1) . '" class="btn btn-outline" style="padding: 0.5rem 1rem;">Next &raquo;</a>';
    }

    echo '</div>';
}

$allMoviesTitle = $isSearching ? "Search Results" : "Now Showing";
$recTitle = isLoggedIn() ? 'Recommended For You' : 'Trending Now';

$recHtml = $renderMovies($recommendedMovies, $recTitle, true);
$allMoviesHtml = $renderMovies($moviesRaw, $allMoviesTitle, false);
$upcomingHtml = $renderMovies($upcomingMovies, "Upcoming Shows", false, true);

// AJAX response for live search
if (isset($_GET['ajax'])) {
    if (empty($moviesRaw) && $isSearching) {
        echo '<div class="card" style="text-align: center; padding: var(--spacing-3xl); grid-column: 1 / -1;">
                <div style="margin-bottom: var(--spacing-lg);">
                    <svg style="width: 64px; height: 64px; color: var(--gray-400);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <h3>No movies found</h3>
                <p class="text-muted">We couldn\'t find any movies matching "<strong>' . htmlspecialchars($search) . '</strong>".</p>
                <a href="index.php" class="btn btn-outline" style="margin-top: var(--spacing-lg);">Clear Search</a>
              </div>';
    } else {
        if ($isSearching) {
            // ONLY show search results, no recommendations or upcoming
            echo $allMoviesHtml;
            renderPagination($page, $totalPages, $search, $filter_genre, $filter_language);
        } else {
            // Order: Now Showing -> Recommended -> Upcoming
            echo $allMoviesHtml;
            echo $recHtml;
            if ($page == 1) echo $upcomingHtml;
            renderPagination($page, $totalPages, $search, $filter_genre, $filter_language);
        }
    }
    exit;
}

include 'includes/header.php';
?>

<!-- Hero Section -->
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
                    <input type="text" name="search" id="searchInput" class="form-input" placeholder="Search by title or description..." value="<?= htmlspecialchars($search) ?>" autocomplete="off">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Genre</label>
                    <select name="genre" id="genreFilter" class="form-select" style="min-width: 150px;">
                        <option value="">All Genres</option>
                        <?php foreach($uniqueGenres as $g): ?>
                            <option value="<?= htmlspecialchars($g) ?>" <?= $filter_genre == $g ? 'selected' : '' ?>><?= htmlspecialchars($g) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Language</label>
                    <select name="language" id="languageFilter" class="form-select" style="min-width: 150px;">
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
            <?php if($isSearching): ?>
                <div style="margin-top: var(--spacing-md); font-size: var(--font-size-sm); color: var(--gray-500);">
                    Found <strong><?= $totalResults ?></strong> results. <a href="index.php" style="color: var(--primary-600); text-decoration: none; margin-left: 8px;">Clear all filters</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div id="movieResults">
        <?php 
        if ($isSearching) {
            // ONLY show search results in main view
            echo $allMoviesHtml;
            renderPagination($page, $totalPages, $search, $filter_genre, $filter_language);
        } else {
            // Reordered: Now Showing -> Recommended -> Upcoming
            echo $allMoviesHtml;
            echo $recHtml;
            if ($page == 1) echo $upcomingHtml;
            renderPagination($page, $totalPages, $search, $filter_genre, $filter_language);
        }
        ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
