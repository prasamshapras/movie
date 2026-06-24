<?php
require_once '../includes/config.php';
require_once '../includes/automation.php';

if (!isAdminLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Run automation cleanup
cleanupPastShows($pdo);

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Admin Panel' ?> - Ticketly</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .admin-sidebar {
            width: 280px;
            background: #1e293b;
            color: #94a3b8;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 100;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #334155;
        }

        .sidebar-header h2 {
            color: white;
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }

        .sidebar-header p {
            font-size: 0.875rem;
            color: #94a3b8;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .nav-item:hover {
            background: #334155;
            color: white;
        }

        .nav-item.active {
            background: #4f46e5;
            color: white;
            border-left-color: #818cf8;
        }

        .nav-icon {
            width: 20px;
            height: 20px;
        }

        .admin-main {
            flex: 1;
            margin-left: 280px;
        }

        .admin-header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .admin-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #0f172a;
        }

        .admin-content {
            padding: 1.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .stat-label {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #4f46e5;
        }

        .card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .card-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .card-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #0f172a;
        }

        .card-body {
            padding: 1.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #4f46e5;
            color: white;
        }

        .btn-primary:hover {
            background: #4338ca;
        }

        .btn-secondary {
            background: #64748b;
            color: white;
        }

        .btn-secondary:hover {
            background: #475569;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid #e2e8f0;
            color: #475569;
        }

        .btn-outline:hover {
            border-color: #4f46e5;
            color: #4f46e5;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
        }

        .btn-group {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 0.75rem;
            background: #f8fafc;
            font-weight: 600;
            color: #475569;
            font-size: 0.875rem;
        }

        td {
            padding: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 0.375rem;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background: #fed7aa;
            color: #92400e;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            margin-right: 1rem;
        }

        .search-section {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .search-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .search-body {
            padding: 1.5rem;
        }

        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
        }

        .search-group {
            flex: 1;
            min-width: 200px;
        }

        .search-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #475569;
            font-size: 0.875rem;
        }

        .search-input,
        .search-select {
            width: 100%;
            padding: 0.625rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 0.875rem;
        }

        input,
        select,
        textarea {
            font-family: inherit;
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            
            .admin-sidebar.show {
                transform: translateX(0);
            }
            
            .admin-main {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <aside class="admin-sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>Ticketly</h2>
                <p>Administration Panel</p>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Dashboard
                </a>

                <a href="movies.php" class="nav-item <?= ($current_page == 'movies.php' || $current_page == 'edit_movie.php') ? 'active' : '' ?>">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                    </svg>
                    Movies
                </a>

                <a href="add_movie.php" class="nav-item <?= $current_page == 'add_movie.php' ? 'active' : '' ?>">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Movie
                </a>

                <a href="add_showtime.php" class="nav-item <?= $current_page == 'add_showtime.php' ? 'active' : '' ?>">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Add Showtime
                </a>

                <a href="showtimes.php" class="nav-item <?= ($current_page == 'showtimes.php' || $current_page == 'edit_showtime.php') ? 'active' : '' ?>">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Manage Showtimes
                </a>

                <a href="bookings.php" class="nav-item <?= $current_page == 'bookings.php' ? 'active' : '' ?>">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Bookings
                </a>
            </nav>
        </aside>

        <main class="admin-main">
            <div class="admin-header">
                <div style="display: flex; align-items: center;">
                    <button class="menu-toggle" id="menuToggle">☰</button>
                    <h1><?= $page_title ?? 'Dashboard' ?></h1>
                </div>

                <div>
                    <span style="color: #64748b; margin-right: 1rem;">
                        Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>
                    </span>
                    <a href="../logout.php" class="btn btn-outline">Logout</a>
                </div>
            </div>

            <div class="admin-content">
                <?php if (isset($_SESSION['error'])): ?>
                    <div style="background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; border: 1px solid #fecaca;">
                        <?= htmlspecialchars($_SESSION['error']) ?>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; border: 1px solid #a7f3d0;">
                        <?= htmlspecialchars($_SESSION['success']) ?>
                        <?php unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>