<?php

/**
 * KNN and Cosine Similarity Based Recommendation System
 */

/**
 * Content-Based: Tokenize movie features for comparison
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
 * Cosine Similarity: Calculate similarity between two vectors
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

    if ($magA == 0 || $magB == 0) {
        return 0;
    }

    return $dot / (sqrt($magA) * sqrt($magB));
}

/**
 * Content-Based: Build a preference vector for the user based on history
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
 * KNN: User-User Similarity (Collaborative Filtering)
 * Finds K nearest neighbors based on demographic data (Age, Gender)
 * and returns weighted movie scores from those neighbors.
 */
function getKnnScores($pdo, $customer_id, $k = 5) {
    $scores = [];

    $stmt = $pdo->prepare("SELECT age, gender FROM customers WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $current = $stmt->fetch();

    // If user has no demographic data, return empty scores
    if (!$current || !$current['age'] || !$current['gender']) {
        return $scores;
    }

    $users = $pdo->prepare("
        SELECT customer_id, age, gender
        FROM customers
        WHERE customer_id != ?
        AND age IS NOT NULL
        AND gender IS NOT NULL
    ");
    $users->execute([$customer_id]);

    $neighbors = [];

    foreach ($users->fetchAll() as $user) {
        // Euclidean distance for Age (normalized roughly)
        $ageDiff = abs((int)$current['age'] - (int)$user['age']);
        
        // Categorical distance for Gender
        $genderDiff = strtolower($current['gender']) === strtolower($user['gender']) ? 0 : 5;

        // Total demographic distance
        $distance = sqrt(pow($ageDiff, 2) + pow($genderDiff, 2));

        $neighbors[] = [
            'customer_id' => $user['customer_id'],
            'distance' => $distance
        ];
    }

    // Sort by smallest distance
    usort($neighbors, function($a, $b) {
        return $a['distance'] <=> $b['distance'];
    });

    // Take top K
    $neighbors = array_slice($neighbors, 0, $k);

    foreach ($neighbors as $n) {
        // Higher weight for closer neighbors (1 / (distance + 1))
        $weight = 1 / ($n['distance'] + 1);

        $stmt = $pdo->prepare("
            SELECT DISTINCT s.movie_id
            FROM bookings b
            JOIN showtimes s ON b.showtime_id = s.showtime_id
            WHERE b.customer_id = ? AND b.status = 'Confirmed'
        ");
        $stmt->execute([$n['customer_id']]);

        foreach ($stmt->fetchAll() as $movie) {
            $mid = $movie['movie_id'];
            $scores[$mid] = ($scores[$mid] ?? 0) + (1 * $weight);
        }
    }

    return $scores;
}

/**
 * Main Hybrid Recommendation Engine with Context Awareness
 */
function getRecommendedMovies($pdo, $customer_id = null, $limit = 4, $context = []) {
    $allMovies = $pdo->query("SELECT * FROM movies")->fetchAll();
    
    $userVector = [];
    $bookedMovieIds = [];
    if ($customer_id) {
        $userVector = getUserVector($pdo, $customer_id);
        $bookedMovieIds = getBookedMovieIds($pdo, $customer_id);
    }
    
    // KNN Scores (Demographic Collaborative)
    $knnScores = $customer_id ? getKnnScores($pdo, $customer_id) : [];

    // Contextual factors
    $currentTime = $context['time'] ?? date('H:i:s');
    $viewingMovieId = $context['movie_id'] ?? null;
    
    $recommended = [];

    foreach ($allMovies as $movie) {
        // Skip already booked or currently viewing
        if (in_array($movie['movie_id'], $bookedMovieIds) || $movie['movie_id'] == $viewingMovieId) {
            continue;
        }

        $movieVector = [];
        foreach (movieTokens($movie) as $token) {
            $movieVector[$token] = 1;
        }

        // 1. Content-Based Score (User Interest)
        $cosineScore = $customer_id ? cosineSimilarity($userVector, $movieVector) : 0;

        // 2. Similarity to currently viewing movie (if any)
        $itemItemScore = 0;
        if ($viewingMovieId) {
            $stmt = $pdo->prepare("SELECT * FROM movies WHERE movie_id = ?");
            $stmt->execute([$viewingMovieId]);
            $viewingMovie = $stmt->fetch();
            if ($viewingMovie) {
                $viewingVector = [];
                foreach (movieTokens($viewingMovie) as $token) {
                    $viewingVector[$token] = 1;
                }
                $itemItemScore = cosineSimilarity($viewingVector, $movieVector);
            }
        }

        // 3. KNN Score
        $knnScore = $knnScores[$movie['movie_id']] ?? 0;

        // 4. Contextual Score (Time of day)
        $contextScore = 0;
        $hour = (int)date('H', strtotime($currentTime));
        if ($hour >= 18 || $hour < 4) { // Night
            if (stripos($movie['genre'], 'Thriller') !== false || stripos($movie['genre'], 'Horror') !== false || stripos($movie['genre'], 'Mystery') !== false) {
                $contextScore += 0.5;
            }
        } else { // Day
            if (stripos($movie['genre'], 'Animation') !== false || stripos($movie['genre'], 'Comedy') !== false || stripos($movie['genre'], 'Adventure') !== false) {
                $contextScore += 0.5;
            }
        }

        // Hybrid Score: Weighting
        $score = ($cosineScore * 10) + ($itemItemScore * 8) + ($knnScore * 5) + ($contextScore * 4);

        // Add a small boost for general popularity
        $popStmt = $pdo->prepare("
            SELECT COUNT(*) FROM bookings b 
            JOIN showtimes s ON b.showtime_id = s.showtime_id 
            WHERE s.movie_id = ? AND b.status = 'Confirmed'
        ");
        $popStmt->execute([$movie['movie_id']]);
        $popularity = (int)$popStmt->fetchColumn();
        $score += ($popularity * 0.1);

        $movie['recommendation_score'] = $score;
        $recommended[] = $movie;
    }

    // Sort by score descending
    usort($recommended, function($a, $b) {
        return $b['recommendation_score'] <=> $a['recommendation_score'];
    });

    return array_slice($recommended, 0, $limit);
}