<?php

/**
 * Hybrid Recommendation System for Ticketly
 * Weights:
 * - Cosine Similarity (Content-Based): 50%
 * - Collaborative Filtering (User History): 20%
 * - KNN (Demographic Matching): 15%
 * - Context-Aware (Time of Day): 15%
 */

/**
 * Tokenize movie features for content comparison
 */
function movieTokens($movie) {
    $tokens = [];

    // Genre tokens
    $genres = explode(',', strtolower($movie['genre'] ?? ''));
    foreach ($genres as $g) {
        $g = trim($g);
        if ($g !== '') {
            $tokens[] = 'genre_' . $g;
        }
    }

    // Language tokens
    $language = strtolower(trim($movie['language'] ?? ''));
    if ($language !== '') {
        $tokens[] = 'language_' . $language;
    }

    return $tokens;
}

/**
 * Calculate similarity between two vectors
 */
function cosineSimilarity($a, $b) {
    $dot = 0;
    $magA = 0;
    $magB = 0;

    foreach ($a as $key => $value) {
        $magA += $value * $value;
        if (isset($b[$key])) {
            $dot += $value * $b[$key];
        }
    }

    foreach ($b as $value) {
        $magB += $value * $value;
    }

    if ($magA == 0 || $magB == 0) return 0;

    return $dot / (sqrt($magA) * sqrt($magB));
}

/**
 * Build a preference vector for the user based on history
 */
function getUserVector($pdo, $customer_id) {
    $stmt = $pdo->prepare("
        SELECT m.*
        FROM bookings b
        JOIN showtimes s ON b.showtime_id = s.showtime_id
        JOIN movies m ON s.movie_id = m.movie_id
        WHERE b.customer_id = ? AND b.status = 'Confirmed'
    ");
    $stmt->execute([$customer_id]);

    $movies = $stmt->fetchAll();
    $vector = [];

    foreach ($movies as $movie) {
        foreach (movieTokens($movie) as $token) {
            $vector[$token] = ($vector[$token] ?? 0) + 1;
        }
    }

    return $vector;
}

/**
 * Get IDs of movies already booked by user
 */
function getBookedMovieIds($pdo, $customer_id) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT s.movie_id
        FROM bookings b
        JOIN showtimes s ON b.showtime_id = s.showtime_id
        WHERE b.customer_id = ?
    ");
    $stmt->execute([$customer_id]);

    return array_column($stmt->fetchAll(), 'movie_id');
}

/**
 * KNN: Demographic Filtering
 * Finds users with similar age and gender
 */
