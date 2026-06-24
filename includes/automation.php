<?php
/*
|--------------------------------------------------------------------------
| Ticketly Automation
|--------------------------------------------------------------------------
| Important:
| Do NOT delete old showtimes.
| Do NOT delete old bookings.
| Do NOT delete old payments.
|
| Booking history should always remain visible to user and admin.
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config.php';

date_default_timezone_set('Asia/Kathmandu');

if (!function_exists('cleanupPastShows')) {
    function cleanupPastShows(PDO $pdo)
    {
        try {
            /*
            |--------------------------------------------------------------------------
            | Only release expired temporary seat reservations
            |--------------------------------------------------------------------------
            | This is safe.
            | This does not remove confirmed bookings.
            | This does not remove past showtimes.
            */

            $stmt = $pdo->prepare("
                UPDATE seats
                SET status = 'available',
                    reserved_until = NULL,
                    reserved_by_customer_id = NULL
                WHERE status = 'reserved'
                AND reserved_until IS NOT NULL
                AND reserved_until < NOW()
            ");
            $stmt->execute();

            return $stmt->rowCount();

        } catch (Exception $e) {
            error_log("Cleanup error: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('ensureSeatsForShowtime')) {
    function ensureSeatsForShowtime(PDO $pdo, int $showtime_id): void
    {
        $check = $pdo->prepare("
            SELECT COUNT(*)
            FROM seats
            WHERE showtime_id = ?
        ");
        $check->execute([$showtime_id]);

        if ($check->fetchColumn() > 0) {
            return;
        }

        $rows = ['A', 'B', 'C', 'D'];

        $insert = $pdo->prepare("
            INSERT INTO seats (showtime_id, seat_label, status)
            VALUES (?, ?, 'available')
        ");

        foreach ($rows as $row) {
            for ($i = 1; $i <= 10; $i++) {
                $insert->execute([$showtime_id, $row . $i]);
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| If this file is opened directly
|--------------------------------------------------------------------------
*/

if (basename($_SERVER['PHP_SELF']) === 'automation.php') {
    $released = cleanupPastShows($pdo);

    if ($released === false) {
        echo "Cleanup failed.";
    } else {
        echo "Cleanup completed. Expired temporary reservations released: " . intval($released);
    }
}