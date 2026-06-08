<?php
require_once 'includes/config.php';

// Check if we have movies
$movies = $pdo->query("SELECT movie_id FROM movies")->fetchAll();

if (empty($movies)) {
    die("No movies found to seed showtimes for.");
}

try {
    $pdo->beginTransaction();

    foreach ($movies as $movie) {
        $movieId = $movie['movie_id'];
        
        // Add a showtime for today and tomorrow if not exists
        $dates = [date('Y-m-d'), date('Y-m-d', strtotime('+1 day'))];
        
        foreach ($dates as $date) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM showtimes WHERE movie_id = ? AND show_date = ?");
            $stmt->execute([$movieId, $date]);
            
            if ($stmt->fetchColumn() == 0) {
                // Insert showtime
                $ins = $pdo->prepare("INSERT INTO showtimes (movie_id, show_date, show_time, screen, base_price) VALUES (?, ?, '18:30:00', 'Screen 1', 250.00)");
                $ins->execute([$movieId, $date]);
                $showtimeId = $pdo->lastInsertId();
                
                // Seed seats for this showtime
                $rows = ['A', 'B', 'C', 'D'];
                for ($i = 1; $i <= 10; $i++) {
                    foreach ($rows as $row) {
                        $label = $row . $i;
                        $seatIns = $pdo->prepare("INSERT INTO seats (showtime_id, seat_label, status) VALUES (?, ?, 'available')");
                        $seatIns->execute([$showtimeId, $label]);
                    }
                }
            }
        }
    }

    $pdo->commit();
    echo "Showtimes and seats seeded successfully for all movies!";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error seeding data: " . $e->getMessage();
}
