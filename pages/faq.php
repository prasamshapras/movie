<?php
require_once '../includes/config.php';
$page_title = 'Frequently Asked Questions';
include '../includes/header.php';
?>

<div class="container">
    <div class="page-hero">
        <h1>FAQ</h1>
        <p>Everything you need to know about booking with Ticketly.</p>
    </div>

    <div class="page-content">
        <h2 class="section-title">Booking & Tickets</h2>
        <div class="faq-item">
            <div class="faq-question">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                How do I book a ticket on Ticketly?
            </div>
            <div class="faq-answer">
                Booking is simple: Select a movie from the homepage, choose your preferred showtime, pick your seats from the interactive map, and proceed to payment. Once payment is successful, your ticket will be available in your dashboard.
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-question">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Can I cancel my booking?
            </div>
            <div class="faq-answer">
                Currently, Ticketly does not support online cancellations. Once a ticket is booked and paid for, it is considered final. For exceptional cases, please contact our support team at least 4 hours before the showtime.
            </div>
        </div>

        <h2 class="section-title" style="margin-top: var(--spacing-2xl);">Seats & Selection</h2>
        <div class="faq-item">
            <div class="faq-question">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                What do the different seat colors mean?
            </div>
            <div class="faq-answer">
                <strong>White:</strong> Available for booking. <br>
                <strong>Purple:</strong> Currently selected by you. <br>
                <strong>Orange:</strong> Temporarily reserved by another user. <br>
                <strong>Grey:</strong> Already sold out or unavailable.
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-question">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                How long is a seat reserved while I pay?
            </div>
            <div class="faq-answer">
                Once you select a seat, it is temporarily reserved for 2 minutes. You must complete your payment within this timeframe, or the seat will be released back to the public.
            </div>
        </div>

        <h2 class="section-title" style="margin-top: var(--spacing-2xl);">Payment & Security</h2>
        <div class="faq-item">
            <div class="faq-question">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Which payment methods do you accept?
            </div>
            <div class="faq-answer">
                We currently accept payments via eSewa (Nepal's leading digital wallet). We are working on adding more payment gateways like Khalti and Credit/Debit cards soon.
            </div>
        </div>

        <h2 class="section-title" style="margin-top: var(--spacing-2xl);">Account & Login</h2>
        <div class="faq-item">
            <div class="faq-question">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Do I need an account to book tickets?
            </div>
            <div class="faq-answer">
                Yes, you need to create a Ticketly account to book tickets. This allows you to manage your bookings, receive e-tickets, and get personalized movie recommendations.
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
