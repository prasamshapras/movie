<?php
$page_title = 'Manage Movies';

require_once '../includes/config.php';

if (!isAdminLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Delete Movie
|--------------------------------------------------------------------------
| This must stay BEFORE admin_header.php.
| Otherwise header('Location: movies.php') will give:
| Cannot modify header information - headers already sent.
*/
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    if ($id <= 0) {
        header('Location: movies.php');
        exit;
    }

    try {
        $pdo->beginTransaction();

        /*
            Delete related records first:
            1. payments linked to bookings of this movie
            2. bookings of this movie
            3. seats linked to this movie's showtimes
            4. showtimes of this movie
            5. movie itself
        */

        $deletePayments = $pdo->prepare("
            DELETE p
            FROM payments p
            INNER JOIN bookings b ON p.booking_id = b.booking_id
            WHERE b.movie_id = ?
        ");
        $deletePayments->execute([$id]);

        $deleteBookings = $pdo->prepare("
            DELETE FROM bookings
            WHERE movie_id = ?
        ");
        $deleteBookings->execute([$id]);

        $deleteSeats = $pdo->prepare("
            DELETE s
            FROM seats s
            INNER JOIN showtimes st ON s.showtime_id = st.showtime_id
            WHERE st.movie_id = ?
        ");
        $deleteSeats->execute([$id]);

        $deleteShowtimes = $pdo->prepare("
            DELETE FROM showtimes
            WHERE movie_id = ?
        ");
        $deleteShowtimes->execute([$id]);

        $deleteMovie = $pdo->prepare("
            DELETE FROM movies
            WHERE movie_id = ?
        ");
        $deleteMovie->execute([$id]);

        $pdo->commit();

        header('Location: movies.php');
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        die('Delete failed: ' . htmlspecialchars($e->getMessage()));
    }
}

require_once 'includes/admin_header.php';

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_genre = isset($_GET['genre']) ? trim($_GET['genre']) : '';
$filter_language = isset($_GET['language']) ? trim($_GET['language']) : '';

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Build query with filters
$queryBase = "FROM movies WHERE 1=1";
$params = [];

if (!empty($search)) {
    $queryBase .= " AND (title LIKE ? OR description LIKE ? OR genre LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filter_genre)) {
    $queryBase .= " AND genre LIKE ?";
    $params[] = "%$filter_genre%";
}

if (!empty($filter_language)) {
    $queryBase .= " AND language = ?";
    $params[] = $filter_language;
}

// Count total
$totalResults = $pdo->prepare("SELECT COUNT(*) $queryBase");
$totalResults->execute($params);
$totalCount = $totalResults->fetchColumn();
$totalPages = ceil($totalCount / $limit);

$query = "SELECT * $queryBase ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$movies = $stmt->fetchAll();

// Get unique genres and languages for filters
$genres = $pdo->query("SELECT DISTINCT genre FROM movies WHERE genre IS NOT NULL AND genre != ''")->fetchAll();
$languages = $pdo->query("SELECT DISTINCT language FROM movies WHERE language IS NOT NULL AND language != ''")->fetchAll();
?>

<!-- Search Section -->
<div class="search-section">
    <div class="search-header">
        <h3>Search Movies</h3>
    </div>
    <div class="search-body">
        <form method="GET" class="search-form">
            <div class="search-group">
                <label class="search-label">Search by Title, Description or Genre</label>
                <input type="text" name="search" class="search-input" placeholder="Enter keywords..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="search-group">
                <label class="search-label">Filter by Genre</label>
                <select name="genre" class="search-select">
                    <option value="">All Genres</option>
                    <?php 
                    $uniqueGenres = [];
                    foreach($genres as $g): 
                        $genre_list = explode(',', $g['genre']);
                        foreach($genre_list as $genre_item):
                            $genre_item = trim($genre_item);
                            if($genre_item && !in_array($genre_item, $uniqueGenres)):
                                $uniqueGenres[] = $genre_item;
                    ?>
                        <option value="<?= htmlspecialchars($genre_item) ?>" <?= $filter_genre == $genre_item ? 'selected' : '' ?>>
                            <?= htmlspecialchars($genre_item) ?>
                        </option>
                    <?php 
                            endif;
                        endforeach; 
                    endforeach; 
                    ?>
                </select>
            </div>
            <div class="search-group">
                <label class="search-label">Filter by Language</label>
                <select name="language" class="search-select">
                    <option value="">All Languages</option>
                    <?php foreach($languages as $l): ?>
                        <option value="<?= htmlspecialchars($l['language']) ?>" <?= $filter_language == $l['language'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($l['language']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="search-actions">
                <button type="submit" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    Search
                </button>
                <a href="movies.php" class="btn btn-outline">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Results Info -->
<div class="result-info" style="padding: 0.75rem 1rem; background: #f8fafc; border-radius: 0.5rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
    <div>
        <strong><?= $totalCount ?></strong> movie(s) found
        <?php if(!empty($search) || !empty($filter_genre) || !empty($filter_language)): ?>
            <span style="color: #64748b;">with applied filters</span>
            <a href="movies.php" class="clear-filters" style="color: #4f46e5; text-decoration: none; font-size: 0.875rem;">Clear all filters</a>
        <?php endif; ?>
    </div>
    <a href="add_movie.php" class="btn btn-primary btn-sm">+ Add New Movie</a>
</div>

<!-- Movies Table -->
<div class="card">
    <div class="card-header">
        <h3>All Movies</h3>
    </div>
    <div class="card-body">
        <div class="table-container">
            <?php if (empty($movies)): ?>
                <div style="text-align: center; padding: 3rem; color: #64748b;">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom: 1rem;">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <p>No movies found matching your criteria.</p>
                    <a href="movies.php" class="btn btn-outline" style="margin-top: 1rem;">View All Movies</a>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Genre</th>
                            <th>Language</th>
                            <th>Duration</th>
                            <th>Release Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($movies as $m): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($m['title']) ?></strong></td>
                            <td>
                                <?php 
                                $movie_genres = explode(',', $m['genre']);
                                foreach($movie_genres as $g):
                                    $g = trim($g);
                                    if($g):
                                ?>
                                    <span class="badge badge-info" style="margin: 2px;"><?= htmlspecialchars($g) ?></span>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </td>
                            <td><?= htmlspecialchars($m['language']) ?></td>
                            <td><?= $m['duration'] ?> min</td>
                            <td><?= date('M d, Y', strtotime($m['release_date'])) ?></td>
                            <td class="actions" style="display: flex; gap: 0.5rem;">
                                <a href="edit_movie.php?id=<?= $m['movie_id'] ?>" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.75rem; font-size: 0.75rem;">Edit</a>
                                <a href="?delete=<?= $m['movie_id'] ?>" class="btn btn-danger btn-sm" style="padding: 0.25rem 0.75rem; font-size: 0.75rem;" onclick="return confirm('Delete this movie? This will also delete all associated showtimes and bookings.')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <?php if ($totalPages > 1): ?>
            <div class="pagination" style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem;">
                <?php
                $queryParams = $_GET;
                $buildUrl = function($p) use ($queryParams) {
                    $queryParams['page'] = $p;
                    return '?' . http_build_query($queryParams);
                };
                ?>
                
                <?php if ($page > 1): ?>
                    <a href="<?= $buildUrl($page - 1) ?>" class="btn btn-outline btn-sm">&laquo; Previous</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="<?= $buildUrl($i) ?>" class="btn btn-sm <?= $i == $page ? 'btn-primary' : 'btn-outline' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= $buildUrl($page + 1) ?>" class="btn btn-outline btn-sm">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>