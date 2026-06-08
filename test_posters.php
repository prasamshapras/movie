<?php
require_once 'includes/config.php';

// Fetch movie title and poster URL
$sql = "SELECT title, poster FROM movies";
$result = $pdo->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Movie Posters Test</title>
</head>
<body>
    <h1>Movie Posters</h1>

    <?php
    $moviesList = $result->fetchAll(PDO::FETCH_ASSOC);
    if (count($moviesList) > 0) {
        foreach($moviesList as $row) {
            echo "<div style='margin-bottom:20px;'>";
            echo "<h3>" . htmlspecialchars($row['title']) . "</h3>";
            // Check if poster URL is not empty
            if (!empty($row['poster'])) {
                echo "<img src='" . htmlspecialchars(BASE_URL . '/' . $row['poster']) . "' alt='" . htmlspecialchars($row['title']) . " Poster' style='width:200px;'>";
            } else {
                echo "<p>No poster available</p>";
            }
            echo "</div>";
        }
    } else {
        echo "<p>No movies found in the database.</p>";
    }
    ?>

</body>
</html>
