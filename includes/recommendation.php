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
 * Tokenize movie features for content comparison (AI/ML NLP preprocessing)
 */
function movieTokens($movie) {
    $tokens = [];
    $stopWords = ['the', 'is', 'in', 'and', 'to', 'a', 'of', 'it', 'for', 'with', 'on', 'at', 'by', 'an', 'be', 'this', 'that', 'from', 'as', 'are', 'was', 'were', 'will', 'can', 'his', 'her', 'their', 'they', 'he', 'she', 'who', 'whom', 'whose'];

    // 1. Genre tokens (High weight)
    $genres = explode(',', strtolower($movie['genre'] ?? ''));
    foreach ($genres as $g) {
        $g = trim($g);
        if ($g !== '') {
            $tokens[] = 'genre_' . $g;
        }
    }

    // 2. Language tokens
    $language = strtolower(trim($movie['language'] ?? ''));
    if ($language !== '') {
        $tokens[] = 'language_' . $language;
    }

    // 3. Description Keywords (AI NLP extraction)
    $desc = strtolower($movie['description'] ?? '');
    // Clean string: remove punctuation and non-alphanumeric characters
    $desc = preg_replace('/[^\w\s]/', '', $desc);
    $words = explode(' ', $desc);
    
    foreach ($words as $word) {
        $word = trim($word);
        // Minimum length 3 and not a stop word
        if (strlen($word) > 2 && !in_array($word, $stopWords)) {
            $tokens[] = 'term_' . $word;
        }
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
 * Main Hybrid Recommendation Engine (Improved with AI/ML concepts)
 */
function getRecommendedMovies($pdo, $customer_id = null, $limit = 4, $context = []) {
    // 1. Data Retrieval - Only released movies
    $allMovies = $pdo->query("SELECT * FROM movies WHERE release_date IS NOT NULL AND release_date <= CURDATE()")->fetchAll();
    
    if (empty($allMovies)) return [];

    // 2. Feature Extraction (Personal Profile)
    $userVector = [];
    $bookedMovieIds = [];
    if ($customer_id) {
        $userVector = getUserVector($pdo, $customer_id);
        $bookedMovieIds = getBookedMovieIds($pdo, $customer_id);
    }
    
    // 3. Collaborative & Demographic Data
    $knnScores = $customer_id ? getKnnScores($pdo, $customer_id) : [];
    $collabScores = getCollaborativeScores($pdo);
    
    // Global Popularity Normalization
    $maxCollab = !empty($collabScores) ? max($collabScores) : 1;

    // 4. Contextual Features
    $currentTime = $context['time'] ?? date('H:i:s');
    $hour = (int)date('H', strtotime($currentTime));
    $viewingMovieId = $context['movie_id'] ?? null;
    
    $recommended = [];
    $now = time();

    foreach ($allMovies as $movie) {
        $mid = $movie['movie_id'];

        // Filter: Exclude already booked movies
        if (in_array($mid, $bookedMovieIds)) continue;
        
        // Filter: If "You Might Also Like" context, exclude the current movie
        if ($viewingMovieId && $mid == $viewingMovieId) continue;

        // --- ML SCORING COMPONENTS ---

        // A. Content Similarity (Cosine Similarity) - 40%
        $cosineScore = 0;
        $movieVector = [];
        foreach (movieTokens($movie) as $token) $movieVector[$token] = 1;

        if ($viewingMovieId) {
            $stmt = $pdo->prepare("SELECT * FROM movies WHERE movie_id = ? AND release_date <= CURDATE()");
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

        // B. Collaborative Filtering (Popularity) - 20%
        $collabScore = ($collabScores[$mid] ?? 0) / $maxCollab;

        // C. Trending Score (Popularity + Decay) - 15%
        // Newer movies that are getting popular get higher scores
        $releaseDateTs = strtotime($movie['release_date']);
        $daysSinceRelease = max(1, ($now - $releaseDateTs) / (60 * 60 * 24));
        $decay = exp(-0.01 * $daysSinceRelease); // Exponential decay factor
        $trendingScore = $collabScore * $decay;

        // D. KNN Demographic Matching - 15%
        $knnScore = 0;
        if (!empty($knnScores)) {
            $maxKnn = max($knnScores);
            $knnScore = ($knnScores[$mid] ?? 0) / ($maxKnn ?: 1);
        }

        // E. Contextual Awareness (Time of Day) - 10%
        $contextScore = 0;
        $genre = strtolower($movie['genre']);
        if (($hour >= 18 || $hour < 6) && preg_match('/horror|thriller|mystery|action/', $genre)) {
            $contextScore = 1;
        } elseif (($hour >= 6 && $hour < 18) && preg_match('/comedy|romance|family|animation|drama/', $genre)) {
            $contextScore = 1;
        }

        // --- FINAL HYBRID WEIGHTED SCORE ---
        $finalScore = ($cosineScore   * 0.40) + 
                      ($collabScore   * 0.20) + 
                      ($trendingScore * 0.15) + 
                      ($knnScore      * 0.15) + 
                      ($contextScore  * 0.10);

        $movie['ai_score'] = $finalScore;
        $recommended[] = $movie;
    }

    // Sort by AI score descending
    usort($recommended, function($a, $b) {
        return $b['ai_score'] <=> $a['ai_score'];
    });

    return array_slice($recommended, 0, $limit);
}
