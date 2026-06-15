<?php
// includes/footer.php
?>
    </main>

    <footer style="background: var(--gray-900); color: var(--gray-400); margin-top: var(--spacing-3xl); padding: var(--spacing-2xl) 0;">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-2xl);">
                <div>
                    <h4 style="color: white; margin-bottom: var(--spacing-md);">Ticketly</h4>
                    <p style="font-size: var(--font-size-sm);">
                        Your premier destination for movie ticket booking. Experience cinema like never before.
                    </p>
                </div>

                <div>
                    <h4 style="color: white; margin-bottom: var(--spacing-md);">Quick Links</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: var(--spacing-sm);">
                            <a href="<?= BASE_URL ?>/index.php" style="color: var(--gray-400); text-decoration: none;">Movies</a>
                        </li>
                        <li style="margin-bottom: var(--spacing-sm);">
                            <a href="<?= BASE_URL ?>/pages/about.php" style="color: var(--gray-400); text-decoration: none;">About Us</a>
                        </li>
                        <li style="margin-bottom: var(--spacing-sm);">
                            <a href="<?= BASE_URL ?>/pages/contact.php" style="color: var(--gray-400); text-decoration: none;">Contact</a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4 style="color: white; margin-bottom: var(--spacing-md);">Support</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: var(--spacing-sm);">
                            <a href="<?= BASE_URL ?>/pages/faq.php" style="color: var(--gray-400); text-decoration: none;">FAQ</a>
                        </li>
                        <li style="margin-bottom: var(--spacing-sm);">
                            <a href="<?= BASE_URL ?>/pages/terms.php" style="color: var(--gray-400); text-decoration: none;">Terms of Service</a>
                        </li>
                        <li style="margin-bottom: var(--spacing-sm);">
                            <a href="<?= BASE_URL ?>/pages/privacy.php" style="color: var(--gray-400); text-decoration: none;">Privacy Policy</a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4 style="color: white; margin-bottom: var(--spacing-md);">Connect</h4>
                    <p style="font-size: var(--font-size-sm);">Email: support@ticketly.com</p>
                    <p style="font-size: var(--font-size-sm);">Phone: +977 9840020101</p>
                </div>
            </div>

            <div style="text-align: center; margin-top: var(--spacing-2xl); padding-top: var(--spacing-lg); border-top: 1px solid var(--gray-800); font-size: var(--font-size-sm);">
                <p>&copy; <?= date('Y') ?> Ticketly. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Floating Chatbot -->
    <div class="chatbot-container">
        <button class="chatbot-toggle" id="chatbotToggle" type="button" title="Chat with Ticketly Assistant">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
        </button>

        <div class="chatbot-window" id="chatbotWindow">
            <div class="chatbot-header">
                <div>
                    <h4>Ticketly AI Assistant</h4>
                    <p>Ask about movies, seats, showtimes or booking</p>
                </div>
                <button class="chatbot-close" id="chatbotClose" type="button">&times;</button>
            </div>

            <div class="chatbot-messages" id="chatbotMessages">
                <div class="chatbot-message bot">
                    Hi! I am Ticketly Assistant. How can I help you today?
                </div>
            </div>

            <div class="chatbot-input-area">
                <input 
                    type="text" 
                    class="chatbot-input" 
                    id="chatbotInput" 
                    placeholder="Type your message..."
                    autocomplete="off"
                >
                <button class="chatbot-send" id="chatbotSend" type="button">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <style>
        .chatbot-container {
            position: fixed;
            right: 28px;
            bottom: 28px;
            z-index: 99999;
            font-family: inherit;
        }

        .chatbot-toggle {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            border: none;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 14px 35px rgba(79, 70, 229, 0.38);
            transition: all 0.25s ease;
        }

        .chatbot-toggle:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 42px rgba(79, 70, 229, 0.5);
        }

        .chatbot-window {
            position: absolute;
            right: 0;
            bottom: 82px;
            width: 370px;
            max-width: calc(100vw - 40px);
            background: white;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 20px 55px rgba(15, 23, 42, 0.28);
            border: 1px solid #e2e8f0;
            display: none;
        }

        .chatbot-window.active {
            display: block;
            animation: chatbotOpen 0.22s ease;
        }

        @keyframes chatbotOpen {
            from {
                opacity: 0;
                transform: translateY(14px) scale(0.96);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .chatbot-header {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            padding: 1rem;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }

        .chatbot-header h4 {
            margin: 0;
            color: white;
            font-size: 1rem;
            font-weight: 700;
        }

        .chatbot-header p {
            margin: 0.25rem 0 0;
            font-size: 0.78rem;
            color: rgba(255,255,255,0.85);
        }

        .chatbot-close {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: none;
            background: rgba(255,255,255,0.18);
            color: white;
            font-size: 20px;
            cursor: pointer;
            line-height: 1;
        }

        .chatbot-close:hover {
            background: rgba(255,255,255,0.28);
        }

        .chatbot-messages {
            height: 300px;
            overflow-y: auto;
            padding: 1rem;
            background: #f8fafc;
        }

        .chatbot-message {
            max-width: 82%;
            padding: 0.75rem 0.9rem;
            border-radius: 14px;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
            line-height: 1.45;
            white-space: pre-line;
            word-wrap: break-word;
        }

        .chatbot-message.bot {
            background: white;
            color: #334155;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 5px;
        }

        .chatbot-message.user {
            background: #4f46e5;
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }

        .chatbot-message.loading {
            background: white;
            color: #64748b;
            border: 1px solid #e2e8f0;
            font-style: italic;
        }

        .chatbot-input-area {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.9rem;
            background: white;
            border-top: 1px solid #e2e8f0;
        }

        .chatbot-input {
            flex: 1;
            height: 42px;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 0 1rem;
            outline: none;
            font-size: 0.9rem;
        }

        .chatbot-input:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        }

        .chatbot-send {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: none;
            background: #4f46e5;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chatbot-send:hover {
            background: #4338ca;
        }

        @media (max-width: 480px) {
            .chatbot-container {
                right: 16px;
                bottom: 18px;
            }

            .chatbot-window {
                right: 0;
                bottom: 76px;
                width: calc(100vw - 32px);
            }

            .chatbot-toggle {
                width: 58px;
                height: 58px;
            }
        }
    </style>

    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/chatbot.js"></script>
</body>
</html>