function getKnnScores($pdo, $customer_id, $k = 5) {
    $scores = [];

    $stmt = $pdo->prepare("SELECT age, gender FROM customers WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $current = $stmt->fetch();

    if (!$current || !$current['age'] || !$current['gender']) return $scores;

    $users = $pdo->prepare("
        SELECT customer_id, age, gender
        FROM customers
        WHERE customer_id != ? AND age IS NOT NULL AND gender IS NOT NULL
    ");
    $users->execute([$customer_id]);

    $neighbors = [];
    foreach ($users->fetchAll() as $user) {
        $ageDiff = abs((int)$current['age'] - (int)$user['age']);
        $genderDiff = strtolower($current['gender']) === strtolower($user['gender']) ? 0 : 5;
        $distance = sqrt(pow($ageDiff, 2) + pow($genderDiff, 2));

        $neighbors[] = ['id' => $user['customer_id'], 'dist' => $distance];
    }

    usort($neighbors, function($a, $b) { return $a['dist'] <=> $b['dist']; });
    $topK = array_slice($neighbors, 0, $k);

    foreach ($topK as $n) {
        $weight = 1 / ($n['dist'] + 1);
        $stmt = $pdo->prepare("
            SELECT DISTINCT s.movie_id FROM bookings b
            JOIN showtimes s ON b.showtime_id = s.showtime_id
            WHERE b.customer_id = ? AND b.status = 'Confirmed'
        ");
        $stmt->execute([$n['id']]);
        foreach ($stmt->fetchAll() as $movie) {
            $mid = $movie['movie_id'];
            $scores[$mid] = ($scores[$mid] ?? 0) + $weight;
        }
    }

    return $scores;
}

/**
 * Collaborative Filtering: Score movies based on general popularity
 * (Users who watched movies also watched these)
 */
function getCollaborativeScores($pdo) {
    $stmt = $pdo->query("
        SELECT s.movie_id, COUNT(*) as booking_count
        FROM bookings b
        JOIN showtimes s ON b.showtime_id = s.showtime_id
        WHERE b.status = 'Confirmed'
        GROUP BY s.movie_id
    ");
    return array_column($stmt->fetchAll(), 'booking_count', 'movie_id');
}

/**
 * Main Hybrid Recommendation Engine
 */
function getRecommendedMovies($pdo, $customer_id = null, $limit = 4, $context = []) {
    $allMovies = $pdo->query("SELECT * FROM movies")->fetchAll();
    
    $userVector = [];
    $bookedMovieIds = [];
    if ($customer_id) {
        $userVector = getUserVector($pdo, $customer_id);
        $bookedMovieIds = getBookedMovieIds($pdo, $customer_id);
    }
    
    $knnScores = $customer_id ? getKnnScores($pdo, $customer_id) : [];
    $collabScores = getCollaborativeScores($pdo);
    
    // Normalize Collab scores to 0-1
    $maxCollab = !empty($collabScores) ? max($collabScores) : 1;

    $currentTime = $context['time'] ?? date('H:i:s');
    $hour = (int)date('H', strtotime($currentTime));
    $viewingMovieId = $context['movie_id'] ?? null;
    
    $recommended = [];

    foreach ($allMovies as $movie) {
        $mid = $movie['movie_id'];

        // Exclude already booked movies
        if (in_array($mid, $bookedMovieIds)) continue;
        
        // If "You Might Also Like" context, exclude the current movie
        if ($viewingMovieId && $mid == $viewingMovieId) continue;

        $movieVector = [];
        foreach (movieTokens($movie) as $token) {
            $movieVector[$token] = 1;
        }

        // 1. Cosine Similarity (Content-Based) - 50%
        // If viewing a movie, compare with that movie. Otherwise, compare with user profile.
        $cosineScore = 0;
        if ($viewingMovieId) {
            $stmt = $pdo->prepare("SELECT * FROM movies WHERE movie_id = ?");
            $stmt->execute([$viewingMovieId]);
            $viewingMovie = $stmt->fetch();
            if ($viewingMovie) {
                $viewingVector = [];
                foreach (movieTokens($viewingMovie) as $token) $viewingVector[$token] = 1;
                $cosineScore = cosineSimilarity($viewingVector, $movieVector);
            }
        } elseif (!empty($userVector)) {
            $cosineScore = cosineSimilarity($userVector, $movieVector);
        }

        // 2. Collaborative Filtering (User History/Popularity) - 20%
        $collabScore = ($collabScores[$mid] ?? 0) / $maxCollab;

        // 3. KNN Demographic Score - 15%
        $knnScore = 0;
        if (!empty($knnScores)) {
            $maxKnn = max($knnScores);
            $knnScore = ($knnScores[$mid] ?? 0) / ($maxKnn ?: 1);
        }

        // 4. Contextual Score (Time of Day) - 15%
        $contextScore = 0;
        $genre = strtolower($movie['genre']);
        
        if ($hour >= 18 || $hour < 6) { // Evening/Night (6 PM - 6 AM)
            $nightGenres = ['horror', 'thriller', 'scary', 'suspense', 'action'];
            foreach ($nightGenres as $ng) {
                if (stripos($genre, $ng) !== false) {
                    $contextScore = 1;
                    break;
                }
            }
        } else { // Morning/Day (6 AM - 6 PM)
            $dayGenres = ['comedy', 'romance', 'romantic', 'family', 'animation', 'drama'];
            foreach ($dayGenres as $dg) {
                if (stripos($genre, $dg) !== false) {
                    $contextScore = 1;
                    break;
                }
            }
        }

        // Final Hybrid Calculation
        $finalScore = ($cosineScore * 0.50) + 
                      ($collabScore * 0.20) + 
                      ($knnScore * 0.15) + 
                      ($contextScore * 0.15);

        $movie['recommendation_score'] = $finalScore;
        $recommended[] = $movie;
    }

    // Sort by score descending
    usort($recommended, function($a, $b) {
        return $b['recommendation_score'] <=> $a['recommendation_score'];
    });

    return array_slice($recommended, 0, $limit);
}
