<?php
// includes/header.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Book movie tickets online - Ticketly">
    <title><?= $page_title ?? 'Ticketly' ?> - Movie Ticket Booking</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/pages.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="<?= BASE_URL ?>/index.php" class="brand">Ticketly</a>
                <nav class="nav">
                    <a href="<?= BASE_URL ?>/index.php">Movies</a>
                    <?php if(isLoggedIn()): ?>
                        <a href="<?= BASE_URL ?>/dashboard.php">My Bookings</a>
                        <?php if(isAdminLoggedIn()): ?>
                            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline" style="padding: 0.5rem 1rem;">Admin Panel</a>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline">Sign Out</a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline">Sign In</a>
                        <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary">Sign Up</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>
    <main>