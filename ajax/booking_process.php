<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$movie_id     = intval($_POST['movie_id'] ?? 0);
$showtime_id  = intval($_POST['showtime_id'] ?? 0);
$selected     = trim($_POST['selected_seats'] ?? '');

if (!isLoggedIn()) {
    $_SESSION['after_login_redirect'] = "seat_selection.php?movie_id=" . $movie_id . "&showtime_id=" . $showtime_id;
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
| Example selected_seats value: A1,C2,D5
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
| Validate Showtime and Price
|--------------------------------------------------------------------------
*/

$priceStmt = $pdo->prepare("
    SELECT base_price
    FROM showtimes
    WHERE showtime_id = ?
    AND movie_id = ?
");
$priceStmt->execute([$showtime_id, $movie_id]);
$base_price = $priceStmt->fetchColumn();

if (!$base_price) {
    die('Invalid showtime.');
}

$base_price = (float)$base_price;
$totalAmount = count($seatLabels) * $base_price;

/*
|--------------------------------------------------------------------------
| One Transaction UUID For All Selected Seats
|--------------------------------------------------------------------------
| This is the main fix.
| If user selects A1 and C2, both booking rows will have same transaction_uuid.
*/

$transaction_uuid = "TXN-" . $customer_id . "-" . time() . "-" . bin2hex(random_bytes(4));

try {
    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Release Expired Reservations
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
        AND reserved_until < NOW()
    ");
    $releaseExpired->execute();

    /*
    |--------------------------------------------------------------------------
    | Check Seat Availability With Lock
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
            reserved_until = DATE_ADD(NOW(), INTERVAL 2 MINUTE),
            reserved_by_customer_id = ?
        WHERE showtime_id = ?
        AND seat_label = ?
    ");

    foreach ($seatLabels as $seat) {
        $checkSeat->execute([$showtime_id, $seat]);
        $seatRow = $checkSeat->fetch(PDO::FETCH_ASSOC);

        if (!$seatRow) {
            throw new Exception("Seat $seat does not exist.");
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
        | Reserve available seat for current customer
        |--------------------------------------------------------------------------
        */

        if ($seatRow['status'] === 'available') {
            $reserveSeat->execute([
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
    | All selected seats share the same transaction_uuid.
    | Example:
    | A1 -> Pending -> TXN-123
    | C2 -> Pending -> TXN-123
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

    /*
    |--------------------------------------------------------------------------
    | Insert One Payment Row For Whole Seat Group
    |--------------------------------------------------------------------------
    | Payment amount is total of all selected seats.
    | Payment is linked to first booking_id only,
    | but transaction_uuid represents the full group.
    */

    $firstBookingId = $bookingIds[0];

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

    /*
    |--------------------------------------------------------------------------
    | Redirect To Payment Page
    |--------------------------------------------------------------------------
    | Send both booking_id and transaction_uuid.
    | payment.php must use this same transaction_uuid.
    */

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