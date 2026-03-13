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
    <meta name="theme-color" content="#0f172a">
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="icon" type="image/png" sizes="192x192" href="assets/icons/app-icon-192.png">
    <link rel="apple-touch-icon" href="assets/icons/app-icon-192.png">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Roboto+Slab:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/variety-ui.css">
    <script src="assets/js/variety-ui.js" defer></script>
    <script src="assets/js/pwa.js" defer></script>
    
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
        .msg-actions {
            opacity: 0;
            transition: opacity 0.15s ease-in-out;
        }
        .msg-row:hover .msg-actions {
            opacity: 1;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Include Header -->
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="md:pl-64 pb-20 md:pb-0">
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
                                <button id="openChatProfileBtn" class="p-2 hover:bg-gray-100 rounded-lg" title="View profile">
                                    <i data-lucide="user" class="h-5 w-5 text-gray-600"></i>
                                </button>
                                <button id="audioCallBtn" class="p-2 hover:bg-gray-100 rounded-lg" title="Audio call">
                                    <i data-lucide="phone" class="h-5 w-5 text-gray-600"></i>
                                </button>
                                <button id="videoCallBtn" class="p-2 hover:bg-gray-100 rounded-lg" title="Video call">
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
                        <div id="pendingAttachmentPreview" class="hidden mb-2 text-xs text-gray-600 bg-gray-100 rounded-lg px-3 py-2"></div>
                        <div class="flex items-center">
                            <button id="attachFileBtn" class="p-2 hover:bg-gray-100 rounded-lg mr-2" type="button" title="Attach file">
                                <i data-lucide="paperclip" class="h-5 w-5 text-gray-600"></i>
                            </button>
                            <input type="file" id="messageAttachmentInput" class="hidden">
                            <div class="flex-1 relative">
                                <input type="text" 
                                       id="messageInput" 
                                       placeholder="Type your message..." 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       disabled>
                                <button id="emojiBtn" class="absolute right-3 top-2.5" type="button" title="Add emoji">
                                    <i data-lucide="smile" class="h-5 w-5 text-gray-400"></i>
                                </button>
                                <div id="emojiPicker" class="hidden absolute bottom-14 right-0 bg-white border border-gray-200 rounded-lg shadow-md p-2 z-20"></div>
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
        let currentChatUserId = null;
        let conversationsSignature = '';
        let messagesSignature = '';
        let loadingConversations = false;
        let loadingMessages = false;
        let pendingAttachment = null;
        
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
        const attachFileBtn = document.getElementById('attachFileBtn');
        const messageAttachmentInput = document.getElementById('messageAttachmentInput');
        const pendingAttachmentPreview = document.getElementById('pendingAttachmentPreview');
        const emojiBtn = document.getElementById('emojiBtn');
        const emojiPicker = document.getElementById('emojiPicker');
        const openChatProfileBtn = document.getElementById('openChatProfileBtn');
        const audioCallBtn = document.getElementById('audioCallBtn');
        const videoCallBtn = document.getElementById('videoCallBtn');
        
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
        if (attachFileBtn) {
            attachFileBtn.addEventListener('click', () => messageAttachmentInput.click());
        }
        if (messageAttachmentInput) {
            messageAttachmentInput.addEventListener('change', handleMessageAttachmentSelection);
        }
        if (emojiBtn) {
            emojiBtn.addEventListener('click', toggleEmojiPicker);
        }
        document.addEventListener('click', (event) => {
            if (emojiPicker && !emojiPicker.classList.contains('hidden')) {
                if (!emojiPicker.contains(event.target) && event.target !== emojiBtn && !emojiBtn.contains(event.target)) {
                    emojiPicker.classList.add('hidden');
                }
            }
        });
        if (openChatProfileBtn) {
            openChatProfileBtn.addEventListener('click', () => {
                if (currentChatUserId) {
                    window.location.href = `profile.php?id=${currentChatUserId}`;
                }
            });
        }
        if (audioCallBtn) {
            audioCallBtn.addEventListener('click', () => startCall('audio'));
        }
        if (videoCallBtn) {
            videoCallBtn.addEventListener('click', () => startCall('video'));
        }
        document.addEventListener('click', (event) => {
            if (!event.target.closest('[id^="msgMenu-"]') && !event.target.closest('.msg-menu-toggle')) {
                closeAllMessageMenus();
            }
        });
        
        // Functions
        function getConversationSignature(items) {
            return (items || []).map((conv) => {
                return [
                    conv.conversation_id,
                    conv.last_message_at || '',
                    conv.unread_count || 0,
                    conv.last_message || ''
                ].join('|');
            }).join('||');
        }

        function getMessagesSignature(items) {
            return (items || []).map((msg) => {
                return [
                    msg.message_id || '',
                    msg.sender_id || '',
                    msg.created_at || msg.timestamp || '',
                    msg.read_at || '',
                    msg.message || '',
                    msg.edited_at || '',
                    msg.deleted_at || '',
                    msg.attachment_url || '',
                    msg.attachment_name || '',
                    msg.attachment_type || ''
                ].join('|');
            }).join('||');
        }

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function renderReadTick(msg) {
            if (!msg || !msg.sender_id) return '';
            if (msg.read_at) {
                return '<span class="ml-1 text-blue-600 font-semibold" title="Read">✓✓</span>';
            }
            return '<span class="ml-1 text-emerald-600 font-semibold" title="Sent">✓</span>';
        }

        async function loadConversations(forceRender = false) {
            if (loadingConversations) return;
            loadingConversations = true;
            try {
                const token = localStorage.getItem('jwt_token');
                const response = await fetch(`${window.getApiBase ? window.getApiBase() : (window.PORTAL_BASE_PREFIX || '') + 'api'}/get_conversations.php`, {
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                });

                const data = await response.json();
                const conversationsList = document.getElementById('conversationsList');
                const list = (data.success && Array.isArray(data.data)) ? data.data : [];
                const nextSignature = getConversationSignature(list);
                if (!forceRender && nextSignature === conversationsSignature) {
                    return;
                }
                conversationsSignature = nextSignature;

                if (list.length > 0) {
                    conversationsList.innerHTML = list.map(conv => `
                        <div class="conversation-item p-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer ${conv.unread_count > 0 ? 'message-unread' : ''} ${String(currentConversationId) === String(conv.conversation_id) ? 'message-active' : ''}" 
                             data-conversation-id="${conv.conversation_id}" 
                             data-user-id="${conv.other_user_id}"
                             onclick="selectConversation('${conv.conversation_id}', '${conv.other_user_id}')">
                            <div class="flex items-center">
                                <img src="${conv.profile_picture_url || 'https://via.placeholder.com/40'}" 
                                     alt="${conv.full_name}" 
                                     class="h-10 w-10 rounded-full cursor-pointer"
                                     onclick="event.stopPropagation(); openProfileFromMessages('${conv.other_user_id}')">
                                <div class="ml-3 flex-1">
                                    <div class="flex justify-between">
                                        <h3 class="font-semibold text-gray-900 cursor-pointer hover:underline" onclick="event.stopPropagation(); openProfileFromMessages('${conv.other_user_id}')">${conv.full_name}</h3>
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
            } finally {
                loadingConversations = false;
            }
        }
        
        async function loadUsers(searchTerm = '') {
            try {
                const token = localStorage.getItem('jwt_token');
                const response = await fetch(`${window.getApiBase ? window.getApiBase() : (window.PORTAL_BASE_PREFIX || '') + 'api'}/search_users.php?q=${encodeURIComponent(searchTerm)}`, {
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
                                 class="h-10 w-10 rounded-full cursor-pointer"
                                 onclick="event.stopPropagation(); openProfileFromMessages('${user.user_id}')">
                            <div class="ml-3">
                                <h4 class="font-medium text-gray-900 cursor-pointer hover:underline" onclick="event.stopPropagation(); openProfileFromMessages('${user.user_id}')">${user.full_name}</h4>
                                <p class="text-sm text-gray-600">${user.role} - ${user.branch || ''}</p>
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
                const response = await fetch(`${window.getApiBase ? window.getApiBase() : (window.PORTAL_BASE_PREFIX || '') + 'api'}/create_conversation.php`, {
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
                    await loadConversations(true);
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
            currentChatUserId = userId;
            
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
            await loadConversations(true);
        }
        
        async function loadConversationDetails(userId) {
            try {
                const token = localStorage.getItem('jwt_token');
                const response = await fetch(`${window.getApiBase ? window.getApiBase() : (window.PORTAL_BASE_PREFIX || '') + 'api'}/get_user.php?id=${userId}`, {
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const user = data.data;
                    document.getElementById('chatUserImage').src = user.profile_picture_url || 'https://via.placeholder.com/40';
                    document.getElementById('chatUserName').textContent = user.full_name;
                    document.getElementById('chatUserStatus').textContent = `${user.role} - ${user.branch || 'RJIT Alumni'}`;
                }
            } catch (error) {
                console.error('Error loading conversation details:', error);
            }
        }
        
        async function loadMessages(conversationId) {
            if (loadingMessages) return;
            loadingMessages = true;
            try {
                const token = localStorage.getItem('jwt_token');
                const response = await fetch(`${window.getApiBase ? window.getApiBase() : (window.PORTAL_BASE_PREFIX || '') + 'api'}/get_messages.php?conversation_id=${conversationId}`, {
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                });

                const payload = await response.json();
                const messages = Array.isArray(payload) ? payload : (payload && Array.isArray(payload.data) ? payload.data : []);
                const nextSignature = getMessagesSignature(messages);
                if (nextSignature === messagesSignature) {
                    return;
                }
                messagesSignature = nextSignature;

                if (messages.length > 0) {
                    const userData = JSON.parse(localStorage.getItem('user_data'));
                    const currentUserId = userData.user_id;
                    const wasNearBottom = (messagesArea.scrollHeight - messagesArea.scrollTop - messagesArea.clientHeight) < 80;

                    messagesArea.innerHTML = messages.map(msg => {
                        const isCurrentUser = msg.sender_id == currentUserId;
                        const bodyHtml = msg.is_deleted
                            ? '<p class="italic text-sm opacity-80">This message was deleted</p>'
                            : `
                                ${msg.message ? `<p class="whitespace-pre-wrap break-words">${escapeHtml(msg.message)}</p>` : ''}
                                ${renderAttachmentHtml(msg)}
                            `;
                        const editedTag = (isCurrentUser && msg.is_edited) ? '<span class="ml-1 text-[11px] text-gray-400">(edited)</span>' : '';
                        const actionsMenu = isCurrentUser && !msg.is_deleted ? `
                            <div class="relative msg-actions">
                                <button class="msg-menu-toggle p-1 rounded hover:bg-gray-200 text-gray-500" onclick="toggleMessageMenu(${msg.message_id})">
                                    <i data-lucide="more-vertical" class="h-4 w-4"></i>
                                </button>
                                <div id="msgMenu-${msg.message_id}" class="hidden absolute right-0 mt-1 w-32 bg-white border border-gray-200 rounded-md shadow-lg z-20">
                                    ${msg.can_edit ? `<button class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50" onclick="editMessagePrompt(${msg.message_id}, '${encodeURIComponent(msg.message || '')}')">Edit</button>` : ''}
                                    <button class="w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50" onclick="deleteMessage(${msg.message_id})">Delete</button>
                                </div>
                            </div>
                        ` : '';
                        return `
                            <div class="msg-row flex mb-4 ${isCurrentUser ? 'justify-end' : ''}">
                                ${!isCurrentUser ? `
                                    <img src="${msg.sender_profile_picture || 'https://via.placeholder.com/32'}" 
                                         alt="User" 
                                         class="h-8 w-8 rounded-full mt-1">
                                ` : ''}
                                
                                <div class="${isCurrentUser ? 'mr-3 text-right' : 'ml-3'}">
                                    <div class="${isCurrentUser ? 'bg-blue-600 text-white chat-bubble-right' : 'bg-gray-100 text-gray-900 chat-bubble-left'} px-4 py-2 max-w-xs lg:max-w-md">
                                        ${bodyHtml}
                                    </div>
                                    <span class="text-xs text-gray-500 mt-1 inline-flex items-center ${isCurrentUser ? 'justify-end w-full' : ''}">
                                        ${formatDateTime(msg.created_at || msg.timestamp)}
                                        ${editedTag}
                                        ${isCurrentUser ? renderReadTick(msg) : ''}
                                    </span>
                                </div>

                                ${isCurrentUser ? actionsMenu : ''}
                                
                                ${isCurrentUser ? `
                                    <img src="${msg.sender_profile_picture || 'https://via.placeholder.com/32'}" 
                                         alt="You" 
                                         class="h-8 w-8 rounded-full mt-1">
                                ` : ''}
                            </div>
                        `;
                    }).join('');
                    lucide.createIcons();

                    if (wasNearBottom) {
                        messagesArea.scrollTop = messagesArea.scrollHeight;
                    }
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
            } finally {
                loadingMessages = false;
            }
        }
        async function sendMessage() {
            const message = messageInput.value.trim();
            if (!currentConversationId) return;
            if (!message && !pendingAttachment) return;
            
            try {
                let uploadedAttachment = null;
                if (pendingAttachment) {
                    uploadedAttachment = await uploadMessageAttachment(pendingAttachment);
                }
                const token = localStorage.getItem('jwt_token');
                const response = await fetch(`${window.getApiBase ? window.getApiBase() : (window.PORTAL_BASE_PREFIX || '') + 'api'}/send_message.php`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        conversation_id: currentConversationId,
                        message: message,
                        attachment_url: uploadedAttachment ? uploadedAttachment.url : '',
                        attachment_type: uploadedAttachment ? uploadedAttachment.type : '',
                        attachment_name: uploadedAttachment ? uploadedAttachment.name : ''
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Clear input
                    messageInput.value = '';
                    clearPendingAttachment();
                    
                    // Reload messages
                    await loadMessages(currentConversationId);
                    
                    // Reload conversations to update last message
                    await loadConversations(true);
                } else {
                    alert(data.message || 'Failed to send message');
                }
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Error sending message');
            }
        }

        function renderAttachmentHtml(msg) {
            if (!msg || !msg.attachment_url) return '';
            const url = escapeHtml(msg.attachment_url);
            const name = escapeHtml(msg.attachment_name || 'Attachment');
            const lowerType = String(msg.attachment_type || '').toLowerCase();
            const lowerName = String(msg.attachment_name || '').toLowerCase();
            const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(lowerType) || /\.(jpg|jpeg|png|gif|webp)$/.test(lowerName);
            if (isImage) {
                return `<a href="${url}" target="_blank" rel="noopener" class="block mt-2"><img src="${url}" alt="${name}" class="max-h-52 rounded-lg border border-white/20"></a>`;
            }
            return `<a href="${url}" target="_blank" rel="noopener" class="inline-flex items-center mt-2 underline break-all">${name}</a>`;
        }

        function clearPendingAttachment() {
            pendingAttachment = null;
            if (messageAttachmentInput) {
                messageAttachmentInput.value = '';
            }
            if (pendingAttachmentPreview) {
                pendingAttachmentPreview.innerHTML = '';
                pendingAttachmentPreview.classList.add('hidden');
            }
        }

        function handleMessageAttachmentSelection(event) {
            const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
            if (!file) return;
            pendingAttachment = file;
            if (pendingAttachmentPreview) {
                pendingAttachmentPreview.innerHTML = `
                    <span class="font-medium">Attachment:</span> ${escapeHtml(file.name)}
                    <button type="button" class="ml-2 underline" onclick="clearPendingAttachment()">Remove</button>
                `;
                pendingAttachmentPreview.classList.remove('hidden');
            }
        }

        async function uploadMessageAttachment(file) {
            const token = localStorage.getItem('jwt_token');
            const formData = new FormData();
            formData.append('attachment', file);
            formData.append('context', 'messages');
            const response = await fetch(`${window.getApiBase ? window.getApiBase() : (window.PORTAL_BASE_PREFIX || '') + 'api'}/upload_file.php`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`
                },
                body: formData
            });
            const data = await response.json();
            if (!response.ok || !data || !data.url) {
                throw new Error(data && data.message ? data.message : 'Attachment upload failed');
            }
            return {
                url: data.url,
                type: data.type || '',
                name: file.name || ''
            };
        }

        function toggleEmojiPicker() {
            if (!emojiPicker) return;
            if (!emojiPicker.dataset.ready) {
                const emojis = ['😀', '😁', '😂', '😊', '😍', '😎', '👍', '🔥', '🎉', '🙏', '💯', '🚀', '❤️', '🙂', '🤝'];
                emojiPicker.innerHTML = emojis.map((emoji) => `
                    <button type="button" class="p-1 text-lg hover:bg-gray-100 rounded" onclick="insertEmoji('${emoji}')">${emoji}</button>
                `).join('');
                emojiPicker.dataset.ready = '1';
            }
            emojiPicker.classList.toggle('hidden');
        }

        function insertEmoji(emoji) {
            const cursorPos = messageInput.selectionStart || messageInput.value.length;
            const text = messageInput.value;
            messageInput.value = `${text.slice(0, cursorPos)}${emoji}${text.slice(cursorPos)}`;
            messageInput.focus();
            const nextPos = cursorPos + emoji.length;
            messageInput.setSelectionRange(nextPos, nextPos);
        }

        function closeAllMessageMenus() {
            document.querySelectorAll('[id^="msgMenu-"]').forEach((el) => el.classList.add('hidden'));
        }

        function toggleMessageMenu(messageId) {
            const menu = document.getElementById(`msgMenu-${messageId}`);
            if (!menu) return;
            const willShow = menu.classList.contains('hidden');
            closeAllMessageMenus();
            if (willShow) {
                menu.classList.remove('hidden');
            }
        }

        async function editMessagePrompt(messageId, encodedText) {
            closeAllMessageMenus();
            const currentText = decodeURIComponent(String(encodedText || ''));
            const nextText = prompt('Edit message (allowed for 30 minutes):', currentText || '');
            if (nextText === null) return;
            const trimmed = String(nextText).trim();
            if (!trimmed) {
                alert('Message cannot be empty.');
                return;
            }
            try {
                const token = localStorage.getItem('jwt_token');
                const response = await fetch(`${window.getApiBase ? window.getApiBase() : (window.PORTAL_BASE_PREFIX || '') + 'api'}/edit_message.php`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message_id: messageId,
                        message: trimmed
                    })
                });
                const data = await response.json();
                if (!data.success) {
                    alert(data.message || 'Failed to edit message');
                    return;
                }
                await loadMessages(currentConversationId);
                await loadConversations(true);
            } catch (error) {
                console.error('Error editing message:', error);
                alert('Error editing message');
            }
        }

        async function deleteMessage(messageId) {
            closeAllMessageMenus();
            if (!confirm('Delete this message?')) return;
            try {
                const token = localStorage.getItem('jwt_token');
                const response = await fetch(`${window.getApiBase ? window.getApiBase() : (window.PORTAL_BASE_PREFIX || '') + 'api'}/delete_message.php`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message_id: messageId
                    })
                });
                const data = await response.json();
                if (!data.success) {
                    alert(data.message || 'Failed to delete message');
                    return;
                }
                await loadMessages(currentConversationId);
                await loadConversations(true);
            } catch (error) {
                console.error('Error deleting message:', error);
                alert('Error deleting message');
            }
        }

        async function startCall(callType) {
            if (!currentChatUserId) return;
            try {
                const token = localStorage.getItem('jwt_token');
                const response = await fetch(`${window.getApiBase ? window.getApiBase() : (window.PORTAL_BASE_PREFIX || '') + 'api'}/start_call.php`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        receiver_id: Number(currentChatUserId),
                        call_type: callType
                    })
                });
                const data = await response.json();
                if (!data.success || !data.data || !data.data.room_url) {
                    alert(data.message || 'Unable to start call');
                    return;
                }
                window.open(data.data.room_url, '_blank', 'noopener');
            } catch (error) {
                console.error('Error starting call:', error);
                alert('Error starting call');
            }
        }
        
        function searchUsers(searchTerm) {
            loadUsers(searchTerm);
        }

        function openProfileFromMessages(userId) {
            const uid = parseInt(userId || 0, 10);
            if (!uid) return;
            window.location.href = `profile.php?id=${uid}`;
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
            }
            loadConversations();
        }, 3000); // Light polling with no-flicker render guards
        
        // Initial load
        loadConversations();
        (async function bootstrapFromQuery() {
            const params = new URLSearchParams(window.location.search);
            const uid = parseInt(params.get('user_id') || '0', 10);
            if (uid > 0) {
                await startNewConversation(uid);
            }
        })();
    </script>
</body>
</html>






