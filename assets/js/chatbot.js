document.addEventListener('DOMContentLoaded', function () {
    const chatbotToggle = document.getElementById('chatbotToggle');
    const chatbotWindow = document.getElementById('chatbotWindow');
    const chatbotClose = document.getElementById('chatbotClose');
    const chatbotInput = document.getElementById('chatbotInput');
    const chatbotSend = document.getElementById('chatbotSend');
    const chatbotMessages = document.getElementById('chatbotMessages');

    if (!chatbotToggle || !chatbotWindow || !chatbotInput || !chatbotSend || !chatbotMessages) {
        return;
    }

    chatbotToggle.addEventListener('click', function () {
        chatbotWindow.classList.toggle('active');

        if (chatbotWindow.classList.contains('active')) {
            setTimeout(function () {
                chatbotInput.focus();
            }, 150);
        }
    });

    if (chatbotClose) {
        chatbotClose.addEventListener('click', function () {
            chatbotWindow.classList.remove('active');
        });
    }

    chatbotSend.addEventListener('click', sendMessage);

    chatbotInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    function addMessage(message, type) {
        const div = document.createElement('div');
        div.className = 'chatbot-message ' + type;
        div.textContent = message;

        chatbotMessages.appendChild(div);
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;

        return div;
    }

    function sendMessage() {
        const message = chatbotInput.value.trim();

        if (message === '') {
            return;
        }

        addMessage(message, 'user');

        chatbotInput.value = '';
        chatbotInput.focus();

        const loadingMessage = addMessage('Typing...', 'loading');

        fetch('ajax/chatbot.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                message: message
            })
        })
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            loadingMessage.remove();

            if (data && data.reply) {
                addMessage(data.reply, 'bot');
            } else {
                addMessage('Sorry, I could not understand that.', 'bot');
            }
        })
        .catch(function () {
            loadingMessage.remove();
            addMessage('Sorry, something went wrong. Please try again.', 'bot');
        });
    }
});