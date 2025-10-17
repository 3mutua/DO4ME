class ChatManager {
    constructor(taskId, currentUserId) {
        this.taskId = taskId;
        this.currentUserId = currentUserId;
        this.ws = null;
        this.isConnected = false;
        this.typingTimeout = null;
        
        this.init();
    }
    
    init() {
        this.setupWebSocket();
        this.setupEventListeners();
        this.loadChatHistory();
    }
    
    setupWebSocket() {
        try {
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            this.ws = new WebSocket(`${protocol}//${window.location.host}/ws/${this.currentUserId}`);
            
            this.ws.onopen = () => {
                this.isConnected = true;
                console.log('WebSocket connected for chat');
            };
            
            this.ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                this.handleIncomingMessage(data);
            };
            
            this.ws.onclose = () => {
                this.isConnected = false;
                console.log('WebSocket disconnected. Attempting to reconnect...');
                setTimeout(() => this.setupWebSocket(), 3000);
            };
        } catch (error) {
            console.error('WebSocket connection failed:', error);
        }
    }
    
    setupEventListeners() {
        const chatForm = document.getElementById('chat-form');
        const messageInput = document.getElementById('message-input');
        
        if (chatForm && messageInput) {
            chatForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.sendMessage();
            });
            
            messageInput.addEventListener('input', () => {
                this.sendTypingIndicator(true);
                clearTimeout(this.typingTimeout);
                this.typingTimeout = setTimeout(() => {
                    this.sendTypingIndicator(false);
                }, 1000);
            });
            
            messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
        }
    }
    
    async loadChatHistory() {
        try {
            const response = await app.apiCall(`/api/tasks/${this.taskId}/messages`);
            if (response.success) {
                this.renderMessages(response.messages);
            }
        } catch (error) {
            console.error('Failed to load chat history:', error);
        }
    }
    
    sendMessage() {
        const messageInput = document.getElementById('message-input');
        const message = messageInput.value.trim();
        
        if (!message || !this.isConnected) return;
        
        const messageData = {
            type: 'chat_message',
            task_id: this.taskId,
            message: message,
            timestamp: new Date().toISOString()
        };
        
        this.ws.send(JSON.stringify(messageData));
        
        // Clear input and send typing stop
        messageInput.value = '';
        this.sendTypingIndicator(false);
        
        // Add message to UI immediately (optimistic update)
        this.addMessageToUI({
            id: 'temp-' + Date.now(),
            sender_id: this.currentUserId,
            message: message,
            created_at: new Date().toISOString(),
            is_own: true
        });
    }
    
    sendTypingIndicator(isTyping) {
        if (!this.isConnected) return;
        
        const typingData = {
            type: 'typing_indicator',
            task_id: this.taskId,
            is_typing: isTyping
        };
        
        this.ws.send(JSON.stringify(typingData));
    }
    
    handleIncomingMessage(data) {
        switch (data.type) {
            case 'chat_message':
                this.addMessageToUI({
                    id: data.message_id || 'remote-' + Date.now(),
                    sender_id: data.from_user_id,
                    message: data.message,
                    created_at: data.timestamp,
                    is_own: data.from_user_id === this.currentUserId
                });
                break;
                
            case 'typing_indicator':
                this.showTypingIndicator(data.user_id, data.is_typing);
                break;
                
            case 'task_update':
                this.showTaskUpdateNotification(data);
                break;
        }
    }
    
    addMessageToUI(message) {
        const chatMessages = document.getElementById('chat-messages');
        if (!chatMessages) return;
        
        const messageElement = this.createMessageElement(message);
        
        // Remove temporary message if exists
        if (message.is_own && message.id.startsWith('temp-')) {
            const tempMessage = chatMessages.querySelector(`[data-temp-id="${message.id}"]`);
            if (tempMessage) {
                tempMessage.remove();
            }
            messageElement.dataset.tempId = message.id;
        }
        
        chatMessages.appendChild(messageElement);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        // Mark as read if it's not our own message
        if (!message.is_own) {
            this.markMessageAsRead(message.id);
        }
    }
    
    createMessageElement(message) {
        const div = document.createElement('div');
        div.className = `message ${message.is_own ? 'own-message' : 'other-message'}`;
        
        const timestamp = new Date(message.created_at).toLocaleTimeString();
        
        div.innerHTML = `
            <div class="message-content">
                <div class="message-text">${this.escapeHtml(message.message)}</div>
                <div class="message-time">${timestamp}</div>
            </div>
        `;
        
        return div;
    }
    
    showTypingIndicator(userId, isTyping) {
        const typingIndicator = document.getElementById('typing-indicator');
        if (!typingIndicator) return;
        
        if (isTyping) {
            typingIndicator.style.display = 'block';
            typingIndicator.textContent = 'User is typing...';
        } else {
            typingIndicator.style.display = 'none';
        }
    }
    
    showTaskUpdateNotification(data) {
        app.showNotification(`Task update: ${data.message}`, 'info');
    }
    
    async markMessageAsRead(messageId) {
        try {
            await app.apiCall(`/api/messages/${messageId}/read`, {
                method: 'POST'
            });
        } catch (error) {
            console.error('Failed to mark message as read:', error);
        }
    }
    
    renderMessages(messages) {
        const chatMessages = document.getElementById('chat-messages');
        if (!chatMessages) return;
        
        chatMessages.innerHTML = messages.map(message => 
            this.createMessageElement({
                ...message,
                is_own: message.sender_id === this.currentUserId
            }).outerHTML
        ).join('');
        
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize chat when page loads
document.addEventListener('DOMContentLoaded', () => {
    const chatContainer = document.getElementById('chat-container');
    if (chatContainer && app.currentUser) {
        const taskId = chatContainer.dataset.taskId;
        window.chatManager = new ChatManager(taskId, app.currentUser.id);
    }
});