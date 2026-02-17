<?php
// messages.php
session_start();

// Check if user is logged in
#if (!isset($_SESSION['user_id']) && !isset($_COOKIE['jwt_token'])) {
 #   header('Location: login.php');
  #  exit();
#}

$pageTitle = "Messages - RJIT Alumni Portal";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Roboto+Slab:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        .message-active {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
        }
        .message-unread {
            font-weight: 600;
        }
        .chat-bubble-left {
            border-radius: 18px 18px 18px 4px;
        }
        .chat-bubble-right {
            border-radius: 18px 18px 4px 18px;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Include Header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Messages</h1>
                <p class="text-gray-600">Connect with alumni, students, and faculty</p>
            </div>
            
            <div class="flex flex-col lg:flex-row gap-6">
                <!-- Left Sidebar - Conversations -->
                <div class="lg:w-1/3 bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-4 border-b border-gray-200">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold text-gray-900">Conversations</h2>
                            <button id="newMessageBtn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center">
                                <i data-lucide="plus" class="h-4 w-4 mr-2"></i>
                                New Message
                            </button>
                        </div>
                        
                        <!-- Search -->
                        <div class="relative">
                            <i data-lucide="search" class="absolute left-3 top-3 h-4 w-4 text-gray-400"></i>
                            <input type="text" 
                                   id="searchConversations" 
                                   placeholder="Search conversations..." 
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <!-- Conversations List -->
                    <div id="conversationsList" class="overflow-y-auto max-h-[calc(100vh-300px)]">
                        <!-- Conversations will be loaded here -->
                        <div class="text-center py-8">
                            <i data-lucide="message-square" class="h-12 w-12 text-gray-300 mx-auto mb-4"></i>
                            <p class="text-gray-500">No conversations yet</p>
                            <p class="text-sm text-gray-400 mt-1">Start a conversation with someone!</p>
                        </div>
                    </div>
                </div>
                
                <!-- Right Side - Chat Area -->
                <div class="lg:w-2/3 bg-white rounded-xl shadow-sm border border-gray-200">
                    <!-- Chat Header -->
                    <div id="chatHeader" class="p-4 border-b border-gray-200 hidden">
                        <div class="flex items-center">
                            <img id="chatUserImage" src="https://via.placeholder.com/40" alt="User" class="h-10 w-10 rounded-full">
                            <div class="ml-3">
                                <h2 id="chatUserName" class="font-bold text-gray-900">Select a conversation</h2>
                                <p id="chatUserStatus" class="text-sm text-gray-600">Loading...</p>
                            </div>
                            <div class="ml-auto flex space-x-2">
                                <button class="p-2 hover:bg-gray-100 rounded-lg">
                                    <i data-lucide="phone" class="h-5 w-5 text-gray-600"></i>
                                </button>
                                <button class="p-2 hover:bg-gray-100 rounded-lg">
                                    <i data-lucide="video" class="h-5 w-5 text-gray-600"></i>
                                </button>
                                <button class="p-2 hover:bg-gray-100 rounded-lg">
                                    <i data-lucide="info" class="h-5 w-5 text-gray-600"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Messages Area -->
                    <div id="messagesArea" class="p-4 h-[400px] overflow-y-auto">
                        <!-- Welcome message when no conversation selected -->
                        <div class="h-full flex items-center justify-center text-center">
                            <div>
                                <i data-lucide="message-square" class="h-16 w-16 text-gray-300 mx-auto mb-4"></i>
                                <h3 class="text-xl font-semibold text-gray-700 mb-2">Select a conversation</h3>
                                <p class="text-gray-500">Choose a conversation from the list to start messaging</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Message Input (Hidden initially) -->
                    <div id="messageInputContainer" class="p-4 border-t border-gray-200 hidden">
                        <div class="flex items-center">
                            <button class="p-2 hover:bg-gray-100 rounded-lg mr-2">
                                <i data-lucide="paperclip" class="h-5 w-5 text-gray-600"></i>
                            </button>
                            <div class="flex-1 relative">
                                <input type="text" 
                                       id="messageInput" 
                                       placeholder="Type your message..." 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       disabled>
                                <button class="absolute right-3 top-2.5">
                                    <i data-lucide="smile" class="h-5 w-5 text-gray-400"></i>
                                </button>
                            </div>
                            <button id="sendMessageBtn" class="ml-3 bg-blue-600 text-white p-3 rounded-full hover:bg-blue-700" disabled>
                                <i data-lucide="send" class="h-5 w-5"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- New Message Modal -->
    <div id="newMessageModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-xl shadow-lg max-w-md w-full mx-4">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900">New Message</h3>
                    <button id="closeModalBtn" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                
                <!-- Search Users -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search User</label>
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3 top-3 h-4 w-4 text-gray-400"></i>
                        <input type="text" 
                               id="searchUserInput" 
                               placeholder="Search by name or email..." 
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <!-- User List -->
                <div id="userList" class="max-h-64 overflow-y-auto">
                    <!-- Users will be loaded here -->
                    <div class="text-center py-4 text-gray-500">
                        <i data-lucide="users" class="h-8 w-8 mx-auto mb-2"></i>
                        <p>Search for users to message</p>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button id="cancelBtn" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 mr-3">
                        Cancel
                    </button>
                    <button id="startChatBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" disabled>
                        Start Chat
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Initialize icons
        lucide.createIcons();
        
        // State variables
        let currentConversationId = null;
        let selectedUserId = null;
        
        // DOM Elements
        const newMessageBtn = document.getElementById('newMessageBtn');
        const newMessageModal = document.getElementById('newMessageModal');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const searchUserInput = document.getElementById('searchUserInput');
        const startChatBtn = document.getElementById('startChatBtn');
        const chatHeader = document.getElementById('chatHeader');
        const messagesArea = document.getElementById('messagesArea');
        const messageInputContainer = document.getElementById('messageInputContainer');
        const messageInput = document.getElementById('messageInput');
        const sendMessageBtn = document.getElementById('sendMessageBtn');
        
        // Event Listeners
        newMessageBtn.addEventListener('click', () => {
            newMessageModal.classList.remove('hidden');
            loadUsers();
        });
        
        closeModalBtn.addEventListener('click', () => {
            newMessageModal.classList.add('hidden');
        });
        
        cancelBtn.addEventListener('click', () => {
            newMessageModal.classList.add('hidden');
        });
        
        searchUserInput.addEventListener('input', (e) => {
            searchUsers(e.target.value);
        });
        
        startChatBtn.addEventListener('click', () => {
            if (selectedUserId) {
                startNewConversation(selectedUserId);
                newMessageModal.classList.add('hidden');
            }
        });
        
        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        sendMessageBtn.addEventListener('click', sendMessage);
        
        // Functions
        async function loadConversations() {
            try {
                const token = localStorage.getItem('jwt_token');
                const response = await fetch('api/get_conversations.php', {
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                });
                
                const data = await response.json();
                const conversationsList = document.getElementById('conversationsList');
                
                if (data.success && data.data && data.data.length > 0) {
                    conversationsList.innerHTML = data.data.map(conv => `
                        <div class="conversation-item p-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer ${conv.unread_count > 0 ? 'message-unread' : ''}" 
                             data-conversation-id="${conv.conversation_id}" 
                             data-user-id="${conv.other_user_id}"
                             onclick="selectConversation('${conv.conversation_id}', '${conv.other_user_id}')">
                            <div class="flex items-center">
                                <img src="${conv.profile_picture_url || 'https://via.placeholder.com/40'}" 
                                     alt="${conv.full_name}" 
                                     class="h-10 w-10 rounded-full">
                                <div class="ml-3 flex-1">
                                    <div class="flex justify-between">
                                        <h3 class="font-semibold text-gray-900">${conv.full_name}</h3>
                                        <span class="text-xs text-gray-500">${formatTime(conv.last_message_at)}</span>
                                    </div>
                                    <p class="text-sm text-gray-600 truncate">${conv.last_message || 'No messages yet'}</p>
                                    ${conv.unread_count > 0 ? `
                                        <span class="inline-block mt-1 px-2 py-0.5 bg-blue-600 text-white text-xs rounded-full">
                                            ${conv.unread_count}
                                        </span>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    conversationsList.innerHTML = `
                        <div class="text-center py-8">
                            <i data-lucide="message-square" class="h-12 w-12 text-gray-300 mx-auto mb-4"></i>
                            <p class="text-gray-500">No conversations yet</p>
                            <p class="text-sm text-gray-400 mt-1">Start a conversation with someone!</p>
                        </div>
                    `;
                }
                
                lucide.createIcons();
            } catch (error) {
                console.error('Error loading conversations:', error);
            }
        }
        
        async function loadUsers(searchTerm = '') {
            try {
                const token = localStorage.getItem('jwt_token');
                const response = await fetch(`api/search_users.php?q=${encodeURIComponent(searchTerm)}`, {
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                });
                
                const data = await response.json();
                const userList = document.getElementById('userList');
                
                if (data.success && data.data && data.data.length > 0) {
                    userList.innerHTML = data.data.map(user => `
                        <div class="user-item p-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer flex items-center"
                             data-user-id="${user.user_id}"
                             onclick="selectUser('${user.user_id}')">
                            <img src="${user.profile_picture_url || 'https://via.placeholder.com/40'}" 
                                 alt="${user.full_name}" 
                                 class="h-10 w-10 rounded-full">
                            <div class="ml-3">
                                <h4 class="font-medium text-gray-900">${user.full_name}</h4>
                                <p class="text-sm text-gray-600">${user.role} • ${user.branch || ''}</p>
                            </div>
                        </div>
                    `).join('');
                } else {
                    userList.innerHTML = `
                        <div class="text-center py-4 text-gray-500">
                            <i data-lucide="users" class="h-8 w-8 mx-auto mb-2"></i>
                            <p>${searchTerm ? 'No users found' : 'Search for users to message'}</p>
                        </div>
                    `;
                }
                
                lucide.createIcons();
            } catch (error) {
                console.error('Error loading users:', error);
            }
        }
        
        function selectUser(userId) {
            selectedUserId = userId;
            
            // Remove previous selection
            document.querySelectorAll('.user-item').forEach(item => {
                item.classList.remove('bg-blue-50', 'border-blue-200');
            });
            
            // Add selection to clicked item
            const selectedItem = document.querySelector(`[data-user-id="${userId}"]`);
            if (selectedItem) {
                selectedItem.classList.add('bg-blue-50', 'border-blue-200');
            }
            
            // Enable start chat button
            startChatBtn.disabled = false;
        }
        
        async function startNewConversation(userId) {
            try {
                const token = localStorage.getItem('jwt_token');
                const response = await fetch('api/create_conversation.php', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ other_user_id: userId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Reload conversations and select the new one
                    loadConversations();
                    selectConversation(data.conversation_id, userId);
                } else {
                    alert(data.message || 'Failed to start conversation');
                }
            } catch (error) {
                console.error('Error starting conversation:', error);
                alert('Error starting conversation');
            }
        }
        
        async function selectConversation(conversationId, userId) {
            currentConversationId = conversationId;
            
            // Update UI
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('message-active');
            });
            
            const selectedItem = document.querySelector(`[data-conversation-id="${conversationId}"]`);
            if (selectedItem) {
                selectedItem.classList.add('message-active');
            }
            
            // Show chat area
            chatHeader.classList.remove('hidden');
            messageInputContainer.classList.remove('hidden');
            messageInput.disabled = false;
            sendMessageBtn.disabled = false;
            
            // Load conversation details and messages
            await loadConversationDetails(userId);
            await loadMessages(conversationId);
        }
        
        async function loadConversationDetails(userId) {
            try {
                const token = localStorage.getItem('jwt_token');
                const response = await fetch(`api/get_user.php?id=${userId}`, {
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const user = data.data;
                    document.getElementById('chatUserImage').src = user.profile_picture_url || 'https://via.placeholder.com/40';
                    document.getElementById('chatUserName').textContent = user.full_name;
                    document.getElementById('chatUserStatus').textContent = `${user.role} • ${user.branch || 'RJIT Alumni'}`;
                }
            } catch (error) {
                console.error('Error loading conversation details:', error);
            }
        }
        
        async function loadMessages(conversationId) {
            try {
                const token = localStorage.getItem('jwt_token');
                const response = await fetch(`api/get_messages.php?conversation_id=${conversationId}`, {
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                });
                
                const data = await response.json();
                
                if (data.success && data.data) {
                    const messages = data.data;
                    const userData = JSON.parse(localStorage.getItem('user_data'));
                    const currentUserId = userData.user_id;
                    
                    messagesArea.innerHTML = messages.map(msg => {
                        const isCurrentUser = msg.sender_id == currentUserId;
                        return `
                            <div class="flex mb-4 ${isCurrentUser ? 'justify-end' : ''}">
                                ${!isCurrentUser ? `
                                    <img src="${msg.sender_profile_picture || 'https://via.placeholder.com/32'}" 
                                         alt="User" 
                                         class="h-8 w-8 rounded-full mt-1">
                                ` : ''}
                                
                                <div class="${isCurrentUser ? 'mr-3 text-right' : 'ml-3'}">
                                    <div class="${isCurrentUser ? 'bg-blue-600 text-white chat-bubble-right' : 'bg-gray-100 text-gray-900 chat-bubble-left'} px-4 py-2 max-w-xs lg:max-w-md">
                                        <p>${msg.message}</p>
                                    </div>
                                    <span class="text-xs text-gray-500 mt-1 block">
                                        ${formatDateTime(msg.created_at)}
                                        ${isCurrentUser && msg.read_at ? ' ✓✓' : (isCurrentUser ? ' ✓' : '')}
                                    </span>
                                </div>
                                
                                ${isCurrentUser ? `
                                    <img src="${msg.sender_profile_picture || 'https://via.placeholder.com/32'}" 
                                         alt="You" 
                                         class="h-8 w-8 rounded-full mt-1">
                                ` : ''}
                            </div>
                        `;
                    }).join('');
                    
                    // Scroll to bottom
                    messagesArea.scrollTop = messagesArea.scrollHeight;
                } else {
                    messagesArea.innerHTML = `
                        <div class="h-full flex items-center justify-center text-center">
                            <div>
                                <i data-lucide="message-square" class="h-12 w-12 text-gray-300 mx-auto mb-4"></i>
                                <p class="text-gray-500">No messages yet</p>
                                <p class="text-sm text-gray-400">Start the conversation!</p>
                            </div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading messages:', error);
            }
        }
        
        async function sendMessage() {
            const message = messageInput.value.trim();
            if (!message || !currentConversationId) return;
            
            try {
                const token = localStorage.getItem('jwt_token');
                const response = await fetch('api/send_message.php', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        conversation_id: currentConversationId,
                        message: message
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Clear input
                    messageInput.value = '';
                    
                    // Reload messages
                    await loadMessages(currentConversationId);
                    
                    // Reload conversations to update last message
                    await loadConversations();
                } else {
                    alert(data.message || 'Failed to send message');
                }
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Error sending message');
            }
        }
        
        function searchUsers(searchTerm) {
            loadUsers(searchTerm);
        }
        
        function formatTime(timestamp) {
            if (!timestamp) return '';
            const date = new Date(timestamp);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);
            
            if (diffMins < 60) return `${diffMins}m ago`;
            if (diffHours < 24) return `${diffHours}h ago`;
            if (diffDays < 7) return `${diffDays}d ago`;
            return date.toLocaleDateString();
        }
        
        function formatDateTime(timestamp) {
            if (!timestamp) return '';
            const date = new Date(timestamp);
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        
        // Poll for new messages
        setInterval(() => {
            if (currentConversationId) {
                loadMessages(currentConversationId);
                loadConversations();
            }
        }, 10000); // Check every 10 seconds
        
        // Initial load
        loadConversations();
    </script>
</body>
</html>