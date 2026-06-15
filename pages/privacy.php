<?php
require_once '../includes/config.php';
$page_title = 'Privacy Policy';
include '../includes/header.php';
?>

<div class="container">
    <div class="page-hero">
        <h1>Privacy Policy</h1>
        <p>Your privacy is important to us. Learn how we handle your data.</p>
    </div>

    <div class="page-content">
        <section class="content-block">
            <h2 class="section-title">1. Information We Collect</h2>
            <p>
                When you use Ticketly, we collect information that you provide directly to us, such as when you create an account (name, email, phone number) and when you make a booking (movie selection, showtime, and seat preferences). We do not store your full payment card details on our servers; payments are processed securely by our third-party partners.
            </p>
        </section>

        <section class="content-block">
            <h2 class="section-title">2. How We Use Your Data</h2>
            <p>
                We use the information we collect to:
                <ul style="margin-left: 1.5rem; margin-top: 0.5rem; color: var(--gray-600);">
                    <li>Process your ticket bookings and payments.</li>
                    <li>Send you booking confirmations and reminders.</li>
                    <li>Provide personalized movie recommendations.</li>
                    <li>Improve our platform's user experience and security.</li>
                    <li>Respond to your support requests and feedback.</li>
                </ul>
            </p>
        </section>

        <section class="content-block">
            <h2 class="section-title">3. Data Protection</h2>
            <p>
                We implement industry-standard security measures to protect your personal data from unauthorized access, disclosure, or destruction. This includes using SSL encryption for all data transmissions and securing our database with restricted access protocols.
            </p>
        </section>

        <section class="content-block">
            <h2 class="section-title">4. Third-Party Sharing</h2>
            <p>
                We do not sell your personal information to third parties. We only share necessary data with trusted partners to facilitate our services, such as payment gateways (eSewa) and email service providers (PHPMailer). These partners are obligated to keep your information confidential.
            </p>
        </section>

        <section class="content-block">
            <h2 class="section-title">5. Your Rights</h2>
            <p>
                You have the right to access, update, or delete your account information at any time through your dashboard. If you wish to permanently delete your data or have any questions about our privacy practices, please contact our support team.
            </p>
        </section>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
