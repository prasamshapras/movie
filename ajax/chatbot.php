<?php
require_once '../includes/config.php';
require_once '../includes/recommendation.php';

header('Content-Type: application/json');

// Nepal timezone fix
date_default_timezone_set('Asia/Kathmandu');

$input = json_decode(file_get_contents('php://input'), true);
$messageOriginal = trim($input['message'] ?? '');
$message = strtolower($messageOriginal);

if (!$message) {
    echo json_encode(['reply' => "I'm sorry, I didn't catch that."]);
    exit;
}

$customer_id = currentUserId();
$today = date('Y-m-d');

/**
 * Find movie from user message
 * This checks actual movie title from database, so showtime matches correct movie.
 */
function findMovieFromMessage($pdo, $message) {
    $messageLower = strtolower($message);

    // First: direct title match from all movies
    $stmt = $pdo->query("SELECT movie_id, title, genre FROM movies ORDER BY LENGTH(title) DESC");
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($movies as $movie) {
        if (strpos($messageLower, strtolower($movie['title'])) !== false) {
            return $movie;
        }
    }

    // Second: remove common words and search by remaining text
    $clean = $messageLower;

    $removeWords = [
        'showtime', 'show time', 'shows', 'show', 'time', 'times',
        'schedule', 'screening', 'when', 'is', 'are', 'for',
        'movie', 'film', 'ko', 'kati', 'baje', 'cha', 'xa',
        'what', 'please', 'tell', 'me', 'today'
    ];

    foreach ($removeWords as $word) {
        $clean = str_replace($word, '', $clean);
    }

    $clean = trim(preg_replace('/\s+/', ' ', $clean));

    if (strlen($clean) >= 2) {
        $stmt = $pdo->prepare("
            SELECT movie_id, title, genre
            FROM movies
            WHERE LOWER(title) LIKE ?
            ORDER BY 
                CASE 
                    WHEN LOWER(title) = ? THEN 1
                    WHEN LOWER(title) LIKE ? THEN 2
                    ELSE 3
                END,
                title ASC
            LIMIT 1
        ");
        $stmt->execute([
            '%' . $clean . '%',
            $clean,
            $clean . '%'
        ]);

        $movie = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($movie) {
            return $movie;
        }
    }

    return null;
}

/**
 * AI/ML-Inspired Intent Classification
 */
$intents = [
    'greeting' => [
        'keywords' => ['hi', 'hello', 'hey', 'namaste', 'morning', 'evening', 'greetings'],
        'weight' => 1.0
    ],
    'recommendation' => [
        'keywords' => ['recommend', 'suggest', 'watch', 'surprise', 'suggestion', 'best', 'good'],
        'weight' => 1.2
    ],
    'showtime' => [
        'keywords' => ['showtime', 'show time', 'when', 'time', 'times', 'schedule', 'screening', 'kati baje', 'baje', 'show'],
        'weight' => 1.5
    ],
    'horror' => [
        'keywords' => ['horror', 'scary', 'ghost', 'thriller', 'frightening'],
        'weight' => 1.3
    ],
    'booking' => [
        'keywords' => ['book', 'booking', 'ticket', 'reserve', 'buy', 'purchase', 'how'],
        'weight' => 1.1
    ],
    'seat' => [
        'keywords' => ['seat', 'seats', 'available', 'select', 'position', 'choose'],
        'weight' => 1.1
    ],
    'payment' => [
        'keywords' => ['payment', 'esewa', 'pay', 'paid', 'cost', 'price', 'transaction'],
        'weight' => 1.2
    ],
    'history' => [
        'keywords' => ['my booking', 'my bookings', 'history', 'past', 'booked', 'my ticket'],
        'weight' => 1.4
    ],
    'help' => [
        'keywords' => ['help', 'can you', 'do', 'features', 'support', 'guide'],
        'weight' => 1.0
    ]
];

$scores = [];

foreach ($intents as $intent => $data) {
    $score = 0;

    foreach ($data['keywords'] as $kw) {
        if (strpos($message, $kw) !== false) {
            $score += $data['weight'];
        }
    }

    $scores[$intent] = $score;
}

arsort($scores);
$topIntent = key($scores);
$confidence = current($scores);

$reply = "";

// Extra check: if user mentions an actual movie title and time/show, force showtime intent
$detectedMovie = findMovieFromMessage($pdo, $messageOriginal);

if ($detectedMovie && (
    strpos($message, 'time') !== false ||
    strpos($message, 'show') !== false ||
    strpos($message, 'schedule') !== false ||
    strpos($message, 'when') !== false ||
    strpos($message, 'baje') !== false
)) {
    $topIntent = 'showtime';
    $confidence = 2;
}

if ($confidence > 0) {
    switch ($topIntent) {
        case 'greeting':
            $reply = "Hello! I am your Ticketly AI assistant. How can I help you today?";
            break;

        case 'recommendation':
            $recs = getRecommendedMovies($pdo, $customer_id, 3);

            if ($recs) {
                $reply = "Based on our AI algorithm, I recommend:\n";

                foreach ($recs as $m) {
                    $reply .= "- " . $m['title'];

                    if (!empty($m['genre'])) {
                        $reply .= " (" . $m['genre'] . ")";
                    }

                    $reply .= "\n";
                }

                $reply .= "These are personalized for you!";
            } else {
                $reply = "I don't have enough data to recommend something personal yet. Try browsing our newest releases!";
            }
            break;

        case 'showtime':
            $movie = $detectedMovie;

            if (!$movie) {
                $movie = findMovieFromMessage($pdo, $messageOriginal);
            }

            if (!$movie) {
                $reply = "Which movie's showtime would you like to know?";
            } else {
                /*
                 * IMPORTANT FIX:
                 * show_date = today's date only.
                 * This prevents chatbot from showing old/future/wrong dates.
                 */
                $st = $pdo->prepare("
                    SELECT showtime_id, show_date, show_time, screen, base_price
                    FROM showtimes
                    WHERE movie_id = ?
                    AND show_date = ?
                    ORDER BY show_time ASC
                ");
                $st->execute([$movie['movie_id'], $today]);
                $times = $st->fetchAll(PDO::FETCH_ASSOC);

                if ($times) {
                    $reply = "Today's shows for " . $movie['title'] . " (" . date('M d, Y', strtotime($today)) . "):\n";

                    foreach ($times as $t) {
                        $reply .= "- " . date('h:i A', strtotime($t['show_time']));

                        if (!empty($t['screen'])) {
                            $reply .= " | Screen: " . $t['screen'];
                        }

                        if (isset($t['base_price'])) {
                            $reply .= " | NPR " . number_format((float)$t['base_price'], 2);
                        }

                        $reply .= "\n";
                    }
                } else {
                    $reply = "No showtimes available for " . $movie['title'] . " today (" . date('M d, Y', strtotime($today)) . ").";
                }
            }
            break;

        case 'horror':
            $stmt = $pdo->prepare("
                SELECT title 
                FROM movies 
                WHERE genre LIKE '%Horror%' 
                ORDER BY title ASC
                LIMIT 3
            ");
            $stmt->execute();
            $horror = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($horror) {
                $reply = "Feeling brave? Here are some horror movies:\n";

                foreach ($horror as $h) {
                    $reply .= "- " . $h['title'] . "\n";
                }
            } else {
                $reply = "No horror movies available right now.";
            }
            break;

        case 'booking':
            $reply = "Booking is easy! Pick a movie, select today's showtime, choose your seats, and pay via eSewa. Your ticket will be shown in your dashboard after successful payment.";
            break;

        case 'seat':
            $reply = "Our seat map shows available, selected, reserved, and booked seats. Choose your seat from the seat selection page before payment.";
            break;

        case 'payment':
            $reply = "We support eSewa payment. After successful payment, your booking status changes to Confirmed and your seats become permanently booked.";
            break;

        case 'history':
            if (!$customer_id) {
                $reply = "Please login to see your booking history.";
            } else {
                $stmt = $pdo->prepare("
                    SELECT 
                        b.booking_id, 
                        b.status,
                        b.created_at,
                        m.title
                    FROM bookings b
                    JOIN showtimes s ON b.showtime_id = s.showtime_id
                    JOIN movies m ON s.movie_id = m.movie_id
                    WHERE b.customer_id = ?
                    ORDER BY b.created_at DESC
                    LIMIT 3
                ");
                $stmt->execute([$customer_id]);
                $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($history) {
                    $reply = "Your recent bookings:\n";

                    foreach ($history as $h) {
                        $reply .= "- #" . $h['booking_id'] . " for " . $h['title'] . " | Status: " . $h['status'] . "\n";
                    }
                } else {
                    $reply = "You haven't booked any movies yet.";
                }
            }
            break;

        case 'help':
            $reply = "I can help with movie recommendations, today's showtimes, booking steps, seat information, payment help, and your booking history.";
            break;
    }
} else {
    $reply = "I'm not quite sure I understand. You can ask me for 'movie suggestions', 'today showtime for movie name', or 'booking help'.";
}

echo json_encode(['reply' => $reply]);