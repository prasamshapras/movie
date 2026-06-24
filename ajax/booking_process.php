<?php
require_once '../includes/config.php';

date_default_timezone_set('Asia/Kathmandu');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$movie_id = intval($_POST['movie_id'] ?? 0);
$showtime_id = intval($_POST['showtime_id'] ?? 0);
$selected = trim($_POST['selected_seats'] ?? '');

$today = date('Y-m-d');
$currentTime = date('H:i:s');
$nowDateTime = date('Y-m-d H:i:s');

if (!isLoggedIn()) {
    $_SESSION['after_login_redirect'] = "movie.php?id=" . $movie_id . "&showtime=" . $showtime_id;
    $_SESSION['redirect_after_login'] = "movie.php?id=" . $movie_id . "&showtime=" . $showtime_id;
    header('Location: ../login.php');
    exit;
}

if (isAdminLoggedIn()) {
    $_SESSION['error'] = "Admin cannot book tickets. Please use a customer account.";
    header("Location: ../admin/dashboard.php");
    exit;
}

if (!$movie_id || !$showtime_id || !$selected) {
    die('No seats selected.');
}

$customer_id = currentUserId();

/*
|--------------------------------------------------------------------------
| Clean selected seats
|--------------------------------------------------------------------------
*/

$seatLabels = array_values(
    array_unique(
        array_filter(
            array_map('trim', explode(',', $selected))
        )
    )
);

if (empty($seatLabels)) {
    die('No valid seats selected.');
}

/*
|--------------------------------------------------------------------------
| Booking Limit Check
|--------------------------------------------------------------------------
| Customer can book maximum 10 different confirmed movies.
*/

