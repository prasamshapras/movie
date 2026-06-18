<?php
require_once '../includes/config.php';

if (isAdminLoggedIn()) {
    $_SESSION['error'] = "Admin cannot book tickets. Please use a customer account.";
    header("Location: ../admin/dashboard.php");
    exit;
}

$customer_id = currentUserId();

$movie_id = intval($_GET['id'] ?? 0);
$showtime_id = intval($_GET['showtime'] ?? 0);

$pdo->prepare("
    UPDATE seats
    SET status = 'available',
        reserved_until = NULL,
        reserved_by_customer_id = NULL
    WHERE status = 'reserved'
    AND reserved_until IS NOT NULL
    AND reserved_until < NOW()
")->execute();

$movieStmt = $pdo->prepare("
    SELECT *
    FROM movies
    WHERE movie_id = ?
");
$movieStmt->execute([$movie_id]);
$movie = $movieStmt->fetch();

if (!$movie) {
    die('Movie not found.');
}

$showStmt = $pdo->prepare("
    SELECT *
    FROM showtimes
    WHERE movie_id = ?
    AND show_date >= CURDATE()
    ORDER BY show_date, show_time
");
$showStmt->execute([$movie_id]);
$showtimes = $showStmt->fetchAll();

if (!$showtime_id && !empty($showtimes)) {
    $showtime_id = $showtimes[0]['showtime_id'];
}

$seats = [];
$price = 250;

if ($showtime_id) {
    $seatStmt = $pdo->prepare("
        SELECT *
        FROM seats
        WHERE showtime_id = ?
        ORDER BY LEFT(seat_label,1), CAST(SUBSTRING(seat_label,2) AS UNSIGNED)
    ");
    $seatStmt->execute([$showtime_id]);
    $seats = $seatStmt->fetchAll();

    $priceStmt = $pdo->prepare("
        SELECT base_price
        FROM showtimes
        WHERE showtime_id = ?
    ");
    $priceStmt->execute([$showtime_id]);
    $price = $priceStmt->fetchColumn() ?: 250;
}
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($movie['title']) ?> - Ticketly</title>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 0;
    background: #f5f7fb;
}

.main-card {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin: 20px;
    padding: 20px;
    background: #fff;
    border-radius: 12px;
}

.movie-poster {
    width: 100%;
    border-radius: 10px;
}

.small {
    font-size: 14px;
    color: #666;
    margin-top: 6px;
}

select {
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
}

.seat-map {
    margin-top: 15px;
    padding: 15px;
    background: #fafafa;
    border-radius: 10px;
    display: grid;
    grid-template-columns: repeat(10,45px);
    gap: 10px;
    justify-content: center;
}

.seat {
    width: 45px;
    height: 45px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: bold;
    border: 1px solid #ddd;
    transition: .2s;
}

.seat.available {
    background: #fff;
    color: #111;
    cursor: pointer;
}

.seat.selected {
    background: red !important;
    color: white !important;
    border-color: red !important;
    cursor: pointer;
}

.seat.reserved {
    background: #ffcc00 !important;
    color: #111 !important;
    border-color: #ffcc00 !important;
    cursor: not-allowed;
    pointer-events: none;
}

.seat.booked {
    background: #777 !important;
    color: white !important;
    border-color: #777 !important;
    cursor: not-allowed;
    pointer-events: none;
}

.legend {
    margin-top: 15px;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
}

.legend-box {
    width: 16px;
    height: 16px;
    border-radius: 3px;
}

.btn {
    background: #0b5cff;
    color: white;
    border: none;
    padding: 10px 18px;
    border-radius: 6px;
    cursor: pointer;
}

.btn:hover {
    background: #0046b3;
}

#confirmModal {
    display: none;
    position: fixed;
    z-index: 999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,.5);
}

.modal-content {
    background: white;
    width: 320px;
    margin: 15% auto;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
}

.modal-btn {
    padding: 8px 14px;
    border: none;
    border-radius: 5px;
    margin: 8px;
    cursor: pointer;
}

.confirm-btn {
    background: #0b5cff;
    color: white;
}

.cancel-btn {
    background: #ddd;
}
</style>
</head>

<body>

<div class="main-card">

    <div style="width:320px;">
        <img src="<?= htmlspecialchars(BASE_URL . '/' . trim($movie['poster'])) ?>" class="movie-poster">

        <div style="margin-top:10px;">
            <div class="small">Genre: <?= htmlspecialchars($movie['genre']) ?></div>
            <div class="small">Language: <?= htmlspecialchars($movie['language']) ?></div>
            <div class="small">Duration: <?= htmlspecialchars($movie['duration']) ?> min</div>
        </div>
    </div>

    <div style="flex:1;min-width:300px;">

        <h2><?= htmlspecialchars($movie['title']) ?></h2>
        <p><?= nl2br(htmlspecialchars($movie['description'])) ?></p>

        <?php if (empty($showtimes)): ?>

            <p>No showtimes available.</p>

        <?php else: ?>

            <label>Showtime</label>

            <select id="showtimeSelect">
                <?php foreach ($showtimes as $st): ?>
                    <option value="<?= $st['showtime_id'] ?>" <?= $st['showtime_id'] == $showtime_id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($st['show_date']) ?>
                        <?= htmlspecialchars(substr($st['show_time'],0,5)) ?>
                        (Screen: <?= htmlspecialchars($st['screen']) ?>)
                        - NPR <?= number_format($st['base_price'],2) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <h3>Choose Seats</h3>

            <div class="small">
                Ticket Price: NPR <?= number_format($price,2) ?>
            </div>

            <div class="seat-map">
                <?php foreach ($seats as $s): ?>
                    <?php
                    $status = $s['status'];

                    if ($status === 'booked') {
                        $class = 'seat booked';
                    } elseif ($status === 'reserved') {
                        $class = 'seat reserved';
                    } else {
                        $class = 'seat available';
                    }
                    ?>

                    <div class="<?= $class ?>" data-label="<?= htmlspecialchars($s['seat_label']) ?>">
                        <?= htmlspecialchars($s['seat_label']) ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="legend">
                <div class="legend-item">
                    <div class="legend-box" style="background:white;border:1px solid #ccc;"></div>
                    Available
                </div>

                <div class="legend-item">
                    <div class="legend-box" style="background:red;"></div>
                    Selected Now
                </div>

                <div class="legend-item">
                    <div class="legend-box" style="background:#ffcc00;"></div>
                    Reserved
                </div>

                <div class="legend-item">
                    <div class="legend-box" style="background:#777;"></div>
                    Booked
                </div>
            </div>

            <form method="post" action="../ajax/booking_process.php" id="bookingForm">
                <input type="hidden" name="movie_id" value="<?= $movie['movie_id'] ?>">
                <input type="hidden" name="showtime_id" value="<?= $showtime_id ?>">
                <input type="hidden" name="selected_seats" id="selectedSeatsInput">

                <div style="margin-top:15px;">
                    <button class="btn" type="submit">Book Now</button>

                    <span style="margin-left:10px;">
                        Total: NPR <span id="totalAmount">0.00</span>
                    </span>
                </div>
            </form>

        <?php endif; ?>

    </div>
</div>

<div id="confirmModal">
    <div class="modal-content">
        <p id="confirmText"></p>

        <button class="modal-btn confirm-btn" id="modalConfirm">
            Confirm
        </button>

        <button class="modal-btn cancel-btn" id="modalCancel">
            Cancel
        </button>
    </div>
</div>

<script>
const isLoggedIn = <?= $customer_id ? 'true' : 'false' ?>;

const showtimeSelect = document.getElementById('showtimeSelect');

if (showtimeSelect) {
    showtimeSelect.addEventListener('change', function() {
        window.location.href = 'checkout.php?id=<?= $movie_id ?>&showtime=' + this.value;
    });
}

const selectedSeats = new Set();

const price = <?= json_encode((float)$price) ?>;
const showtimeId = <?= json_encode((int)$showtime_id) ?>;

const totalEl = document.getElementById('totalAmount');
const seatsInput = document.getElementById('selectedSeatsInput');

updateInput();
updateTotal();

function goLogin() {
    window.location.href = "../login.php?redirect=" + encodeURIComponent(window.location.href);
}

function updateInput() {
    seatsInput.value = Array.from(selectedSeats).join(',');
}

function updateTotal() {
    totalEl.textContent = (selectedSeats.size * price).toFixed(2);
}

function attachSeatClick(el) {
    el.onclick = function() {
        if (!isLoggedIn) {
            goLogin();
            return;
        }

        const label = this.dataset.label;

        if (selectedSeats.has(label)) {
            fetch('<?= BASE_URL ?>/ajax/release_seat.php', {
                method: 'POST',
                headers: {
                    'Content-Type':'application/x-www-form-urlencoded'
                },
                body:
                    'showtime_id=' + encodeURIComponent(showtimeId) +
                    '&seat_label=' + encodeURIComponent(label)
            })
            .then(r => r.json())
            .then(() => {
                selectedSeats.delete(label);
                this.className = 'seat available';
                updateInput();
                updateTotal();
            });

            return;
        }

        fetch('<?= BASE_URL ?>/ajax/reserve_seat.php', {
            method: 'POST',
            headers: {
                'Content-Type':'application/x-www-form-urlencoded'
            },
            body:
                'showtime_id=' + encodeURIComponent(showtimeId) +
                '&seat_label=' + encodeURIComponent(label)
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                if (data.message === 'Please login first') {
                    goLogin();
                    return;
                }

                alert(data.message || 'Seat unavailable');
                refreshSeats();
                return;
            }

            selectedSeats.add(label);
            this.className = 'seat selected';

            updateInput();
            updateTotal();
        })
        .catch(() => {
            alert('Reservation failed.');
        });
    };
}

document.querySelectorAll('.seat.available').forEach(attachSeatClick);

function refreshSeats() {
    fetch('<?= BASE_URL ?>/ajax/fetch_seats.php?showtime_id=' + encodeURIComponent(showtimeId))
        .then(r => r.json())
        .then(data => {
            data.forEach(s => {
                const el = document.querySelector('.seat[data-label="' + s.seat_label + '"]');
                if (!el) return;

                if (selectedSeats.has(s.seat_label)) {
                    el.className = 'seat selected';
                    attachSeatClick(el);
                    return;
                }

                if (s.status === 'booked') {
                    el.className = 'seat booked';
                    el.onclick = null;
                } else if (s.status === 'reserved') {
                    el.className = 'seat reserved';
                    el.onclick = null;
                } else {
                    el.className = 'seat available';
                    attachSeatClick(el);
                }
            });

            updateInput();
            updateTotal();
        })
        .catch(() => {});
}

setInterval(refreshSeats, 3000);

const form = document.getElementById('bookingForm');
const modal = document.getElementById('confirmModal');
const confirmText = document.getElementById('confirmText');

document.getElementById('modalConfirm').addEventListener('click', function() {
    modal.style.display = 'none';
    form.submit();
});

document.getElementById('modalCancel').addEventListener('click', function() {
    modal.style.display = 'none';
});

form.addEventListener('submit', function(e) {
    e.preventDefault();

    if (!isLoggedIn) {
        goLogin();
        return;
    }

    if (selectedSeats.size <= 0) {
        alert('Please select at least one seat');
        return;
    }

    confirmText.textContent =
        'You selected ' +
        selectedSeats.size +
        ' seat(s). Total NPR ' +
        (selectedSeats.size * price).toFixed(2) +
        '. Proceed?';

    modal.style.display = 'block';
});
</script>

</body>
</html>