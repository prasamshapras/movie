<?php
require_once '../includes/config.php';
$page_title = 'About Us';
include '../includes/header.php';
?>

<div class="container">
    <div class="page-hero">
        <h1>About Ticketly</h1>
        <p>Your ultimate destination for seamless movie ticket booking and cinematic experiences.</p>
    </div>

    <div class="page-content">
        <section class="content-block">
            <h2 class="section-title">Who We Are</h2>
            <p>
                Ticketly is a state-of-the-art online movie ticket booking platform designed to bring the magic of cinema directly to your fingertips. Whether you're a fan of action-packed blockbusters, heart-warming dramas, or spine-chilling thrillers, Ticketly provides a unified platform to browse, discover, and book tickets for your favorite movies with absolute ease.
            </p>
        </section>

        <section class="content-block">
            <h2 class="section-title">What We Offer</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-xl); margin-top: var(--spacing-lg);">
                <div class="card" style="padding: var(--spacing-lg); text-align: center;">
                    <div style="color: var(--primary-600); margin-bottom: var(--spacing-md);">
                        <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <h3>Smart Browsing</h3>
                    <p style="font-size: var(--font-size-sm);">Explore a wide range of movies with detailed information, trailers, and ratings.</p>
                </div>
                <div class="card" style="padding: var(--spacing-lg); text-align: center;">
                    <div style="color: var(--primary-600); margin-bottom: var(--spacing-md);">
                        <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                        </svg>
                    </div>
                    <h3>Seat Selection</h3>
                    <p style="font-size: var(--font-size-sm);">Interactive seat maps allow you to pick your favorite spot in the theater in real-time.</p>
                </div>
                <div class="card" style="padding: var(--spacing-lg); text-align: center;">
                    <div style="color: var(--primary-600); margin-bottom: var(--spacing-md);">
                        <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <h3>Secure Payment</h3>
                    <p style="font-size: var(--font-size-sm);">Multiple payment options including eSewa, ensuring your transactions are safe and fast.</p>
                </div>
                <div class="card" style="padding: var(--spacing-lg); text-align: center;">
                    <div style="color: var(--primary-600); margin-bottom: var(--spacing-md);">
                        <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <h3>AI Recommendations</h3>
                    <p style="font-size: var(--font-size-sm);">Our smart algorithm suggests movies you'll love based on your past bookings.</p>
                </div>
            </div>
        </section>

        <section class="content-block">
            <h2 class="section-title">Our Mission</h2>
            <p>
                Our mission is to simplify the entertainment experience by eliminating long queues and the uncertainty of ticket availability. We strive to provide a user-friendly platform that is accessible to everyone, from tech-savvy enthusiasts to casual moviegoers. At Ticketly, we believe that the journey to the theater should be as enjoyable as the movie itself.
            </p>
        </section>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
