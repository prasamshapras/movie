<?php
require_once '../includes/config.php';
$page_title = 'Contact Us';
include '../includes/header.php';
?>

<div class="container">
    <div class="page-hero">
        <h1>Contact Us</h1>
        <p>Have questions or need assistance? Our support team is here to help you.</p>
    </div>

    <div class="page-content">
        <div class="contact-grid">
            <aside class="contact-info-card">
                <h2 class="section-title" style="border-bottom: none;">Get in Touch</h2>
                <p style="margin-bottom: var(--spacing-xl); color: var(--gray-600);">Feel free to reach out to us through any of these channels. We're available 7 days a week.</p>
                
                <div class="contact-method">
                    <div class="contact-icon">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 style="margin: 0; color: var(--gray-900);">Email Support</h4>
                        <p style="margin: 0; font-size: var(--font-size-sm);">support@ticketly.com</p>
                    </div>
                </div>

                <div class="contact-method">
                    <div class="contact-icon">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 style="margin: 0; color: var(--gray-900);">Call Us</h4>
                        <p style="margin: 0; font-size: var(--font-size-sm);">+977 9840020101</p>
                    </div>
                </div>

                <div class="contact-method">
                    <div class="contact-icon">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 style="margin: 0; color: var(--gray-900);">Location</h4>
                        <p style="margin: 0; font-size: var(--font-size-sm);">Kathmandu, Nepal</p>
                    </div>
                </div>
            </aside>

            <main>
                <h2 class="section-title">Send us a Message</h2>
                <form class="contact-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg);">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-input" placeholder="Enter your name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-input" placeholder="Enter your email">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subject</label>
                        <select class="form-select">
                            <option value="">Select a topic</option>
                            <option value="booking">Booking Issue</option>
                            <option value="payment">Payment Issue</option>
                            <option value="account">Account Support</option>
                            <option value="feedback">General Feedback</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Message</label>
                        <textarea class="form-textarea" rows="6" placeholder="How can we help you?"></textarea>
                    </div>
                    <button type="button" class="btn btn-primary btn-block" style="padding: 1rem;">Send Message</button>
                </form>
            </main>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
