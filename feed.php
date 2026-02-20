<?php
// Check authentication

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed - RJIT Alumni Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Roboto+Slab:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/variety-ui.css">
    <script src="includes/auth-check.js"></script>
    <script src="assets/js/variety-ui.js" defer></script>
    
    <style>
        .post-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .post-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .pinned-post {
            border-left: 4px solid #f59e0b;
            background-color: #fffbeb;
        }
        
        .comment-box {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .comment-box.open {
            max-height: 500px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="md:pl-64">
        <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Community Feed</h1>
                <p class="text-gray-600 mt-1">Stay updated with posts from alumni, faculty, and students</p>
            </div>

            <!-- Create Post Section -->
            <div id="createPostSection" class="bg-white rounded-xl shadow-sm p-6 mb-8 hidden">
                <div class="flex items-start mb-4">
                    <div class="flex-shrink-0">
                        <img id="userFeedAvatar" 
                             src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='48' height='48' viewBox='0 0 48 48'%3E%3Crect width='48' height='48' fill='%23dbeafe'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='14' fill='%233b82f6'%3EU%3C/text%3E%3C/svg%3E" 
                             alt="Profile" 
                             class="h-12 w-12 rounded-full">
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="font-medium text-gray-900" id="userFeedName">Share your thoughts</h3>
                        <form id="createPostForm" class="mt-4">
                            <textarea id="postContent" 
                                      rows="4"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                                      placeholder="What's on your mind?"></textarea>
                            
                            <!-- File Uploads -->
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Add Images (Optional)</label>
                                    <div class="flex items-center space-x-4">
                                        <label for="postImages" class="cursor-pointer">
                                            <div class="h-20 w-20 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center hover:border-blue-400 hover:bg-blue-50">
                                                <i data-lucide="image" class="h-6 w-6 text-gray-400"></i>
                                            </div>
                                            <input type="file" id="postImages" name="images[]" accept="image/*" multiple class="hidden">
                                        </label>
                                        <div id="imagePreviews" class="flex space-x-2"></div>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Add Files (Optional)</label>
                                    <div class="flex items-center space-x-4">
                                        <label for="postFiles" class="cursor-pointer">
                                            <div class="h-20 w-20 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center hover:border-blue-400 hover:bg-blue-50">
                                                <i data-lucide="paperclip" class="h-6 w-6 text-gray-400"></i>
                                            </div>
                                            <input type="file" id="postFiles" name="files[]" multiple class="hidden">
                                        </label>
                                        <div id="filePreviews" class="flex space-x-2"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Post Options -->
                            <div class="mt-6 flex items-center justify-between">
                                <div class="flex items-center space-x-6">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="allowComments" name="allow_comments" checked class="h-4 w-4 text-blue-600 rounded">
                                        <label for="allowComments" class="ml-2 text-sm text-gray-700">Allow comments</label>
                                    </div>
                                    
                                    <div class="flex items-center">
                                        <input type="checkbox" id="pinPost" name="pin_post" class="h-4 w-4 text-amber-600 rounded">
                                        <label for="pinPost" class="ml-2 text-sm text-gray-700">Pin to profile</label>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-4">
                                    <button type="button" onclick="clearPostForm()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
                                        Clear
                                    </button>
                                    <button type="submit" 
                                            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-medium">
                                        Post
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Feed Filters -->
            <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
                    <div class="flex items-center space-x-4">
                        <button id="allPosts" class="px-4 py-2 rounded-lg bg-blue-100 text-blue-700 font-medium">
                            All Posts
                        </button>
                        <button id="announcements" class="px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 font-medium">
                            Announcements
                        </button>
                        <button id="following" class="px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 font-medium">
                            Following
                        </button>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <select id="sortBy" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            <option value="newest">Newest First</option>
                            <option value="popular">Most Popular</option>
                            <option value="oldest">Oldest First</option>
                        </select>
                        
                        <div class="relative">
                            <input type="text" 
                                   id="searchPosts" 
                                   placeholder="Search posts..."
                                   class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full sm:w-64">
                            <i data-lucide="search" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pinned Posts Section -->
            <div id="pinnedPostsSection" class="mb-8 hidden">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <i data-lucide="pin" class="h-5 w-5 text-amber-500 mr-2"></i>
                    Pinned Posts
                </h2>
                <div id="pinnedPosts" class="space-y-4">
                    <!-- Pinned posts will be loaded here -->
                </div>
            </div>

            <!-- Main Feed -->
            <div id="feedContainer">
                <div class="space-y-6">
                    <!-- Posts will be loaded here -->
                    <div class="text-center py-12">
                        <i data-lucide="loader" class="h-8 w-8 animate-spin text-blue-600 mx-auto mb-4"></i>
                        <p class="text-gray-500">Loading posts...</p>
                    </div>
                </div>
                
                <!-- Load More Button -->
                <div id="loadMoreContainer" class="mt-8 text-center hidden">
                    <button id="loadMoreBtn" 
                            class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
                        Load More Posts
                    </button>
                </div>
            </div>
        </main>
    </div>

    <!-- Comment Modal Template -->
    <template id="commentTemplate">
        <div class="comment-box bg-gray-50 border-t border-gray-200 mt-4">
            <div class="p-4">
                <!-- Comment Input -->
                <div class="flex items-start mb-6">
                    <div class="flex-shrink-0">
                        <img class="h-8 w-8 rounded-full" src="" alt="">
                    </div>
                    <div class="ml-3 flex-1">
                        <textarea class="comment-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none" 
                                  rows="2" 
                                  placeholder="Write a comment..."></textarea>
                        <div class="mt-2 flex justify-end">
                            <button type="button" class="post-comment-btn bg-blue-600 text-white px-4 py-1.5 rounded-lg text-sm hover:bg-blue-700">
                                Post Comment
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Comments List -->
                <div class="comments-list space-y-4"></div>
            </div>
        </div>
    </template>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Global variables
        let currentPage = 1;
        let isLoading = false;
        let hasMorePosts = true;
        let currentFilter = 'all';
        let currentSort = 'newest';
        
        // Load feed data
        document.addEventListener('DOMContentLoaded', async function() {
            await loadUserProfile();
            await loadFeed();
            setupEventListeners();
        });
        
        async function loadUserProfile() {
            try {
                const userData = localStorage.getItem('user_data');
                if (userData) {
                    const user = JSON.parse(userData);
                    
                    // Update user info in create post section
                    document.getElementById('userFeedName').textContent = user.name || 'Share your thoughts';
                    
                    if (user.profile_pic) {
                        document.getElementById('userFeedAvatar').src = user.profile_pic;
                    }
                    
                    // Show create post section if user can post
                    if (user.can_post) {
                        document.getElementById('createPostSection').classList.remove('hidden');
                    }
                }
            } catch (error) {
                console.error('Error loading user profile:', error);
            }
        }
        
        async function loadFeed() {
            if (isLoading) return;
            
            isLoading = true;
            const feedContainer = document.getElementById('feedContainer');
            const loadMoreContainer = document.getElementById('loadMoreContainer');
            
            try {
                const response = await makeApiCall(`get_feed.php?page=${currentPage}&filter=${currentFilter}&sort=${currentSort}`);
                
                if (response && (response.success || response.status === 'success')) {
                    const posts = response.data || [];
                    const totalPosts = response.total || 0;
                    
                    if (currentPage === 1) {
                        feedContainer.innerHTML = '';
                        
                        // Check for pinned posts
                        const pinnedPosts = posts.filter(post => post.is_pinned);
                        if (pinnedPosts.length > 0) {
                            await loadPinnedPosts(pinnedPosts);
                        }
                        
                        // Filter out pinned posts from regular feed
                        const regularPosts = posts.filter(post => !post.is_pinned);
                        
                        if (regularPosts.length === 0 && pinnedPosts.length === 0) {
                            showNoPostsMessage();
                        } else if (regularPosts.length > 0) {
                            await renderPosts(regularPosts);
                        }
                    } else {
                        await renderPosts(posts);
                    }
                    
                    // Check if there are more posts to load
                    const loadedPosts = document.querySelectorAll('.post-card').length;
                    hasMorePosts = loadedPosts < totalPosts;
                    
                    if (hasMorePosts) {
                        loadMoreContainer.classList.remove('hidden');
                    } else {
                        loadMoreContainer.classList.add('hidden');
                    }
                } else {
                    showNoPostsMessage();
                }
            } catch (error) {
                console.error('Error loading feed:', error);
                showErrorMessage();
            } finally {
                isLoading = false;
            }
        }
        
        async function loadPinnedPosts(pinnedPosts) {
            const pinnedSection = document.getElementById('pinnedPostsSection');
            const pinnedContainer = document.getElementById('pinnedPosts');
            
            if (pinnedPosts.length > 0) {
                pinnedSection.classList.remove('hidden');
                pinnedContainer.innerHTML = '';
                
                for (const post of pinnedPosts) {
                    const postElement = await createPostElement(post, true);
                    pinnedContainer.appendChild(postElement);
                }
            } else {
                pinnedSection.classList.add('hidden');
            }
        }
        
        async function renderPosts(posts) {
            const feedContainer = document.getElementById('feedContainer').querySelector('.space-y-6') || 
                                document.getElementById('feedContainer');
            
            if (currentPage === 1 && !feedContainer.classList.contains('space-y-6')) {
                feedContainer.innerHTML = '<div class="space-y-6"></div>';
            }
            
            const postsContainer = feedContainer.classList.contains('space-y-6') ? feedContainer : feedContainer.querySelector('.space-y-6');
            
            for (const post of posts) {
                const postElement = await createPostElement(post);
                postsContainer.appendChild(postElement);
            }
            
            // Re-initialize icons
            lucide.createIcons();
        }
        
        async function createPostElement(post, isPinned = false) {
            const postElement = document.createElement('div');
            postElement.className = `post-card bg-white rounded-xl shadow-sm overflow-hidden ${isPinned ? 'pinned-post' : ''}`;
            postElement.dataset.postId = post.id;
            
            // Fetch content from file
            let content = '';
            if (post.content_file_path) {
                try {
                    content = await fetchTextContent(post.content_file_path);
                } catch (error) {
                    console.error('Error fetching post content:', error);
                    content = 'Content not available';
                }
            }
            
            // Format date
            const postDate = formatDate(post.created_at);
            
            // Check if user has liked the post
            const hasLiked = post.user_has_liked || false;
            
            // Check if comments are allowed
            const commentsAllowed = post.allow_comments !== false;
            
            postElement.innerHTML = `
                <div class="p-6">
                    <!-- Post Header -->
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                ${post.author_avatar ? 
                                    `<img src="${post.author_avatar}" alt="${post.author_name}" class="h-10 w-10 rounded-full">` : 
                                    `<div class="h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i data-lucide="user" class="h-5 w-5 text-blue-600"></i>
                                    </div>`}
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="font-semibold text-gray-900">${post.author_name}</h3>
                                    ${post.author_role === 'admin' ? 
                                        '<span class="px-2 py-0.5 bg-amber-100 text-amber-800 text-xs rounded-full">ADMIN</span>' : 
                                        post.author_role === 'faculty' ? 
                                        '<span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-xs rounded-full">FACULTY</span>' : 
                                        post.author_role === 'alumni' ? 
                                        '<span class="px-2 py-0.5 bg-green-100 text-green-800 text-xs rounded-full">ALUMNI</span>' : ''}
                                </div>
                                <p class="text-sm text-gray-500">
                                    ${post.branch ? `${post.branch} • ` : ''}${postDate}
                                    ${isPinned ? '<span class="ml-2 text-amber-600 font-medium">📌 Pinned</span>' : ''}
                                </p>
                            </div>
                        </div>
                        
                        <!-- Post Actions Menu -->
                        <div class="relative">
                            <button class="post-menu-btn p-2 rounded-full hover:bg-gray-100">
                                <i data-lucide="more-vertical" class="h-5 w-5 text-gray-500"></i>
                            </button>
                            <div class="post-menu hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-10">
                                <div class="py-1">
                                    ${post.is_owner ? `
                                        <button class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 edit-post-btn">Edit Post</button>
                                        <button class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 delete-post-btn">Delete Post</button>
                                        <button class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 pin-post-btn">
                                            ${post.is_pinned ? 'Unpin Post' : 'Pin to Profile'}
                                        </button>
                                    ` : ''}
                                    <button class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 report-post-btn">Report Post</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Post Content -->
                    <div class="mb-4">
                        <p class="text-gray-700 whitespace-pre-line">${content}</p>
                    </div>
                    
                    <!-- Post Attachments -->
                    ${post.attachments && post.attachments.length > 0 ? `
                        <div class="mb-4">
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                ${post.attachments.map(attachment => `
                                    ${attachment.type === 'image' ? 
                                        `<img src="${attachment.url}" alt="Attachment" class="w-full h-32 object-cover rounded-lg cursor-pointer hover:opacity-90" onclick="viewImage('${attachment.url}')">` : 
                                        `<a href="${attachment.url}" target="_blank" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                                            <i data-lucide="file" class="h-5 w-5 text-gray-400 mr-3"></i>
                                            <span class="text-sm text-gray-700 truncate">${attachment.name}</span>
                                        </a>`}
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                    
                    <!-- Post Stats -->
                    <div class="flex items-center justify-between text-sm text-gray-500 pt-4 border-t border-gray-100">
                        <div class="flex items-center space-x-4">
                            <span class="flex items-center">
                                <i data-lucide="heart" class="h-4 w-4 mr-1"></i>
                                <span class="like-count">${post.likes_count || 0}</span>
                            </span>
                            <span class="flex items-center">
                                <i data-lucide="message-square" class="h-4 w-4 mr-1"></i>
                                <span class="comment-count">${post.comments_count || 0}</span>
                            </span>
                            <span class="flex items-center">
                                <i data-lucide="share-2" class="h-4 w-4 mr-1"></i>
                                ${post.shares_count || 0}
                            </span>
                        </div>
                        
                        <span>${post.view_count || 0} views</span>
                    </div>
                    
                    <!-- Post Actions -->
                    <div class="mt-4 flex border-t border-gray-100 pt-4">
                        <button class="like-btn flex-1 flex items-center justify-center py-2 rounded-lg hover:bg-gray-50 ${hasLiked ? 'text-red-600' : 'text-gray-600'}">
                            <i data-lucide="heart" class="h-5 w-5 mr-2 ${hasLiked ? 'fill-current' : ''}"></i>
                            ${hasLiked ? 'Liked' : 'Like'}
                        </button>
                        
                        <button class="comment-toggle-btn flex-1 flex items-center justify-center py-2 rounded-lg hover:bg-gray-50 text-gray-600 ${!commentsAllowed ? 'opacity-50 cursor-not-allowed' : ''}" 
                                ${!commentsAllowed ? 'disabled' : ''}>
                            <i data-lucide="message-square" class="h-5 w-5 mr-2"></i>
                            Comment
                        </button>
                        
                        <button class="share-btn flex-1 flex items-center justify-center py-2 rounded-lg hover:bg-gray-50 text-gray-600">
                            <i data-lucide="share-2" class="h-5 w-5 mr-2"></i>
                            Share
                        </button>
                    </div>
                </div>
            `;
            
            // Add event listeners
            setTimeout(() => {
                setupPostEventListeners(postElement, post);
            }, 100);
            
            return postElement;
        }
        
        function setupPostEventListeners(postElement, post) {
            // Like button
            const likeBtn = postElement.querySelector('.like-btn');
            likeBtn.addEventListener('click', async () => {
                await handleLike(post.id, likeBtn, postElement.querySelector('.like-count'));
            });
            
            // Comment toggle button
            const commentToggleBtn = postElement.querySelector('.comment-toggle-btn');
            if (post.allow_comments !== false) {
                commentToggleBtn.addEventListener('click', () => {
                    toggleComments(postElement, post.id);
                });
            }
            
            // Post menu
            const menuBtn = postElement.querySelector('.post-menu-btn');
            const menu = postElement.querySelector('.post-menu');
            
            menuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                menu.classList.toggle('hidden');
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', () => {
                menu.classList.add('hidden');
            });
            
            // Pin/unpin post
            const pinBtn = postElement.querySelector('.pin-post-btn');
            if (pinBtn) {
                pinBtn.addEventListener('click', async () => {
                    await togglePinPost(post.id, post.is_pinned);
                });
            }
        }
        
        async function handleLike(postId, likeBtn, likeCountElement) {
            try {
                const response = await makeApiCall('react_to_post.php', 'POST', {
                    post_id: postId,
                    reaction: 'like'
                });
                
                if (response && (response.success || response.status === 'success')) {
                    const currentLikes = parseInt(likeCountElement.textContent);
                    const hasLiked = likeBtn.classList.contains('text-red-600');
                    
                    if (hasLiked) {
                        likeBtn.classList.remove('text-red-600');
                        likeBtn.querySelector('i').classList.remove('fill-current');
                        likeBtn.innerHTML = '<i data-lucide="heart" class="h-5 w-5 mr-2"></i>Like';
                        likeCountElement.textContent = currentLikes - 1;
                    } else {
                        likeBtn.classList.add('text-red-600');
                        likeBtn.querySelector('i').classList.add('fill-current');
                        likeBtn.innerHTML = '<i data-lucide="heart" class="h-5 w-5 mr-2 fill-current"></i>Liked';
                        likeCountElement.textContent = currentLikes + 1;
                    }
                    
                    lucide.createIcons();
                }
            } catch (error) {
                console.error('Error liking post:', error);
            }
        }
        
        async function toggleComments(postElement, postId) {
            const commentBox = postElement.querySelector('.comment-box');
            
            if (commentBox) {
                // Toggle existing comment box
                commentBox.classList.toggle('open');
            } else {
                // Create new comment box
                const template = document.getElementById('commentTemplate').content.cloneNode(true);
                const commentBox = template.querySelector('.comment-box');
                commentBox.classList.add('open');
                
                // Add comment box to post
                postElement.querySelector('.p-6').appendChild(commentBox);
                
                // Load comments
                await loadComments(postId, commentBox.querySelector('.comments-list'));
                
                // Setup comment submission
                const commentInput = commentBox.querySelector('.comment-input');
                const postCommentBtn = commentBox.querySelector('.post-comment-btn');
                
                postCommentBtn.addEventListener('click', async () => {
                    await postComment(postId, commentInput.value.trim(), commentBox);
                });
                
                commentInput.addEventListener('keypress', async (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        await postComment(postId, commentInput.value.trim(), commentBox);
                    }
                });
            }
        }
        
        async function loadComments(postId, commentsContainer) {
            try {
                const response = await makeApiCall(`get_comments.php?post_id=${postId}`);
                
                if (response && (response.success || response.status === 'success') && response.data) {
                    commentsContainer.innerHTML = '';
                    
                    for (const comment of response.data) {
                        const commentElement = document.createElement('div');
                        commentElement.className = 'flex items-start';
                        
                        commentElement.innerHTML = `
                            <div class="flex-shrink-0">
                                ${comment.author_avatar ? 
                                    `<img src="${comment.author_avatar}" alt="${comment.author_name}" class="h-8 w-8 rounded-full">` : 
                                    `<div class="h-8 w-8 bg-gray-100 rounded-full flex items-center justify-center">
                                        <i data-lucide="user" class="h-4 w-4 text-gray-400"></i>
                                    </div>`}
                            </div>
                            <div class="ml-3 flex-1">
                                <div class="bg-white rounded-lg p-3">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-medium text-gray-900 text-sm">${comment.author_name}</h4>
                                        <span class="text-xs text-gray-500">${formatDate(comment.created_at)}</span>
                                    </div>
                                    <p class="text-gray-700 text-sm mt-1">${comment.content}</p>
                                </div>
                            </div>
                        `;
                        
                        commentsContainer.appendChild(commentElement);
                    }
                    
                    lucide.createIcons();
                }
            } catch (error) {
                console.error('Error loading comments:', error);
            }
        }
        
        async function postComment(postId, content, commentBox) {
            if (!content.trim()) return;
            
            try {
                const response = await makeApiCall('create_comment.php', 'POST', {
                    post_id: postId,
                    content: content
                });
                
                if (response && (response.success || response.status === 'success')) {
                    // Clear input
                    commentBox.querySelector('.comment-input').value = '';
                    
                    // Reload comments
                    await loadComments(postId, commentBox.querySelector('.comments-list'));
                    
                    // Update comment count
                    const postElement = commentBox.closest('.post-card');
                    const commentCount = postElement.querySelector('.comment-count');
                    const currentCount = parseInt(commentCount.textContent);
                    commentCount.textContent = currentCount + 1;
                }
            } catch (error) {
                console.error('Error posting comment:', error);
            }
        }
        
        async function togglePinPost(postId, isCurrentlyPinned) {
            try {
                const endpoint = isCurrentlyPinned ? 'unpin_post.php' : 'pin_post.php';
                const response = await makeApiCall(endpoint, 'POST', {
                    post_id: postId
                });
                
                if (response && (response.success || response.status === 'success')) {
                    alert(`Post ${isCurrentlyPinned ? 'unpinned' : 'pinned'} successfully!`);
                    location.reload(); // Reload to update pinned posts
                }
            } catch (error) {
                console.error('Error toggling pin:', error);
            }
        }
        
        function setupEventListeners() {
            // Filter buttons
            document.getElementById('allPosts').addEventListener('click', () => changeFilter('all'));
            document.getElementById('announcements').addEventListener('click', () => changeFilter('announcements'));
            document.getElementById('following').addEventListener('click', () => changeFilter('following'));
            
            // Sort dropdown
            document.getElementById('sortBy').addEventListener('change', (e) => {
                currentSort = e.target.value;
                reloadFeed();
            });
            
            // Search input
            let searchTimeout;
            document.getElementById('searchPosts').addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    searchPosts(e.target.value);
                }, 500);
            });
            
            // Load more button
            document.getElementById('loadMoreBtn').addEventListener('click', () => {
                currentPage++;
                loadFeed();
            });
            
            // Create post form
            const createPostForm = document.getElementById('createPostForm');
            if (createPostForm) {
                createPostForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    await createPost();
                });
            }
            
            // Image upload preview
            document.getElementById('postImages').addEventListener('change', function() {
                previewImages(this, 'imagePreviews');
            });
            
            // File upload preview
            document.getElementById('postFiles').addEventListener('change', function() {
                previewFiles(this, 'filePreviews');
            });
        }
        
        function changeFilter(filter) {
            currentFilter = filter;
            currentPage = 1;
            
            // Update active button
            document.querySelectorAll('[id="allPosts"], [id="announcements"], [id="following"]').forEach(btn => {
                if (btn.id === filter) {
                    btn.className = 'px-4 py-2 rounded-lg bg-blue-100 text-blue-700 font-medium';
                } else {
                    btn.className = 'px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 font-medium';
                }
            });
            
            reloadFeed();
        }
        
        function reloadFeed() {
            currentPage = 1;
            document.getElementById('feedContainer').innerHTML = `
                <div class="space-y-6">
                    <div class="text-center py-12">
                        <i data-lucide="loader" class="h-8 w-8 animate-spin text-blue-600 mx-auto mb-4"></i>
                        <p class="text-gray-500">Loading posts...</p>
                    </div>
                </div>
            `;
            loadFeed();
        }
        
        async function searchPosts(query) {
            if (query.trim() === '') {
                reloadFeed();
                return;
            }
            
            try {
                const response = await makeApiCall(`search_posts.php?q=${encodeURIComponent(query)}`);
                const feedContainer = document.getElementById('feedContainer');
                
                if (response && (response.success || response.status === 'success') && response.data) {
                    feedContainer.innerHTML = '<div class="space-y-6"></div>';
                    const postsContainer = feedContainer.querySelector('.space-y-6');
                    
                    if (response.data.length === 0) {
                        postsContainer.innerHTML = `
                            <div class="text-center py-12">
                                <i data-lucide="search" class="h-12 w-12 text-gray-300 mx-auto mb-4"></i>
                                <p class="text-gray-500">No posts found matching "${query}"</p>
                            </div>
                        `;
                    } else {
                        await renderPosts(response.data);
                    }
                    
                    document.getElementById('loadMoreContainer').classList.add('hidden');
                }
            } catch (error) {
                console.error('Error searching posts:', error);
            }
        }
        
        async function createPost() {
            const content = document.getElementById('postContent').value.trim();
            const allowComments = document.getElementById('allowComments').checked;
            const pinPost = document.getElementById('pinPost').checked;
            
            if (!content) {
                alert('Please enter some content for your post');
                return;
            }
            
            const formData = new FormData();
            formData.append('content', content);
            formData.append('allow_comments', allowComments ? '1' : '0');
            
            if (pinPost) {
                formData.append('pin_post', '1');
            }
            
            // Add images
            const imageInput = document.getElementById('postImages');
            for (let i = 0; i < imageInput.files.length; i++) {
                formData.append('images[]', imageInput.files[i]);
            }
            
            // Add files
            const fileInput = document.getElementById('postFiles');
            for (let i = 0; i < fileInput.files.length; i++) {
                formData.append('files[]', fileInput.files[i]);
            }
            
            try {
                const token = localStorage.getItem('jwt_token');
            const responseRaw = await fetch(API_BASE + '/create_post.php', {
                method: 'POST',
                headers: { 'Authorization': 'Bearer ' + token }, // Allow browser to set Content-Type for FormData
                body: formData
            });
            const response = await responseRaw.json();
                
                if (response && (response.success || response.status === 'success')) {
                    alert('Post created successfully!');
                    clearPostForm();
                    reloadFeed();
                } else {
                    alert(response.message || 'Failed to create post');
                }
            } catch (error) {
                console.error('Error creating post:', error);
                alert('Error creating post');
            }
        }
        
        function clearPostForm() {
            document.getElementById('postContent').value = '';
            document.getElementById('allowComments').checked = true;
            document.getElementById('pinPost').checked = false;
            document.getElementById('imagePreviews').innerHTML = '';
            document.getElementById('filePreviews').innerHTML = '';
            document.getElementById('postImages').value = '';
            document.getElementById('postFiles').value = '';
        }
        
        function previewImages(input, previewContainerId) {
            const container = document.getElementById(previewContainerId);
            container.innerHTML = '';
            
            for (const file of input.files) {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = document.createElement('div');
                        preview.className = 'relative';
                        preview.innerHTML = `
                            <img src="${e.target.result}" alt="Preview" class="h-20 w-20 object-cover rounded-lg">
                            <button type="button" class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full h-5 w-5 flex items-center justify-center text-xs" 
                                    onclick="removeImagePreview(this)">
                                ×
                            </button>
                        `;
                        container.appendChild(preview);
                    };
                    reader.readAsDataURL(file);
                }
            }
        }
        
        function previewFiles(input, previewContainerId) {
            const container = document.getElementById(previewContainerId);
            container.innerHTML = '';
            
            for (const file of input.files) {
                const preview = document.createElement('div');
                preview.className = 'flex items-center p-2 border border-gray-200 rounded-lg';
                preview.innerHTML = `
                    <i data-lucide="file" class="h-5 w-5 text-gray-400 mr-2"></i>
                    <span class="text-sm text-gray-700 truncate" style="max-width: 100px;">${file.name}</span>
                    <button type="button" class="ml-2 text-red-500 text-xs" onclick="removeFilePreview(this)">
                        ×
                    </button>
                `;
                container.appendChild(preview);
            }
            
            lucide.createIcons();
        }
        
        function removeImagePreview(button) {
            button.parentElement.remove();
        }
        
        function removeFilePreview(button) {
            button.parentElement.remove();
        }
        
        function viewImage(url) {
            window.open(url, '_blank');
        }
        
        function showNoPostsMessage() {
            const feedContainer = document.getElementById('feedContainer');
            feedContainer.innerHTML = `
                <div class="text-center py-12">
                    <i data-lucide="newspaper" class="h-12 w-12 text-gray-300 mx-auto mb-4"></i>
                    <p class="text-gray-500">No posts to show yet</p>
                    <p class="text-gray-400 text-sm mt-2">Be the first to share something with the community!</p>
                </div>
            `;
        }
        
        function showErrorMessage() {
            const feedContainer = document.getElementById('feedContainer');
            feedContainer.innerHTML = `
                <div class="text-center py-12">
                    <i data-lucide="alert-circle" class="h-12 w-12 text-red-300 mx-auto mb-4"></i>
                    <p class="text-gray-500">Unable to load posts</p>
                    <button onclick="reloadFeed()" class="mt-4 text-blue-600 hover:text-blue-800">
                        Try Again
                    </button>
                </div>
            `;
        }
    </script>
</body>
</html>
