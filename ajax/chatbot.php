<?php
require_once '../includes/config.php';
require_once '../includes/recommendation.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$message = strtolower(trim($input['message'] ?? ''));

if (!$message) {
    echo json_encode(['reply' => "I'm sorry, I didn't catch that."]);
    exit;
}

$customer_id = currentUserId();

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
        'keywords' => ['showtime', 'show time', 'when', 'time', 'times', 'schedule', 'screening'],
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
        'keywords' => ['my booking', 'my bookings', 'history', 'past', 'booked', 'ticket'],
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

// Only process if we have some confidence (at least one keyword matched)
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
                    $reply .= "- " . $m['title'] . " (" . $m['genre'] . ")\n";
                }
                $reply .= "These are personalized for you!";
            } else {
                $reply = "I don't have enough data to recommend something personal yet. Try browsing our newest releases!";
            }
            break;

        case 'showtime':
            // Simple extraction of movie title
            $movie_query = '';
            $remove = $intents['showtime']['keywords'];
            $movie_query = trim(str_replace($remove, '', $message));
            
            if (!$movie_query || strlen($movie_query) < 2) {
                $reply = "Which movie's showtime would you like to know?";
            } else {
                $stmt = $pdo->prepare("SELECT movie_id, title FROM movies WHERE LOWER(title) LIKE ? LIMIT 1");
                $stmt->execute(['%' . $movie_query . '%']);
                $movie = $stmt->fetch();
                if ($movie) {
                    $st = $pdo->prepare("SELECT show_date, show_time FROM showtimes WHERE movie_id = ? AND show_date >= CURDATE() ORDER BY show_date LIMIT 3");
                    $st->execute([$movie['movie_id']]);
                    $times = $st->fetchAll();
                    if ($times) {
                        $reply = "Shows for " . $movie['title'] . ":\n";
                        foreach ($times as $t) $reply .= "- " . date('M d', strtotime($t['show_date'])) . " at " . date('h:i A', strtotime($t['show_time'])) . "\n";
                    } else {
                        $reply = "No upcoming shows for " . $movie['title'] . ".";
                    }
                } else {
                    $reply = "I couldn't find a movie matching '$movie_query'.";
                }
            }
            break;

        case 'horror':
            $stmt = $pdo->query("SELECT title FROM movies WHERE genre LIKE '%Horror%' LIMIT 3");
            $horror = $stmt->fetchAll();
            if ($horror) {
                $reply = "Feeling brave? Here are some horror movies:\n";
                foreach ($horror as $h) $reply .= "- " . $h['title'] . "\n";
            } else {
                $reply = "No horror movies available right now.";
            }
            break;

        case 'booking':
            $reply = "Booking is easy! Pick a movie, select a showtime, choose your seats, and pay via eSewa. Your ticket will be in your dashboard instantly.";
            break;

        case 'seat':
            $reply = "Our interactive seat map shows available (white), selected (blue), reserved (orange), and booked (gray) seats. Choose your perfect spot!";
            break;

        case 'payment':
            $reply = "We support eSewa for secure payments. After a successful transaction, your seats are permanently booked.";
            break;

        case 'history':
            if (!$customer_id) {
                $reply = "Please login to see your booking history.";
            } else {
                $stmt = $pdo->prepare("SELECT b.booking_id, m.title FROM bookings b JOIN movies m ON b.movie_id = m.movie_id WHERE b.customer_id = ? ORDER BY b.created_at DESC LIMIT 3");
                $stmt->execute([$customer_id]);
                $history = $stmt->fetchAll();
                if ($history) {
                    $reply = "Your recent bookings:\n";
                    foreach ($history as $h) $reply .= "- #" . $h['booking_id'] . " for " . $h['title'] . "\n";
                } else {
                    $reply = "You haven't booked any movies yet.";
                }
            }
            break;

        case 'help':
            $reply = "I can help with recommendations, showtimes, booking steps, and viewing your history. Just ask!";
            break;
    }
} else {
    $reply = "I'm not quite sure I understand. You can ask me for 'movie suggestions', 'showtimes', or 'booking help'.";
}

echo json_encode(['reply' => $reply]);
