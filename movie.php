<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/recommendation.php';

$movie_id = intval($_GET['id'] ?? 0);
$showtime_id = intval($_GET['showtime'] ?? 0);

$movieStmt = $pdo->prepare("SELECT * FROM movies WHERE movie_id = ?");
$movieStmt->execute([$movie_id]);
$movie = $movieStmt->fetch();

if(!$movie) {
    die('Movie not found');
}

$showStmt = $pdo->prepare("
    SELECT * FROM showtimes 
    WHERE movie_id = ? AND show_date >= CURDATE()
    ORDER BY show_date, show_time
");
$showStmt->execute([$movie_id]);
$showtimes = $showStmt->fetchAll();

if(!$showtime_id && !empty($showtimes)) {
    $showtime_id = $showtimes[0]['showtime_id'];
}

$price = 250.00;
if($showtime_id) {
    $priceStmt = $pdo->prepare("SELECT base_price FROM showtimes WHERE showtime_id = ?");
    $priceStmt->execute([$showtime_id]);
    $price = $priceStmt->fetchColumn() ?: 250.00;
}

$recommendedMovies = getRecommendedMovies($pdo, currentUserId(), 5, ['movie_id' => $movie_id]);

include 'includes/header.php';
?>

<div class="container" style="padding: var(--spacing-xl) 0;">
    <!-- Movie Detail Section -->
    <div class="movie-detail">
        <div class="movie-detail-header">
            <div class="movie-detail-poster">
                <img src="<?= getMoviePoster($movie['poster']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
            </div>
            <div class="movie-detail-info">
                <h1 class="movie-detail-title"><?= htmlspecialchars($movie['title']) ?></h1>
                <div class="movie-detail-meta">
                    <?php 
                    $genres = explode(',', $movie['genre']);
                    foreach($genres as $genre): ?>
                        <span class="badge badge-primary"><?= htmlspecialchars(trim($genre)) ?></span>
                    <?php endforeach; ?>
                    <span class="badge"><?= htmlspecialchars($movie['language']) ?></span>
                    <span class="badge"><?= $movie['duration'] ?> minutes</span>
                </div>
                <div class="movie-detail-description">
                    <p><?= nl2br(htmlspecialchars($movie['description'])) ?></p>
                </div>
                <div class="movie-detail-grid">
                    <div class="movie-detail-item">
                        <div class="movie-detail-label">Release Date</div>
                        <div class="movie-detail-value"><?= date('F d, Y', strtotime($movie['release_date'])) ?></div>
                    </div>
                    <div class="movie-detail-item">
                        <div class="movie-detail-label">Status</div>
                        <div class="movie-detail-value" style="color: <?= !empty($showtimes) ? 'var(--success)' : 'var(--warning)' ?>;">
                            <?= !empty($showtimes) ? 'Now Showing' : 'Coming Soon' ?>
                        </div>
                    </div>
                    <div class="movie-detail-item">
                        <div class="movie-detail-label">Starting From</div>
                        <div class="movie-detail-value" style="color: var(--primary-600);">NPR <?= number_format($price, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Section -->
    <?php if(empty($showtimes)): ?>
        <div class="card" style="text-align: center; padding: var(--spacing-3xl);">
            <div style="margin-bottom: var(--spacing-lg);">
                <svg style="width: 64px; height: 64px; color: var(--gray-400);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <h3>No Showtimes Scheduled</h3>
            <p class="text-muted" style="margin-bottom: var(--spacing-lg);">We're currently finalizing the screening schedule for this movie.</p>
            <a href="index.php" class="btn btn-primary">Browse Other Movies</a>
        </div>
    <?php else: ?>
        <!-- Showtime Selection -->
        <div class="card" style="margin-bottom: var(--spacing-xl);">
            <div class="card-header" style="display: flex; align-items: center; gap: var(--spacing-sm);">
                <svg style="width: 20px; height: 20px; color: var(--primary-600);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 style="margin: 0;">Select Showtime</h3>
            </div>
            <div class="card-body">
                <?php 
                $groupedShowtimes = [];
                foreach($showtimes as $st) {
                    $date = $st['show_date'];
                    if(!isset($groupedShowtimes[$date])) $groupedShowtimes[$date] = [];
                    $groupedShowtimes[$date][] = $st;
                }
                ?>
                
                <div style="display: flex; flex-direction: column; gap: var(--spacing-xl);">
                    <?php foreach($groupedShowtimes as $date => $times): ?>
                        <div>
                            <div style="font-size: var(--font-size-sm); font-weight: 700; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: var(--spacing-md); display: flex; align-items: center; gap: var(--spacing-sm);">
                                <span><?= date('l, M d, Y', strtotime($date)) ?></span>
                                <div style="flex: 1; height: 1px; background: var(--gray-100);"></div>
                            </div>
                            <div style="display: flex; gap: var(--spacing-md); flex-wrap: wrap;">
                                <?php foreach($times as $st): ?>
                                    <a href="?id=<?= $movie_id ?>&showtime=<?= $st['showtime_id'] ?>" 
                                       class="btn <?= $st['showtime_id'] == $showtime_id ? 'btn-primary' : 'btn-outline' ?>"
                                       style="min-width: 120px; flex-direction: column; padding: var(--spacing-md);">
                                        <span style="font-size: var(--font-size-lg); font-weight: 700;"><?= date('h:i A', strtotime($st['show_time'])) ?></span>
                                        <span style="font-size: 10px; opacity: 0.8;">Screen <?= htmlspecialchars($st['screen']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Seat Map Section -->
        <div style="margin-bottom: var(--spacing-xl);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg);">
                <h3 style="display: flex; align-items: center; gap: var(--spacing-sm);">
                    <svg style="width: 20px; height: 20px; color: var(--primary-600);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                    </svg>
                    Choose Your Seats
                </h3>
                <div style="font-size: var(--font-size-sm); color: var(--gray-600);">
                    <span id="seatsLeftCount">--</span> seats available
                </div>
            </div>
            
            <div class="seat-map-container">
                <div class="screen">
                    <div class="screen-line"></div>
                    SCREEN THIS WAY
                </div>
                
                <div id="seatMap" class="seat-map">
                    <div style="grid-column: 1 / -1; text-align: center; padding: var(--spacing-2xl); color: white;">Loading seats...</div>
                </div>
                
                <div class="legend" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
                    <div class="legend-item">
                        <div class="legend-color" style="background: white;"></div>
                        <span style="color: white;">Available</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: var(--primary-600);"></div>
                        <span style="color: white;">Selected</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: var(--warning);"></div>
                        <span style="color: white;">Reserved</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: var(--gray-600);"></div>
                        <span style="color: white;">Sold Out</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking Form -->
        <form method="post" action="ajax/booking_process.php" id="bookingForm">
            <input type="hidden" name="movie_id" value="<?= $movie['movie_id'] ?>">
            <input type="hidden" name="showtime_id" value="<?= $showtime_id ?>">
            <input type="hidden" name="selected_seats" id="selectedSeatsInput">
        </form>

        <!-- Booking Summary Bar -->
        <div id="bookingSummary" class="booking-summary">
            <div class="container">
                <div class="booking-summary-content">
                    <div style="display: flex; gap: var(--spacing-xl); align-items: center;">
                        <div id="reservationTimer" style="background: var(--warning); color: white; padding: var(--spacing-sm) var(--spacing-md); border-radius: var(--radius-md); font-weight: 700; display: none; align-items: center; gap: var(--spacing-sm); border: 2px solid rgba(0,0,0,0.1);">
                            <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span id="timerValue">02:00</span>
                        </div>
                        <div>
                            <div class="booking-summary-seats">SELECTED SEATS</div>
                            <div class="booking-summary-seats-value" id="selectedSeatsDisplay">None</div>
                            <div id="selectedCount" class="text-muted" style="font-size: var(--font-size-sm);">0 seats</div>
                        </div>
                    </div>
                    <div>
                        <div class="booking-summary-price">TOTAL AMOUNT</div>
                        <div class="booking-summary-price-value">NPR <span id="totalAmount">0</span></div>
                    </div>
                    <button id="bookNowBtn" class="btn btn-primary" style="padding: var(--spacing-md) var(--spacing-xl); font-weight: 700;">
                        Continue to Payment
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recommendations -->
    <?php if (!empty($recommendedMovies)): ?>
        <section style="margin-top: var(--spacing-3xl);">
            <h2 style="margin-bottom: var(--spacing-xl);">You Might Also Like</h2>
            <div class="movie-grid">
                <?php foreach ($recommendedMovies as $recMovie): ?>
                    <?php if($recMovie['movie_id'] == $movie_id) continue; ?>
                    <div class="movie-card">
                        <div class="movie-poster-wrapper">
                            <img src="<?= getMoviePoster($recMovie['poster']) ?>" class="movie-poster" alt="<?= htmlspecialchars($recMovie['title']) ?>">
                        </div>
                        <div class="movie-info">
                            <h3 class="movie-title"><?= htmlspecialchars($recMovie['title']) ?></h3>
                            <div class="movie-meta">
                                <?php 
                                $genres = explode(',', $recMovie['genre']);
                                foreach(array_slice($genres, 0, 2) as $genre): ?>
                                    <span class="badge"><?= htmlspecialchars(trim($genre)) ?></span>
                                <?php endforeach; ?>
                                <span class="badge"><?= htmlspecialchars($recMovie['language']) ?></span>
                            </div>
                            <div class="movie-price">
                                <span class="price">From NPR 250</span>
                                <a href="movie.php?id=<?= $recMovie['movie_id'] ?>" class="btn btn-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<script>
const showtime_id = <?= $showtime_id ?>;
const customer_id = <?= json_encode(currentUserId()) ?>;
const price = <?= (float)$price ?>;

const seatMap = document.getElementById('seatMap');
const bookingForm = document.getElementById('bookingForm');
const seatsInput = document.getElementById('selectedSeatsInput');
const totalAmountEl = document.getElementById('totalAmount');
const selectedSeatsDisplay = document.getElementById('selectedSeatsDisplay');
const selectedCountEl = document.getElementById('selectedCount');
const bookingSummary = document.getElementById('bookingSummary');

let selectedSeats = new Set();
let reservationExpiry = null;
let timerInterval = null;

async function fetchSeats() {
    try {
        const res = await fetch(`ajax/fetch_seats.php?showtime_id=${showtime_id}`);
        const data = await res.json();
        
        // Check if any of our selected seats are no longer reserved by us
        let seatsChanged = false;
        let myReservations = data.filter(s => 
            s.status === 'reserved' && parseInt(s.reserved_by_customer_id) === parseInt(customer_id)
        );
        
        let myReservedLabels = myReservations.map(s => s.seat_label);
        
        selectedSeats.forEach(label => {
            if (!myReservedLabels.includes(label)) {
                selectedSeats.delete(label);
                seatsChanged = true;
            }
        });

        // Update expiry time from the most recent reservation
        if (myReservations.length > 0) {
            const minSecondsLeft = Math.min(...myReservations.map(s => s.seconds_left));
            reservationExpiry = new Date().getTime() + (minSecondsLeft * 1000);
            startTimer();
        } else {
            reservationExpiry = null;
            stopTimer();
        }

        renderSeats(data);
        if (seatsChanged) updateBookingUI();
    } catch (e) {
        console.error('Error fetching seats:', e);
    }
}

function startTimer() {
    if (timerInterval) return;
    document.getElementById('reservationTimer').style.display = 'flex';
    
    timerInterval = setInterval(() => {
        const now = new Date().getTime();
        const distance = reservationExpiry - now;

        if (distance <= 0) {
            stopTimer();
            fetchSeats();
            showNotification('Reservation expired', 'warning');
            return;
        }

        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        document.getElementById('timerValue').textContent = 
            `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        
        if (distance < 30000) { // Red timer for last 30s
            document.getElementById('reservationTimer').style.background = 'var(--error)';
        } else {
            document.getElementById('reservationTimer').style.background = 'var(--warning)';
        }
    }, 1000);
}

function stopTimer() {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
    document.getElementById('reservationTimer').style.display = 'none';
}

function renderSeats(seats) {
    seatMap.innerHTML = '';
    let availableCount = 0;
    
    seats.forEach(s => {
        const div = document.createElement('div');
        div.className = `seat ${s.status}`;
        div.dataset.label = s.seat_label;
        div.textContent = s.seat_label;

        if (s.status === 'available') availableCount++;

        if (selectedSeats.has(s.seat_label)) {
            div.classList.add('selected');
        }

        if (s.status === 'reserved' && parseInt(s.reserved_by_customer_id) === parseInt(customer_id)) {
            div.classList.remove('reserved');
            if (selectedSeats.has(s.seat_label)) {
                div.classList.add('selected');
            } else {
                div.classList.add('available');
                availableCount++;
            }
        }

        if (div.classList.contains('available')) {
            div.onclick = () => toggleSeat(s.seat_label, div);
        }

        seatMap.appendChild(div);
    });

    document.getElementById('seatsLeftCount').textContent = availableCount;
}

async function toggleSeat(label, el) {
    if (!customer_id) {
        if (confirm('Please login to book seats. Go to login page?')) {
            window.location.href = 'login.php';
        }
        return;
    }

    el.classList.add('loading-seat');
    
    if (selectedSeats.has(label)) {
        const success = await releaseSeat(label);
        if (success) {
            selectedSeats.delete(label);
            el.classList.remove('selected');
            // showNotification(`Seat ${label} released`, 'info');
        }
    } else {
        const success = await reserveSeat(label);
        if (success) {
            selectedSeats.add(label);
            el.classList.add('selected');
            // showNotification(`Seat ${label} reserved`, 'success');
        }
    }
    
    el.classList.remove('loading-seat');
    await fetchSeats();
    updateBookingUI();
}

async function reserveSeat(label) {
    const formData = new FormData();
    formData.append('showtime_id', showtime_id);
    formData.append('seat_label', label);

    const res = await fetch('ajax/reserve_seat.php', {
        method: 'POST',
        body: formData
    });
    const data = await res.json();

    if (!data.success) {
        alert(data.message || 'Could not reserve seat.');
        fetchSeats();
        return false;
    }
    return true;
}

async function releaseSeat(label) {
    const formData = new FormData();
    formData.append('showtime_id', showtime_id);
    formData.append('seat_label', label);

    const res = await fetch('ajax/release_seat.php', {
        method: 'POST',
        body: formData
    });
    const data = await res.json();
    return data.success;
}

function updateBookingUI() {
    if (selectedSeats.size > 0) {
        bookingSummary.classList.add('active');
        seatsInput.value = Array.from(selectedSeats).join(',');
        selectedSeatsDisplay.textContent = Array.from(selectedSeats).join(', ');
        selectedCountEl.textContent = selectedSeats.size + (selectedSeats.size === 1 ? ' seat' : ' seats');
        totalAmountEl.textContent = (selectedSeats.size * price).toFixed(2);
    } else {
        bookingSummary.classList.remove('active');
        seatsInput.value = '';
    }
}

function showNotification(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type}`;
    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.zIndex = '1000';
    toast.style.minWidth = '250px';
    toast.style.animation = 'slideIn 0.3s ease';
    toast.innerHTML = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

document.getElementById('bookNowBtn')?.addEventListener('click', function() {
    if (selectedSeats.size === 0) {
        alert('Please select at least one seat');
        return;
    }
    bookingForm.submit();
});

fetchSeats();
setInterval(fetchSeats, 5000);
</script>

<style>
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
@keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}
@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(0.95); opacity: 0.7; }
    100% { transform: scale(1); opacity: 1; }
}
.loading-seat {
    animation: pulse 1s infinite ease-in-out;
    pointer-events: none;
    opacity: 0.7;
}
.seat-map-container {
    background: #111827;
    border: 1px solid #374151;
    box-shadow: inset 0 4px 20px rgba(0,0,0,0.5);
}
.seat.available {
    background: #374151;
    border-color: #4b5563;
    color: #9ca3af;
}
.seat.available:hover {
    background: #4b5563;
    color: white;
    border-color: var(--primary-400);
}
.seat.booked {
    background: #1f2937;
    color: #4b5563;
    border-color: #111827;
}
</style>

<?php include 'includes/footer.php'; ?>