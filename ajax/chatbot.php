<?php
require_once '../includes/config.php';
require_once '../includes/recommendation.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$message = strtolower(trim($input['message'] ?? ''));

if (!$message) {
    echo json_encode([
        'reply' => "I'm sorry, I didn't catch that."
    ]);
    exit;
}

$reply = "";
$customer_id = currentUserId();

/*
|--------------------------------------------------------------------------
| Greeting
|--------------------------------------------------------------------------
*/
if (preg_match('/\b(hi|hello|hey|namaste)\b/', $message)) {
    $reply = "Hello! Welcome to Ticketly. You can ask me for movie recommendations, showtimes, booking help, seat information, or payment help.";
}

/*
|--------------------------------------------------------------------------
| Recommendation
|--------------------------------------------------------------------------
*/
elseif (preg_match('/recommend|suggest|what should i watch|surprise me|movie suggestion/', $message)) {
    $recommendations = getRecommendedMovies($pdo, $customer_id, 3);

    if (empty($recommendations)) {
        $stmt = $pdo->query("
            SELECT title, genre, language 
            FROM movies 
            ORDER BY created_at DESC 
            LIMIT 3
        ");
        $recommendations = $stmt->fetchAll();
    }

    if (empty($recommendations)) {
        $reply = "I couldn't find recommendations right now. Please check the Movies page for available movies.";
    } else {
        $reply = "I recommend these movies:\n";

        foreach ($recommendations as $m) {
            $reply .= "- " . $m['title'];

            if (!empty($m['genre'])) {
                $reply .= " (" . $m['genre'] . ")";
            }

            if (!empty($m['language'])) {
                $reply .= " - " . $m['language'];
            }

            $reply .= "\n";
        }

        $reply .= "You can open the movie and click Book Now to choose seats.";
    }
}

/*
|--------------------------------------------------------------------------
| Showtime Search
|--------------------------------------------------------------------------
*/
elseif (preg_match('/showtime|show time|when is|time for|times for/', $message)) {
    $movie_query = '';

    if (preg_match('/(?:showtime|show time|when is|time for|times for)\s+(.*)/', $message, $matches)) {
        $movie_query = trim($matches[1]);
    }

    if (!$movie_query) {
        $reply = "Which movie showtime do you want to check? Example: showtime for Dil Ka Safar";
    } else {
        $stmt = $pdo->prepare("
            SELECT movie_id, title 
            FROM movies 
            WHERE LOWER(title) LIKE ? 
            LIMIT 1
        ");
        $stmt->execute(['%' . $movie_query . '%']);
        $movie = $stmt->fetch();

        if ($movie) {
            $stmt = $pdo->prepare("
                SELECT show_date, show_time, screen, base_price
                FROM showtimes 
                WHERE movie_id = ? 
                AND show_date >= CURDATE()
                ORDER BY show_date, show_time 
                LIMIT 5
            ");
            $stmt->execute([$movie['movie_id']]);
            $times = $stmt->fetchAll();

            if ($times) {
                $reply = "Upcoming shows for " . $movie['title'] . ":\n";

                foreach ($times as $t) {
                    $reply .= "- " . date('M d, Y', strtotime($t['show_date']));
                    $reply .= " at " . date('h:i A', strtotime($t['show_time']));

                    if (!empty($t['screen'])) {
                        $reply .= " | " . $t['screen'];
                    }

                    if (!empty($t['base_price'])) {
                        $reply .= " | NPR " . number_format($t['base_price']);
                    }

                    $reply .= "\n";
                }
            } else {
                $reply = "There are no upcoming shows scheduled for " . $movie['title'] . " right now.";
            }
        } else {
            $reply = "I couldn't find a movie called '$movie_query'. Please check the movie title and try again.";
        }
    }
}

/*
|--------------------------------------------------------------------------
| Horror Movies
|--------------------------------------------------------------------------
*/
elseif (preg_match('/horror|scary|ghost/', $message)) {
    $stmt = $pdo->prepare("
        SELECT title, language 
        FROM movies 
        WHERE genre LIKE '%Horror%' 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $movies = $stmt->fetchAll();

    if ($movies) {
        $reply = "Here are some horror movies available:\n";

        foreach ($movies as $m) {
            $reply .= "- " . $m['title'] . " (" . $m['language'] . ")\n";
        }

        $reply .= "You can click Book Now from the Movies page.";
    } else {
        $reply = "No horror movies are available right now.";
    }
}

/*
|--------------------------------------------------------------------------
| Booking Help
|--------------------------------------------------------------------------
*/
elseif (preg_match('/book|booking|ticket|how to book/', $message)) {
    $reply = "To book a ticket:\n1. Choose a movie.\n2. Select a showtime.\n3. Choose your seats.\n4. Click proceed to payment.\n5. Complete payment using eSewa.";
}

/*
|--------------------------------------------------------------------------
| Seat Help
|--------------------------------------------------------------------------
*/
elseif (preg_match('/seat|seats|available seat|select seat/', $message)) {
    $reply = "On the seat selection page:\n- Available seats can be selected.\n- Selected seats are highlighted.\n- Reserved seats are temporarily blocked.\n- Sold out seats cannot be selected.";
}

/*
|--------------------------------------------------------------------------
| Payment Help
|--------------------------------------------------------------------------
*/
elseif (preg_match('/payment|esewa|pay|paid/', $message)) {
    $reply = "Ticketly uses eSewa for payment. After successful payment, your booking will be confirmed. If payment fails or is cancelled, you can try booking again.";
}

/*
|--------------------------------------------------------------------------
| My Bookings
|--------------------------------------------------------------------------
*/
elseif (preg_match('/my booking|my bookings|history|past booking/', $message)) {
    if (!$customer_id) {
        $reply = "Please login first to view your bookings.";
    } else {
        $stmt = $pdo->prepare("
            SELECT b.booking_id, b.status, b.payment_status, m.title, s.show_date, s.show_time
            FROM bookings b
            LEFT JOIN movies m ON b.movie_id = m.movie_id
            LEFT JOIN showtimes s ON b.showtime_id = s.showtime_id
            WHERE b.customer_id = ?
            ORDER BY b.created_at DESC
            LIMIT 3
        ");
        $stmt->execute([$customer_id]);
        $bookings = $stmt->fetchAll();

        if ($bookings) {
            $reply = "Your recent bookings:\n";

            foreach ($bookings as $b) {
                $reply .= "- #" . $b['booking_id'] . " | " . $b['title'] . " | " . $b['status'] . " | " . $b['payment_status'] . "\n";
            }
        } else {
            $reply = "You do not have any bookings yet.";
        }
    }
}

/*
|--------------------------------------------------------------------------
| Help
|--------------------------------------------------------------------------
*/
elseif (preg_match('/help|what can you do/', $message)) {
    $reply = "I can help you with:\n1. Movie recommendations\n2. Showtimes\n3. Horror movie suggestions\n4. Booking steps\n5. Seat selection\n6. eSewa payment help\n7. My bookings";
}

/*
|--------------------------------------------------------------------------
| Default
|--------------------------------------------------------------------------
*/
else {
    $reply = "I am still learning. You can ask me things like:\n- Recommend me a movie\n- Showtime for Dil Ka Safar\n- Horror movies\n- How to book ticket\n- Payment help";
}

echo json_encode([
    'reply' => $reply
]);