document.addEventListener('DOMContentLoaded', function () {
    // Inject CSS
    const style = document.createElement('style');
    style.innerHTML = `
        .chatbot-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 320px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.2);
            display: none;
            flex-direction: column;
            overflow: hidden;
            z-index: 1000;
            font-family: 'Inter', sans-serif;
            border: 1px solid #e2e8f0;
        }
        .chatbot-container.active {
            display: flex;
        }
        .chatbot-header {
            background: #2563eb;
            color: #fff;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
        }
        .chatbot-close {
            background: none;
            border: none;
            color: #fff;
            font-size: 1.2rem;
            cursor: pointer;
        }
        .chatbot-messages {
            height: 300px;
            overflow-y: auto;
            padding: 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: #f8fafc;
        }
        .chatbot-message {
            max-width: 85%;
            padding: 10px 14px;
            border-radius: 14px;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        .chatbot-message.bot {
            background: #e2e8f0;
            color: #1e293b;
            align-self: flex-start;
            border-bottom-left-radius: 4px;
        }
        .chatbot-message.user {
            background: #2563eb;
            color: #fff;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }
        .chatbot-input {
            display: flex;
            border-top: 1px solid #e2e8f0;
            padding: 10px;
            background: #fff;
        }
        .chatbot-input input {
            flex: 1;
            border: none;
            outline: none;
            padding: 8px;
            font-size: 0.9rem;
            border-radius: 6px;
            background: #f1f5f9;
        }
        .chatbot-input button {
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0 15px;
            margin-left: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        .chatbot-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            background: #2563eb;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(37,99,235,0.4);
            z-index: 999;
            transition: transform 0.2s;
        }
        .chatbot-toggle:hover {
            transform: scale(1.05);
        }
        .chatbot-toggle.hidden {
            display: none;
        }
    `;
    document.head.appendChild(style);

    // Inject HTML
    const toggleBtn = document.createElement('div');
    toggleBtn.className = 'chatbot-toggle';
    toggleBtn.innerHTML = '💬';
    document.body.appendChild(toggleBtn);

    const chatContainer = document.createElement('div');
    chatContainer.className = 'chatbot-container';
    chatContainer.innerHTML = `
        <div class="chatbot-header">
            <span>Βοηθός Ιατρείου</span>
            <div>
                <button class="chatbot-minimize" style="background:none;border:none;color:#fff;font-size:1.2rem;cursor:pointer;margin-right:10px;" title="Ελαχιστοποίηση">−</button>
                <button class="chatbot-close" title="Τερματισμός">✖</button>
            </div>
        </div>
        <div class="chatbot-messages" id="chatMessages">
            <div class="chatbot-message bot">Γεια σας! Είμαι ο ψηφιακός βοηθός του ιατρείου. Μπορώ να κάνω <strong>Αλλαγή/Ακύρωση</strong> ραντεβού, ή να απαντήσω σε <strong>ερωτήσεις για θέματα υγείας</strong>. Πώς μπορώ να βοηθήσω;</div>
        </div>
        <div class="chatbot-input">
            <input type="text" id="chatInput" placeholder="Γράψτε εδώ...">
            <button id="chatSend">➜</button>
        </div>
    `;
    document.body.appendChild(chatContainer);

    // Logic
    const chatMessages = document.getElementById('chatMessages');
    const chatInput = document.getElementById('chatInput');
    const chatSend = document.getElementById('chatSend');
    const closeBtn = chatContainer.querySelector('.chatbot-close');
    const minBtn = chatContainer.querySelector('.chatbot-minimize');
    
    let hasInteracted = false;

    toggleBtn.addEventListener('click', () => {
        chatContainer.classList.add('active');
        toggleBtn.classList.add('hidden');
        chatInput.focus();
    });

    minBtn.addEventListener('click', () => {
        chatContainer.classList.remove('active');
        toggleBtn.classList.remove('hidden');
    });

    closeBtn.addEventListener('click', () => {
        if (hasInteracted && !confirm('Θέλετε σίγουρα να τερματίσετε τη συνομιλία; Η πρόοδός σας θα χαθεί.')) {
            return;
        }

        chatContainer.classList.remove('active');
        toggleBtn.classList.remove('hidden');
        
        // Reset session memory in backend
        fetch('chatbot_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'message=' + encodeURIComponent('reset_chat_session')
        }).then(() => {
            // Reset UI
            chatMessages.innerHTML = '<div class="chatbot-message bot">Γεια σας! Είμαι ο ψηφιακός βοηθός του ιατρείου. Μπορώ να κάνω <strong>Αλλαγή/Ακύρωση</strong> ραντεβού, ή να απαντήσω σε <strong>ερωτήσεις για θέματα υγείας</strong>. Πώς μπορώ να βοηθήσω;</div>';
            chatInput.value = '';
            hasInteracted = false;
        });
    });

    function addMessage(text, sender) {
        const msg = document.createElement('div');
        msg.className = `chatbot-message ${sender}`;
        msg.innerHTML = text; // using innerHTML for basic formatting
        chatMessages.appendChild(msg);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function sendMessage() {
        const text = chatInput.value.trim();
        if (!text) return;
        
        hasInteracted = true;
        
        addMessage(text, 'user');
        chatInput.value = '';

        // Disable input while waiting
        chatInput.disabled = true;
        chatSend.disabled = true;

        fetch('chatbot_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'message=' + encodeURIComponent(text)
        })
        .then(r => r.json())
        .then(data => {
            addMessage(data.reply, 'bot');
            chatInput.disabled = false;
            chatSend.disabled = false;
            chatInput.focus();
        })
        .catch(e => {
            addMessage('Σφάλμα δικτύου. Παρακαλώ προσπαθήστε ξανά.', 'bot');
            chatInput.disabled = false;
            chatSend.disabled = false;
            chatInput.focus();
        });
    }

    chatSend.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });
});