$stmtLimit = $pdo->prepare("
    SELECT COUNT(DISTINCT movie_id)
    FROM bookings
    WHERE customer_id = ?
    AND status = 'Confirmed'
");
$stmtLimit->execute([$customer_id]);
$totalConfirmedMovies = $stmtLimit->fetchColumn();

if ($totalConfirmedMovies >= 10) {
    $stmtThisMovie = $pdo->prepare("
        SELECT COUNT(*)
        FROM bookings
        WHERE customer_id = ?
        AND movie_id = ?
        AND status = 'Confirmed'
    ");
    $stmtThisMovie->execute([$customer_id, $movie_id]);
    $alreadyBookedThisMovie = $stmtThisMovie->fetchColumn();

    if ($alreadyBookedThisMovie == 0) {
        echo "<h3>Booking limit reached</h3>";
        echo "<p>You have reached the maximum booking limit of 10 movies.</p>";
        echo "<a href='../index.php'>Back to Home</a>";
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Validate Movie
|--------------------------------------------------------------------------
*/

$movieCheck = $pdo->prepare("
    SELECT COUNT(*)
    FROM movies
    WHERE movie_id = ?
");
$movieCheck->execute([$movie_id]);

if ($movieCheck->fetchColumn() == 0) {
    die('Invalid movie.');
}

/*
|--------------------------------------------------------------------------
| Validate Showtime
|--------------------------------------------------------------------------
| This allows:
| - today ko future showtime
| - tomorrow ko showtime
| - parsi/future showtime
|
| This blocks:
| - today ko already passed showtime
| - wrong movie/showtime combination
*/

$priceStmt = $pdo->prepare("
    SELECT base_price
    FROM showtimes
    WHERE showtime_id = ?
    AND movie_id = ?
    AND (
        show_date > ?
        OR (
            show_date = ?
            AND show_time > ?
        )
    )
");
$priceStmt->execute([
    $showtime_id,
    $movie_id,
    $today,
    $today,
    $currentTime
]);

$base_price = $priceStmt->fetchColumn();

if (!$base_price) {
    die('This showtime has already passed or is invalid. Please select another available showtime.');
}

$base_price = (float)$base_price;
$totalAmount = count($seatLabels) * $base_price;

/*
|--------------------------------------------------------------------------
| One Transaction UUID For All Selected Seats
|--------------------------------------------------------------------------
| If user selects A1 and C2, both booking rows get same transaction_uuid.
*/

$transaction_uuid = "TXN-" . $customer_id . "-" . time() . "-" . bin2hex(random_bytes(4));

try {
    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Release Expired Seat Reservations
    |--------------------------------------------------------------------------
    */

    $releaseExpired = $pdo->prepare("
        UPDATE seats
        SET
            status = 'available',
            reserved_until = NULL,
            reserved_by_customer_id = NULL
        WHERE status = 'reserved'
        AND reserved_until IS NOT NULL
        AND reserved_until < ?
    ");
    $releaseExpired->execute([$nowDateTime]);

    /*
    |--------------------------------------------------------------------------
    | Check Selected Seats
    |--------------------------------------------------------------------------
    */

    $checkSeat = $pdo->prepare("
        SELECT status, reserved_by_customer_id
        FROM seats
        WHERE showtime_id = ?
        AND seat_label = ?
        FOR UPDATE
    ");

    $reserveSeat = $pdo->prepare("
        UPDATE seats
        SET
            status = 'reserved',
            reserved_until = ?,
            reserved_by_customer_id = ?
        WHERE showtime_id = ?
        AND seat_label = ?
    ");

    $reservedUntil = date('Y-m-d H:i:s', time() + 120);

    foreach ($seatLabels as $seat) {
        $checkSeat->execute([$showtime_id, $seat]);
        $seatRow = $checkSeat->fetch(PDO::FETCH_ASSOC);

        if (!$seatRow) {
            throw new Exception("Seat $seat does not exist for this showtime.");
        }

        if ($seatRow['status'] === 'booked') {
            throw new Exception("Seat $seat is already booked.");
        }

        if (
            $seatRow['status'] === 'reserved' &&
            intval($seatRow['reserved_by_customer_id']) !== intval($customer_id)
        ) {
            throw new Exception("Seat $seat is reserved by another user.");
        }

        /*
        |--------------------------------------------------------------------------
        | Reserve available seat again for current user before payment
        |--------------------------------------------------------------------------
        */

        if (
            $seatRow['status'] === 'available' ||
            (
                $seatRow['status'] === 'reserved' &&
                intval($seatRow['reserved_by_customer_id']) === intval($customer_id)
            )
        ) {
            $reserveSeat->execute([
                $reservedUntil,
                $customer_id,
                $showtime_id,
                $seat
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Insert One Booking Row Per Seat
    |--------------------------------------------------------------------------
    */

    $insertBooking = $pdo->prepare("
        INSERT INTO bookings
        (
            customer_id,
            showtime_id,
            seat_label,
            amount,
            status,
            payment_status,
            payment_ref,
            movie_id,
            transaction_uuid
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            'Pending',
            'Unpaid',
            NULL,
            ?,
            ?
        )
    ");

    $bookingIds = [];

    foreach ($seatLabels as $seat) {
        $insertBooking->execute([
            $customer_id,
            $showtime_id,
            $seat,
            $base_price,
            $movie_id,
            $transaction_uuid
        ]);

        $bookingIds[] = $pdo->lastInsertId();
    }

    if (empty($bookingIds)) {
        throw new Exception('Booking could not be created.');
    }

    $firstBookingId = $bookingIds[0];

    /*
    |--------------------------------------------------------------------------
    | Insert One Payment Row For Whole Seat Group
    |--------------------------------------------------------------------------
    */

    $insertPayment = $pdo->prepare("
        INSERT INTO payments
        (
            booking_id,
            amount,
            payment_method,
            payment_status,
            transaction_uuid
        )
        VALUES
        (
            ?,
            ?,
            'eSewa',
            'Pending',
            ?
        )
    ");

    $insertPayment->execute([
        $firstBookingId,
        $totalAmount,
        $transaction_uuid
    ]);

    $pdo->commit();

    header(
        "Location: ../payment.php?booking_id=" . $firstBookingId .
        "&transaction_uuid=" . urlencode($transaction_uuid)
    );
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "<h3>Booking failed</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<a href='javascript:history.back()'>Go Back</a>";
    exit;
}