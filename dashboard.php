<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if(!isLoggedIn()) header('Location: login.php');

if (isAdminLoggedIn()) {
    $_SESSION['error'] = "Admin cannot access customer dashboard. Please use a customer account.";
    header("Location: admin/dashboard.php");
    exit;
}

$uid = currentUserId();
$res = $pdo->prepare("SELECT b.*, m.title, m.poster, s.show_date, s.show_time, s.screen 
                      FROM bookings b 
                      JOIN showtimes s ON b.showtime_id = s.showtime_id 
                      JOIN movies m ON s.movie_id = m.movie_id 
                      WHERE b.customer_id = ? 
                      ORDER BY b.created_at DESC");
$res->execute([$uid]);
$rows = $res->fetchAll();

$page_title = 'My Bookings';
include 'includes/header.php';
?>

<div class="container" style="padding: var(--spacing-2xl) 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1 style="font-size: var(--font-size-3xl); font-weight: 800; color: var(--gray-900);">My Bookings</h1>
            <p class="text-muted">Manage and view your movie tickets</p>
        </div>
        <div style="background: white; padding: var(--spacing-md) var(--spacing-lg); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200);">
            <div style="font-size: var(--font-size-xs); color: var(--gray-500); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Total Bookings</div>
            <div style="font-size: var(--font-size-2xl); font-weight: 800; color: var(--primary-600);"><?= count($rows) ?></div>
        </div>
    </div>

    <!-- Movie Search Bar for Dashboard -->
    <div class="card" style="margin-bottom: var(--spacing-xl); padding: var(--spacing-lg);">
        <form action="index.php" method="GET" style="display: flex; gap: var(--spacing-md); align-items: center;">
            <div style="flex: 1;">
                <h4 style="margin-bottom: var(--spacing-xs);">Find New Movies</h4>
                <div style="display: flex; gap: var(--spacing-sm);">
                    <input type="text" name="search" class="form-input" placeholder="Search by name, genre..." style="flex: 1;">
                    <button type="submit" class="btn btn-primary">Search Catalog</button>
                </div>
            </div>
            <div style="padding-left: var(--spacing-xl); border-left: 1px solid var(--gray-200); display: flex; flex-direction: column; gap: var(--spacing-xs);">
                <span style="font-size: var(--font-size-xs); font-weight: 700; color: var(--gray-500);">QUICK FILTERS</span>
                <div style="display: flex; gap: var(--spacing-sm);">
                    <a href="index.php?genre=Action" class="badge badge-primary" style="text-decoration: none;">Action</a>
                    <a href="index.php?genre=Romance" class="badge badge-primary" style="text-decoration: none;">Romance</a>
                    <a href="index.php?genre=Thriller" class="badge badge-primary" style="text-decoration: none;">Thriller</a>
                </div>
            </div>
        </form>
    </div>

    <?php if(empty($rows)): ?>
        <div class="card" style="text-align: center; padding: var(--spacing-3xl); max-width: 600px; margin: 0 auto;">
            <div style="background: var(--primary-50); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--spacing-xl);">
                <svg style="width: 40px; height: 40px; color: var(--primary-500);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                </svg>
            </div>
            <h2 style="margin-bottom: var(--spacing-md);">No tickets booked yet</h2>
            <p class="text-muted" style="margin-bottom: var(--spacing-xl);">You haven't made any bookings. Explore the latest movies and book your perfect seat now!</p>
            <a href="index.php" class="btn btn-primary" style="padding: var(--spacing-md) var(--spacing-2xl);">Browse Movies</a>
        </div>
    <?php else: ?>
        <div style="display: grid; gap: var(--spacing-lg);">
            <?php foreach($rows as $r): ?>
                <div class="card booking-card" style="display: grid; grid-template-columns: 150px 1fr auto; align-items: center;">
                    <div style="height: 100%; border-right: 1px solid var(--gray-100);">
                        <img src="<?= getMoviePoster($r['poster']) ?>" alt="<?= htmlspecialchars($r['title']) ?>" style="width: 100%; height: 200px; object-fit: cover;">
                    </div>
                    
                    <div style="padding: var(--spacing-lg);">
                        <div style="display: flex; align-items: center; gap: var(--spacing-md); margin-bottom: var(--spacing-sm);">
                            <h3 style="font-size: var(--font-size-xl); font-weight: 700;"><?= htmlspecialchars($r['title']) ?></h3>
                            <?php 
                            $status_class = 'badge-primary';
                            if($r['status'] == 'completed') $status_class = 'badge-success';
                            if($r['status'] == 'cancelled') $status_class = 'badge-danger';
                            ?>
                            <span class="badge <?= $status_class ?>" style="text-transform: capitalize;"><?= htmlspecialchars($r['status']) ?></span>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: var(--spacing-lg);">
                            <div style="display: flex; align-items: center; gap: var(--spacing-sm);">
                                <div style="color: var(--gray-400);">
                                    <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div style="font-size: 10px; color: var(--gray-500); font-weight: 700; text-transform: uppercase;">Show Date</div>
                                    <div style="font-weight: 600;"><?= date('D, M d, Y', strtotime($r['show_date'])) ?></div>
                                </div>
                            </div>
                            
                            <div style="display: flex; align-items: center; gap: var(--spacing-sm);">
                                <div style="color: var(--gray-400);">
                                    <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div style="font-size: 10px; color: var(--gray-500); font-weight: 700; text-transform: uppercase;">Show Time</div>
                                    <div style="font-weight: 600;"><?= date('h:i A', strtotime($r['show_time'])) ?></div>
                                </div>
                            </div>
                            
                            <div style="display: flex; align-items: center; gap: var(--spacing-sm);">
                                <div style="color: var(--gray-400);">
                                    <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div style="font-size: 10px; color: var(--gray-500); font-weight: 700; text-transform: uppercase;">Seat(s)</div>
                                    <div style="font-weight: 600; color: var(--primary-600);"><?= htmlspecialchars($r['seat_label']) ?></div>
                                </div>
                            </div>
                            
                            <div style="display: flex; align-items: center; gap: var(--spacing-sm);">
                                <div style="color: var(--gray-400);">
                                    <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div style="font-size: 10px; color: var(--gray-500); font-weight: 700; text-transform: uppercase;">Amount</div>
                                    <div style="font-weight: 600;">NPR <?= number_format($r['amount'], 2) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="padding: var(--spacing-lg); border-left: 1px solid var(--gray-100); display: flex; flex-direction: column; gap: var(--spacing-sm);">
                        <a href="success.php?booking_id=<?= $r['booking_id'] ?>" class="btn btn-outline" style="white-space: nowrap;">
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            View Ticket
                        </a>
                        <button class="btn btn-secondary" onclick="window.print()" style="white-space: nowrap;">
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                            </svg>
                            Print
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.booking-card {
    transition: all 0.3s ease;
    border: 1px solid var(--gray-200);
}
.booking-card:hover {
    transform: translateX(5px);
    border-color: var(--primary-300);
}
.badge-success { background: #d1fae5; color: #065f46; }
.badge-danger { background: #fee2e2; color: #991b1b; }

@media (max-width: 992px) {
    .booking-card {
        grid-template-columns: 1fr !important;
    }
    .booking-card div:first-child {
        border-right: none !important;
        border-bottom: 1px solid var(--gray-100);
    }
    .booking-card div:first-child img {
        height: 250px !important;
    }
    .booking-card div:last-child {
        border-left: none !important;
        border-top: 1px solid var(--gray-100);
        flex-direction: row !important;
    }
}
</style>

<?php
include 'includes/footer.php';
?>
