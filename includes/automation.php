<?php
require_once __DIR__ . '/../includes/config.php';

/**
 * Automation script to remove past showtimes and their associated bookings
 * This leverages the ON DELETE CASCADE database constraints.
 */
function cleanupPastShows($pdo) {
    try {
        // Find showtimes that are older than today
        // We use CURDATE() to get today's date (at 00:00:00)
        // If the show_date is less than today, it's a past show.
        $stmt = $pdo->prepare("DELETE FROM showtimes WHERE show_date < CURDATE()");
        $stmt->execute();
        
        $count = $stmt->rowCount();
        return $count;
    } catch (Exception $e) {
        error_log("Cleanup error: " . $e->getMessage());
        return false;
    }
}

// If run directly via CLI or browser
if (basename($_SERVER['PHP_SELF']) == 'cleanup_past_shows.php') {
    $deleted = cleanupPastShows($pdo);
    if ($deleted === false) {
        echo "Error during cleanup.";
    } else {
        echo "Successfully removed $deleted past showtimes and all associated bookings/seats.";
    }
}
