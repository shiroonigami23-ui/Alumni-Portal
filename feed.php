<?php
// Check authentication

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
    <title>Feed - RJIT Alumni Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Roboto+Slab:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/variety-ui.css">
    <script src="includes/auth-check.js"></script>
    <script src="assets/js/variety-ui.js" defer></script>
    <script src="assets/js/pwa.js" defer></script>
    
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
            max-height: 2200px;
        }

        .comment-thread-indent {
            border-left: 2px solid #e5e7eb;
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
            <div id="createPostSection" class="bg-white rounded-xl shadow-sm p-4 mb-8 hidden">
                <div class="flex items-start mb-2">
                    <img id="userFeedAvatar" 
                         src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='48' height='48' viewBox='0 0 48 48'%3E%3Crect width='48' height='48' fill='%23dbeafe'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='14' fill='%233b82f6'%3EU%3C/text%3E%3C/svg%3E" 
                         alt="Profile" 
                         class="h-11 w-11 rounded-full">
                    <div class="ml-3 flex-1">
                        <h3 class="font-medium text-gray-900 text-sm" id="userFeedName">Share your thoughts</h3>
                        <form id="createPostForm" class="mt-2">
                            <textarea id="postContent" 
                                      rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                                      placeholder="What's happening?"></textarea>

                            <div id="postMediaPanel" class="hidden mt-3 border border-dashed border-gray-300 rounded-xl p-3">
                                <div class="grid sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs text-gray-600">Images</label>
                                        <input type="file" id="postImages" name="images[]" accept="image/*" multiple class="block mt-1 text-xs">
                                        <div id="imagePreviews" class="flex flex-wrap gap-2 mt-2"></div>
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-600">Files</label>
                                        <input type="file" id="postFiles" name="files[]" multiple class="block mt-1 text-xs">
                                        <div id="filePreviews" class="flex flex-wrap gap-2 mt-2"></div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label class="text-xs text-gray-600">GIF URL (optional)</label>
                                    <input type="url" id="postGifUrl" class="w-full mt-1 px-2 py-1 border border-gray-300 rounded text-xs" placeholder="https://media.giphy.com/...">
                                </div>
                            </div>

                            <div class="mt-3 flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <button id="togglePostMediaBtn" type="button" class="p-2 rounded-full hover:bg-blue-50 text-blue-600" title="Media & files">
                                        <i data-lucide="image-plus" class="h-4 w-4"></i>
                                    </button>
                                    <input type="checkbox" id="allowComments" name="allow_comments" checked class="h-4 w-4 text-blue-600 rounded">
                                    <label for="allowComments" class="text-xs text-gray-700">Allow comments</label>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="clearPostForm()" class="px-3 py-1.5 border border-gray-300 rounded-full text-sm text-gray-700 hover:bg-gray-50 font-medium">Clear</button>
                                    <button type="submit" class="bg-blue-600 text-white px-5 py-1.5 rounded-full hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-medium">Post</button>
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
                        <div class="mt-2 flex items-center gap-2">
                            <input type="file" class="comment-image hidden" accept="image/*">
                            <input type="file" class="comment-file hidden">
                            <button type="button" class="comment-add-image p-1.5 rounded-full hover:bg-blue-50 text-blue-600" title="Add image"><i data-lucide="image" class="h-4 w-4"></i></button>
                            <button type="button" class="comment-add-file p-1.5 rounded-full hover:bg-blue-50 text-blue-600" title="Add file"><i data-lucide="paperclip" class="h-4 w-4"></i></button>
                            <input type="url" class="comment-gif-url flex-1 max-w-xs px-2 py-1 border border-gray-300 rounded text-xs" placeholder="GIF URL (optional)">
                        </div>
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
        let currentUserId = 0;
        let currentUserRole = '';
        let feedRefreshTimer = null;
        const openCommentPosts = new Set();
        const commentRefreshTimers = new Map();
        const commentRenderState = new Map();
        const viewedPostIds = new Set();
        let postViewObserver = null;
        let pendingSharedPostId = 0;
        let pendingOpenCommentsPostId = 0;
        const postStateCache = new Map();
        
        // Load feed data
        document.addEventListener('DOMContentLoaded', async function() {
            await loadUserProfile();
            await processSharedLinkOpen();
            processOpenCommentsParam();
            await loadFeed();
            setupEventListeners();
            startFeedAutoRefresh();
            focusSharedPostIfAny();
            openCommentsIfRequested();
        });

        window.addEventListener('beforeunload', () => {
            if (feedRefreshTimer) clearInterval(feedRefreshTimer);
            for (const timer of commentRefreshTimers.values()) {
                clearInterval(timer);
            }
            commentRefreshTimers.clear();
            if (postViewObserver) {
                postViewObserver.disconnect();
                postViewObserver = null;
            }
        });
        
        async function loadUserProfile() {
            try {
                const userData = localStorage.getItem('user_data');
                if (userData) {
                    const user = JSON.parse(userData);
                    currentUserId = parseInt(user.user_id || user.id || 0, 10) || 0;
                    currentUserRole = String(user.role || '').toLowerCase();
                    
                    // Update user info in create post section
                    document.getElementById('userFeedName').textContent = user.full_name || user.name || user.email || 'Share your thoughts';
                    
                    if (user.profile_picture || user.profile_picture_url || user.profile_pic) {
                        document.getElementById('userFeedAvatar').src = user.profile_picture || user.profile_picture_url || user.profile_pic;
                    }
                    
                    // Show create post section if user can post
                    const canPost = (user.role === 'admin' || user.role === 'faculty' || user.role === 'alumni' || !!user.can_post);
                    if (canPost) {
                        document.getElementById('createPostSection').classList.remove('hidden');
                    }
                }

                // Refresh from authoritative API and sync local cache
                const me = await makeApiCall('me.php');
                if (me && me.success && me.data) {
                    localStorage.setItem('user_data', JSON.stringify(me.data));
                    const user = me.data;
                    currentUserId = parseInt(user.user_id || user.id || 0, 10) || 0;
                    currentUserRole = String(user.role || '').toLowerCase();
                    document.getElementById('userFeedName').textContent = user.full_name || user.name || user.email || 'Share your thoughts';
                    if (user.profile_picture || user.profile_picture_url || user.profile_pic) {
                        document.getElementById('userFeedAvatar').src = user.profile_picture || user.profile_picture_url || user.profile_pic;
                    }
                    const canPost = (user.role === 'admin' || user.role === 'faculty' || user.role === 'alumni' || !!user.can_post);
                    if (canPost) {
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
                    let posts = response.data || [];
                    posts = posts.map((p) => ({ ...p, ...(postStateCache.get(Number(p.id)) || {}) }));
                    const totalPosts = response.total || 0;
                    
                    if (currentPage === 1) {
                        feedContainer.innerHTML = '';

                        if (posts.length === 0) {
                            showNoPostsMessage();
                        } else {
                            await renderPosts(posts);
                            openCommentsIfRequested();
                        }
                    } else {
                        await renderPosts(posts);
                        openCommentsIfRequested();
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
                    showErrorMessage(response?.message || 'Unable to load posts');
                }
            } catch (error) {
                console.error('Error loading feed:', error);
                showErrorMessage();
            } finally {
                isLoading = false;
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
            setupPostViewObserver(postsContainer);
            
            // Re-initialize icons
            lucide.createIcons();
        }
        
        async function createPostElement(post, isPinned = false) {
            const postElement = document.createElement('div');
            postElement.className = `post-card bg-white rounded-xl shadow-sm overflow-hidden ${isPinned ? 'pinned-post' : ''}`;
            postElement.dataset.postId = post.id;
            postElement.id = `post-${post.id}`;
            
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
            const hasReposted = post.user_has_reposted || false;
            
            // Check if comments are allowed
            const commentsAllowed = post.allow_comments !== false;
            
            postElement.innerHTML = `
                <div class="p-6">
                    <!-- Post Header -->
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                ${post.author_avatar ? 
                                    `<img src="${post.author_avatar}" alt="${post.author_name}" class="h-10 w-10 rounded-full cursor-pointer" onclick="goToProfile(${Number(post.user_id || 0)})">` : 
                                    `<div class="h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i data-lucide="user" class="h-5 w-5 text-blue-600"></i>
                                    </div>`}
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="font-semibold text-gray-900 cursor-pointer hover:underline" onclick="goToProfile(${Number(post.user_id || 0)})">${post.author_name}</h3>
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
                                    ${(attachment.type === 'image' || attachment.type === 'gif') ? 
                                        `<img src="${attachment.url}" alt="Attachment" class="w-full h-32 object-cover rounded-lg cursor-pointer hover:opacity-90" onclick="viewImage('${attachment.url}')">` : 
                                        `<a href="${attachment.url}" target="_blank" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                                            <i data-lucide="file" class="h-5 w-5 text-gray-400 mr-3"></i>
                                            <span class="text-sm text-gray-700 truncate">${attachment.name}</span>
                                        </a>`}
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                    
                    <!-- Post Actions (X-style compact counters) -->
                    <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 text-sm">
                        <button class="like-btn flex items-center py-2 px-2 rounded-lg hover:bg-red-50 ${hasLiked ? 'text-red-600' : 'text-gray-600'}">
                            <i data-lucide="heart" class="h-5 w-5 mr-2 ${hasLiked ? 'fill-current' : ''}"></i>
                            <span class="like-count">${post.likes_count || 0}</span>
                        </button>
                        
                        <button class="comment-toggle-btn flex items-center py-2 px-2 rounded-lg hover:bg-blue-50 text-gray-600 ${!commentsAllowed ? 'opacity-50 cursor-not-allowed' : ''}" 
                                ${!commentsAllowed ? 'disabled' : ''}>
                            <i data-lucide="message-square" class="h-5 w-5 mr-2"></i>
                            <span class="comment-count">${post.comments_count || 0}</span>
                        </button>

                        <button class="repost-btn flex items-center py-2 px-2 rounded-lg hover:bg-emerald-50 ${hasReposted ? 'text-emerald-600' : 'text-gray-600'}">
                            <i data-lucide="repeat-2" class="h-5 w-5 mr-2"></i>
                            <span class="repost-count">${post.reposts_count || 0}</span>
                        </button>
                        
                        <button class="share-btn flex items-center py-2 px-2 rounded-lg hover:bg-green-50 text-gray-600">
                            <i data-lucide="share-2" class="h-5 w-5 mr-2"></i>
                            <span class="share-count">${post.shares_count || 0}</span>
                        </button>

                        <button class="flex items-center py-2 px-2 rounded-lg text-gray-500 cursor-default">
                            <i data-lucide="bar-chart-2" class="h-5 w-5 mr-2"></i>
                            <span class="view-count">${post.view_count || 0}</span>
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

            const repostBtn = postElement.querySelector('.repost-btn');
            if (repostBtn) {
                repostBtn.addEventListener('click', async () => {
                    await handleRepost(post.id, repostBtn, postElement.querySelector('.repost-count'));
                });
            }
            
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

            const editBtn = postElement.querySelector('.edit-post-btn');
            if (editBtn) {
                editBtn.addEventListener('click', async () => {
                    menu.classList.add('hidden');
                    await handleEditPost(postElement, post.id);
                });
            }

            const deleteBtn = postElement.querySelector('.delete-post-btn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', async () => {
                    menu.classList.add('hidden');
                    await handleDeletePost(postElement, post.id);
                });
            }

            const reportPostBtn = postElement.querySelector('.report-post-btn');
            if (reportPostBtn) {
                reportPostBtn.addEventListener('click', async () => {
                    menu.classList.add('hidden');
                    await handleReportPost(post.id);
                });
            }

            const shareBtn = postElement.querySelector('.share-btn');
            if (shareBtn) {
                shareBtn.addEventListener('click', async () => {
                    try {
                        const shareRes = await makeApiCall('create_share_link.php', 'POST', { post_id: post.id });
                        if (!(shareRes && (shareRes.success || shareRes.status === 'success'))) {
                            throw new Error(shareRes?.message || 'Unable to create share link');
                        }
                        const url = `${window.location.origin}/${shareRes.share_path}`.replace(/([^:]\/)\/+/g, '$1');
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            await navigator.clipboard.writeText(url);
                        }
                        shareBtn.classList.add('text-green-600');
                        const shareCountEl = shareBtn.querySelector('.share-count');
                        if (shareCountEl) {
                            const current = Number(shareCountEl.textContent || 0);
                            postStateCache.set(Number(post.id), {
                                ...(postStateCache.get(Number(post.id)) || {}),
                                shares_count: Number.isFinite(current) ? current : 0
                            });
                        }
                    } catch (e) {
                        console.error('Share failed', e);
                    }
                });
            }
        }

        function setupPostViewObserver(postsContainer) {
            if (!postsContainer) return;
            if (!postViewObserver) {
                postViewObserver = new IntersectionObserver(async (entries) => {
                    for (const entry of entries) {
                        if (!entry.isIntersecting || entry.intersectionRatio < 0.55) continue;
                        const card = entry.target;
                        const postId = Number(card.dataset.postId || 0);
                        if (!postId || viewedPostIds.has(postId)) continue;
                        viewedPostIds.add(postId);
                        try {
                            const res = await makeApiCall('track_post_view.php', 'POST', { post_id: postId });
                            if (res && (res.success || res.status === 'success')) {
                                const viewEl = card.querySelector('.view-count');
                                if (viewEl && typeof res.view_count !== 'undefined') {
                                    viewEl.textContent = res.view_count;
                                }
                                postStateCache.set(postId, {
                                    ...(postStateCache.get(postId) || {}),
                                    view_count: Number(res.view_count || 0)
                                });
                            }
                        } catch (err) {
                            console.error('Failed to track post view:', err);
                        } finally {
                            postViewObserver.unobserve(card);
                        }
                    }
                }, { threshold: [0.55] });
            }

            postsContainer.querySelectorAll('.post-card').forEach((card) => {
                const postId = Number(card.dataset.postId || 0);
                if (postId && !viewedPostIds.has(postId)) {
                    postViewObserver.observe(card);
                }
            });
        }

        async function processSharedLinkOpen() {
            const params = new URLSearchParams(window.location.search);
            const sharedPost = Number(params.get('shared_post') || 0);
            const shareToken = (params.get('share_token') || '').trim();
            if (!sharedPost || !shareToken) return;
            pendingSharedPostId = sharedPost;
            try {
                const res = await makeApiCall('track_share_open.php', 'POST', {
                    post_id: sharedPost,
                    share_token: shareToken
                });
                if (res && (res.success || res.status === 'success')) {
                    postStateCache.set(sharedPost, {
                        ...(postStateCache.get(sharedPost) || {}),
                        shares_count: Number(res.shares_count || 0)
                    });
                    const countEl = document.querySelector(`#post-${sharedPost} .share-count`);
                    if (countEl && typeof res.shares_count !== 'undefined') {
                        countEl.textContent = res.shares_count;
                    }
                }
            } catch (e) {
                console.error('Failed to process shared link open:', e);
            }
        }

        function focusSharedPostIfAny() {
            if (!pendingSharedPostId) return;
            const postEl = document.getElementById(`post-${pendingSharedPostId}`);
            if (!postEl) return;
            postEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            postEl.classList.add('ring-2', 'ring-blue-300');
            setTimeout(() => postEl.classList.remove('ring-2', 'ring-blue-300'), 2200);
        }

        function processOpenCommentsParam() {
            const params = new URLSearchParams(window.location.search);
            const openComments = Number(params.get('open_comments') || 0);
            if (openComments > 0) pendingOpenCommentsPostId = openComments;
        }

        function openCommentsIfRequested() {
            if (!pendingOpenCommentsPostId) return;
            const postElement = document.getElementById(`post-${pendingOpenCommentsPostId}`);
            if (!postElement) return;
            const btn = postElement.querySelector('.comment-toggle-btn');
            if (btn && !btn.disabled) {
                btn.click();
            }
        }
        
        async function handleLike(postId, likeBtn, likeCountElement) {
            try {
                const response = await makeApiCall('react_to_post.php', 'POST', {
                    post_id: postId,
                    reaction: 'like'
                });
                
                if (response && (response.success || response.status === 'success')) {
                    const liked = typeof response.liked === 'boolean'
                        ? response.liked
                        : !likeBtn.classList.contains('text-red-600');

                    if (liked) {
                        likeBtn.classList.add('text-red-600');
                        likeBtn.querySelector('i').classList.add('fill-current');
                    } else {
                        likeBtn.classList.remove('text-red-600');
                        likeBtn.querySelector('i').classList.remove('fill-current');
                    }

                    if (typeof response.likes_count !== 'undefined') {
                        likeCountElement.textContent = response.likes_count;
                    }
                    postStateCache.set(Number(postId), {
                        ...(postStateCache.get(Number(postId)) || {}),
                        user_has_liked: liked,
                        likes_count: Number(response.likes_count || likeCountElement.textContent || 0)
                    });
                }
            } catch (error) {
                console.error('Error liking post:', error);
            }
        }

        async function handleRepost(postId, repostBtn, repostCountElement) {
            try {
                const response = await makeApiCall('toggle_repost.php', 'POST', { post_id: postId });
                if (response && (response.success || response.status === 'success')) {
                    const reposted = !!response.reposted;
                    if (reposted) {
                        repostBtn.classList.add('text-emerald-600');
                        repostBtn.classList.remove('text-gray-600');
                    } else {
                        repostBtn.classList.remove('text-emerald-600');
                        repostBtn.classList.add('text-gray-600');
                    }
                    if (typeof response.reposts_count !== 'undefined' && repostCountElement) {
                        repostCountElement.textContent = response.reposts_count;
                    }
                    postStateCache.set(Number(postId), {
                        ...(postStateCache.get(Number(postId)) || {}),
                        user_has_reposted: reposted,
                        reposts_count: Number(response.reposts_count || repostCountElement?.textContent || 0)
                    });
                }
            } catch (error) {
                console.error('Error reposting:', error);
            }
        }

        async function handleEditPost(postElement, postId) {
            const contentEl = postElement.querySelector('.mb-4 p');
            if (!contentEl) return;
            const currentContent = contentEl.textContent || '';
            const edited = prompt('Edit your post:', currentContent);
            if (edited === null) return;
            if (!edited.trim()) {
                alert('Post content cannot be empty.');
                return;
            }

            const response = await makeApiCall('edit_post.php', 'POST', {
                post_id: postId,
                content: edited.trim()
            });
            if (response && (response.success || response.status === 'success')) {
                contentEl.textContent = edited.trim();
            } else {
                alert(response?.message || 'Failed to edit post.');
            }
        }

        async function handleDeletePost(postElement, postId) {
            if (!confirm('Delete this post?')) return;
            const response = await makeApiCall('delete_post.php', 'POST', { post_id: postId });
            if (response && (response.success || response.status === 'success')) {
                postElement.remove();
            } else {
                alert(response?.message || 'Failed to delete post.');
            }
        }
        
        async function toggleComments(postElement, postId) {
            const commentBox = postElement.querySelector('.comment-box');
            
            if (commentBox) {
                // Toggle existing comment box
                commentBox.classList.toggle('open');
                if (commentBox.classList.contains('open')) {
                    openCommentPosts.add(postId);
                    const cached = commentRenderState.get(postId);
                    if (cached && Array.isArray(cached.comments)) {
                        renderCommentsTree(postId, commentBox.querySelector('.comments-list'), commentBox, cached.comments);
                    }
                    await loadComments(postId, commentBox.querySelector('.comments-list'), commentBox);
                    startCommentsAutoRefresh(postId, commentBox);
                } else {
                    openCommentPosts.delete(postId);
                    stopCommentsAutoRefresh(postId);
                }
            } else {
                // Create new comment box
                const template = document.getElementById('commentTemplate').content.cloneNode(true);
                const commentBox = template.querySelector('.comment-box');
                commentBox.classList.add('open');
                
                // Add comment box to post
                postElement.querySelector('.p-6').appendChild(commentBox);
                
                // Load comments
                openCommentPosts.add(postId);
                const cached = commentRenderState.get(postId);
                if (cached && Array.isArray(cached.comments)) {
                    renderCommentsTree(postId, commentBox.querySelector('.comments-list'), commentBox, cached.comments);
                }
                await loadComments(postId, commentBox.querySelector('.comments-list'), commentBox);
                startCommentsAutoRefresh(postId, commentBox);
                
                // Setup comment submission
                const commentInput = commentBox.querySelector('.comment-input');
                const postCommentBtn = commentBox.querySelector('.post-comment-btn');
                const imageInput = commentBox.querySelector('.comment-image');
                const fileInput = commentBox.querySelector('.comment-file');
                const gifInput = commentBox.querySelector('.comment-gif-url');
                const imageBtn = commentBox.querySelector('.comment-add-image');
                const fileBtn = commentBox.querySelector('.comment-add-file');

                if (imageBtn && imageInput) imageBtn.addEventListener('click', () => imageInput.click());
                if (fileBtn && fileInput) fileBtn.addEventListener('click', () => fileInput.click());
                if (currentUserRole === 'student') {
                    if (imageBtn) imageBtn.classList.add('hidden');
                    if (fileBtn) fileBtn.classList.add('hidden');
                    if (imageInput) imageInput.value = '';
                    if (fileInput) fileInput.value = '';
                }
                
                postCommentBtn.addEventListener('click', async () => {
                    await postComment(postId, commentInput.value.trim(), commentBox, imageInput?.files?.[0] || null, fileInput?.files?.[0] || null, gifInput?.value?.trim() || '', null);
                });
                
                commentInput.addEventListener('keypress', async (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        await postComment(postId, commentInput.value.trim(), commentBox, imageInput?.files?.[0] || null, fileInput?.files?.[0] || null, gifInput?.value?.trim() || '', null);
                    }
                });
            }
        }
        
        async function loadComments(postId, commentsContainer, commentBox = null, force = false) {
            try {
                const response = await makeApiCall(`get_comments.php?post_id=${postId}`);
                if (!(response && (response.success || response.status === 'success') && response.data)) {
                    return;
                }
                const comments = Array.isArray(response.data) ? response.data : [];
                const signature = comments
                    .map((c) => `${c.id}:${c.updated_at || c.created_at}:${c.likes_count || 0}:${c.parent_comment_id || 0}`)
                    .join('|');

                const prev = commentRenderState.get(postId);
                if (!force && prev && prev.signature === signature) {
                    return;
                }
                commentRenderState.set(postId, { signature, comments });
                renderCommentsTree(postId, commentsContainer, commentBox, comments);
                const postElement = commentsContainer.closest('.post-card');
                const countEl = postElement?.querySelector('.comment-count');
                if (countEl) {
                    countEl.textContent = comments.length;
                }
            } catch (error) {
                console.error('Error loading comments:', error);
            }
        }
        
        async function postComment(postId, content, commentBox, imageFile = null, fileAttachment = null, gifUrl = '', parentCommentId = null) {
            if (currentUserRole === 'student') {
                imageFile = null;
                fileAttachment = null;
            }
            if (!content.trim() && !imageFile && !fileAttachment && !gifUrl) return;
            
            try {
                const formData = new FormData();
                formData.append('post_id', String(postId));
                formData.append('content', content || '');
                if (parentCommentId) formData.append('parent_comment_id', String(parentCommentId));
                if (imageFile) formData.append('image', imageFile);
                if (fileAttachment) formData.append('file', fileAttachment);
                if (gifUrl) formData.append('gif_url', gifUrl);
                const token = localStorage.getItem('jwt_token');
                const responseRaw = await fetch(API_BASE + '/create_comment.php', {
                    method: 'POST',
                    headers: { 'Authorization': 'Bearer ' + token },
                    body: formData
                });
                const responseText = await responseRaw.text();
                let response = {};
                try {
                    response = responseText ? JSON.parse(responseText) : {};
                } catch (_error) {
                    response = { success: false, status: 'error', message: responseText || 'Invalid server response' };
                }
                
                if (response && (response.success || response.status === 'success')) {
                    // Clear input
                    commentBox.querySelector('.comment-input').value = '';
                    const img = commentBox.querySelector('.comment-image');
                    const file = commentBox.querySelector('.comment-file');
                    const gif = commentBox.querySelector('.comment-gif-url');
                    if (img) img.value = '';
                    if (file) file.value = '';
                    if (gif) gif.value = '';
                    
                    await loadComments(postId, commentBox.querySelector('.comments-list'), commentBox, true);
                    const postElement = commentBox.closest('.post-card');
                    const commentCount = postElement.querySelector('.comment-count');
                    const latest = commentRenderState.get(postId)?.comments?.length || 0;
                    commentCount.textContent = latest;
                }
            } catch (error) {
                console.error('Error posting comment:', error);
            }
        }

        function renderCommentsTree(postId, commentsContainer, commentBox, comments) {
            commentsContainer.innerHTML = '';
            if (!comments.length) {
                commentsContainer.innerHTML = `<p class="text-xs text-gray-500">No comments yet.</p>`;
                return;
            }

            const byId = new Map();
            const roots = [];
            for (const c of comments) {
                byId.set(Number(c.id), { ...c, children: [] });
            }
            for (const c of byId.values()) {
                if (c.parent_comment_id && byId.has(Number(c.parent_comment_id))) {
                    byId.get(Number(c.parent_comment_id)).children.push(c);
                } else {
                    roots.push(c);
                }
            }

            const dateValue = (v) => new Date(v || 0).getTime();
            roots.sort((a, b) => {
                const ownA = Number(a.author_id) === Number(currentUserId) ? 1 : 0;
                const ownB = Number(b.author_id) === Number(currentUserId) ? 1 : 0;
                if (ownA !== ownB) return ownB - ownA;
                return dateValue(b.created_at) - dateValue(a.created_at);
            });

            const sortChildren = (arr) => {
                arr.sort((a, b) => dateValue(a.created_at) - dateValue(b.created_at));
                arr.forEach((c) => sortChildren(c.children));
            };
            roots.forEach((r) => sortChildren(r.children));

            for (const root of roots) {
                commentsContainer.appendChild(renderCommentBranch(root, postId, commentBox, 0));
            }
            lucide.createIcons();
        }

        function renderCommentBranch(comment, postId, commentBox, level) {
            const fragment = document.createDocumentFragment();
            fragment.appendChild(renderCommentElement(comment, postId, commentBox, level));
            for (const child of (comment.children || [])) {
                fragment.appendChild(renderCommentBranch(child, postId, commentBox, level + 1));
            }
            return fragment;
        }

        function renderCommentElement(comment, postId, commentBox, level = 0) {
            const depth = Math.min(parseInt(comment.depth_level || 0, 10), 5);
            const wrapper = document.createElement('div');
            wrapper.className = `comment-item flex items-start ${Math.max(depth, level) > 0 ? 'comment-thread-indent pl-2' : ''}`;
            wrapper.dataset.commentId = String(comment.id);
            if (Math.max(depth, level) > 0) {
                wrapper.style.marginLeft = `${Math.max(depth, level) * 16}px`;
            }

            wrapper.innerHTML = `
                <div class="flex-shrink-0">
                    ${comment.author_avatar ?
                        `<img src="${comment.author_avatar}" alt="${comment.author_name}" class="h-8 w-8 rounded-full cursor-pointer" onclick="goToProfile(${Number(comment.author_id || 0)})">` :
                        `<div class="h-8 w-8 bg-gray-100 rounded-full flex items-center justify-center">
                            <i data-lucide="user" class="h-4 w-4 text-gray-400"></i>
                        </div>`}
                </div>
                <div class="ml-3 flex-1">
                    <div class="bg-white rounded-lg p-3 border border-gray-100">
                        <div class="flex items-center justify-between">
                            <h4 class="font-medium text-gray-900 text-sm cursor-pointer hover:underline" onclick="goToProfile(${Number(comment.author_id || 0)})">${comment.author_name}</h4>
                            <span class="text-xs text-gray-500">${formatDate(comment.created_at)} ${comment.is_edited ? '<span class="ml-1">(edited)</span>' : ''}</span>
                        </div>
                        <p class="text-gray-700 text-sm mt-1 whitespace-pre-line">${comment.content || ''}</p>
                        <div class="comment-edit-box hidden mt-2">
                            <textarea class="comment-edit-input w-full px-2 py-1 border border-gray-300 rounded text-sm resize-none" rows="2">${(comment.content || '').replace(/</g, '&lt;')}</textarea>
                            <div class="mt-2 flex items-center gap-2 justify-end">
                                <button type="button" class="comment-edit-cancel px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-50">Cancel</button>
                                <button type="button" class="comment-edit-save px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">Save</button>
                            </div>
                        </div>
                        ${comment.attachments && comment.attachments.length ? `
                            <div class="mt-2 flex flex-wrap gap-2">
                                ${comment.attachments.map(a => {
                                    if (a.type === 'image' || a.type === 'gif') {
                                        return `<img src="${a.url}" alt="${a.name || 'attachment'}" class="h-20 w-20 object-cover rounded border border-gray-200 cursor-pointer" onclick="viewImage('${a.url}')">`;
                                    }
                                    return `<a href="${a.url}" target="_blank" class="text-xs text-blue-600 underline">${a.name || 'Attachment'}</a>`;
                                }).join('')}
                            </div>
                        ` : ''}
                        <div class="mt-2 flex items-center gap-3">
                            <button type="button" class="comment-like-btn text-xs ${comment.user_has_liked ? 'text-red-600' : 'text-gray-600'} hover:text-red-600">
                                ${comment.user_has_liked ? 'Unlike' : 'Like'} <span class="comment-like-count">${comment.likes_count || 0}</span>
                            </button>
                            <button type="button" class="reply-toggle-btn text-xs text-blue-600 hover:text-blue-800">Reply</button>
                            ${comment.can_edit ? '<button type="button" class="comment-edit-btn text-xs text-gray-700 hover:text-gray-900">Edit</button>' : ''}
                            ${comment.can_delete ? '<button type="button" class="comment-delete-btn text-xs text-red-600 hover:text-red-700">Delete</button>' : ''}
                            <button type="button" class="comment-report-btn text-xs text-amber-700 hover:text-amber-800">Report</button>
                        </div>
                        <div class="reply-composer hidden mt-2">
                            <textarea class="reply-input w-full px-2 py-1 border border-gray-300 rounded text-sm resize-none" rows="2" placeholder="Write a reply..."></textarea>
                            <div class="mt-2 flex items-center gap-2">
                                <input type="file" class="reply-image hidden" accept="image/*">
                                <input type="file" class="reply-file hidden">
                                <button type="button" class="reply-add-image p-1 rounded-full hover:bg-blue-50 text-blue-600" title="Add image"><i data-lucide="image" class="h-4 w-4"></i></button>
                                <button type="button" class="reply-add-file p-1 rounded-full hover:bg-blue-50 text-blue-600" title="Add file"><i data-lucide="paperclip" class="h-4 w-4"></i></button>
                                <input type="url" class="reply-gif-url flex-1 px-2 py-1 border border-gray-300 rounded text-xs" placeholder="GIF URL (optional)">
                                <button type="button" class="post-reply-btn bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700">Reply</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            const toggleBtn = wrapper.querySelector('.reply-toggle-btn');
            const replyComposer = wrapper.querySelector('.reply-composer');
            const replyImage = wrapper.querySelector('.reply-image');
            const replyFile = wrapper.querySelector('.reply-file');
            const replyAddImage = wrapper.querySelector('.reply-add-image');
            const replyAddFile = wrapper.querySelector('.reply-add-file');
            const postReplyBtn = wrapper.querySelector('.post-reply-btn');
            const replyInput = wrapper.querySelector('.reply-input');
            const replyGif = wrapper.querySelector('.reply-gif-url');
            const likeBtn = wrapper.querySelector('.comment-like-btn');
            const deleteBtn = wrapper.querySelector('.comment-delete-btn');
            const editBtn = wrapper.querySelector('.comment-edit-btn');
            const reportBtn = wrapper.querySelector('.comment-report-btn');
            const editBox = wrapper.querySelector('.comment-edit-box');
            const editInput = wrapper.querySelector('.comment-edit-input');
            const editSaveBtn = wrapper.querySelector('.comment-edit-save');
            const editCancelBtn = wrapper.querySelector('.comment-edit-cancel');

            if (toggleBtn && replyComposer) {
                toggleBtn.addEventListener('click', () => replyComposer.classList.toggle('hidden'));
            }
            if (replyAddImage && replyImage) replyAddImage.addEventListener('click', () => replyImage.click());
            if (replyAddFile && replyFile) replyAddFile.addEventListener('click', () => replyFile.click());
            if (currentUserRole === 'student') {
                if (replyAddImage) replyAddImage.classList.add('hidden');
                if (replyAddFile) replyAddFile.classList.add('hidden');
                if (replyImage) replyImage.value = '';
                if (replyFile) replyFile.value = '';
            }

            if (postReplyBtn) {
                postReplyBtn.addEventListener('click', async () => {
                    await postComment(
                        postId,
                        replyInput?.value?.trim() || '',
                        commentBox,
                        replyImage?.files?.[0] || null,
                        replyFile?.files?.[0] || null,
                        replyGif?.value?.trim() || '',
                        comment.id
                    );
                    if (replyInput) replyInput.value = '';
                    if (replyGif) replyGif.value = '';
                    if (replyImage) replyImage.value = '';
                    if (replyFile) replyFile.value = '';
                    if (replyComposer) replyComposer.classList.add('hidden');
                });
            }

            if (likeBtn) {
                likeBtn.addEventListener('click', async () => {
                    await handleCommentLike(postId, comment.id, commentBox);
                });
            }
            if (deleteBtn) {
                deleteBtn.addEventListener('click', async () => {
                    await handleDeleteComment(postId, comment.id, commentBox);
                });
            }
            if (editBtn && editBox) {
                editBtn.addEventListener('click', () => {
                    editBox.classList.toggle('hidden');
                    if (!editBox.classList.contains('hidden') && editInput) {
                        editInput.focus();
                        editInput.selectionStart = editInput.value.length;
                        editInput.selectionEnd = editInput.value.length;
                    }
                });
            }
            if (editCancelBtn && editBox) {
                editCancelBtn.addEventListener('click', () => {
                    editBox.classList.add('hidden');
                    if (editInput) editInput.value = comment.content || '';
                });
            }
            if (editSaveBtn && editInput) {
                editSaveBtn.addEventListener('click', async () => {
                    await handleEditComment(postId, comment.id, editInput.value.trim(), commentBox);
                });
            }
            if (reportBtn) {
                reportBtn.addEventListener('click', async () => {
                    await handleReportComment(comment.id);
                });
            }

            return wrapper;
        }

        async function handleCommentLike(postId, commentId, commentBox) {
            const res = await makeApiCall('react_to_comment.php', 'POST', { comment_id: commentId, reaction: 'like' });
            if (res && (res.success || res.status === 'success')) {
                await loadComments(postId, commentBox.querySelector('.comments-list'), commentBox, true);
            }
        }

        async function handleDeleteComment(postId, commentId, commentBox) {
            if (!confirm('Delete this comment?')) return;
            const res = await makeApiCall('delete_comment.php', 'POST', { comment_id: commentId });
            if (res && (res.success || res.status === 'success')) {
                await loadComments(postId, commentBox.querySelector('.comments-list'), commentBox, true);
                const postElement = commentBox.closest('.post-card');
                const commentCount = postElement.querySelector('.comment-count');
                const latest = commentRenderState.get(postId)?.comments?.length || 0;
                commentCount.textContent = latest;
            } else {
                alert(res?.message || 'Failed to delete comment.');
            }
        }

        async function handleEditComment(postId, commentId, content, commentBox) {
            if (!content) {
                alert('Comment cannot be empty.');
                return;
            }
            const res = await makeApiCall('edit_comment.php', 'POST', { comment_id: commentId, content });
            if (res && (res.success || res.status === 'success')) {
                await loadComments(postId, commentBox.querySelector('.comments-list'), commentBox, true);
            } else {
                alert(res?.message || 'Failed to edit comment.');
            }
        }

        async function handleReportPost(postId) {
            if (!confirm('Report this post?')) return;
            const res = await makeApiCall('report_content.php', 'POST', { post_id: postId, reason: 'spam' });
            if (res && (res.success || res.status === 'success' || res.message)) {
                alert(res.message || 'Post reported.');
            } else {
                alert(res?.message || 'Failed to report post.');
            }
        }

        async function handleReportComment(commentId) {
            if (!confirm('Report this comment/reply?')) return;
            const res = await makeApiCall('report_content.php', 'POST', { comment_id: commentId, reason: 'spam' });
            if (res && (res.success || res.status === 'success' || res.message)) {
                alert(res.message || 'Comment reported.');
            } else {
                alert(res?.message || 'Failed to report comment.');
            }
        }

        function startCommentsAutoRefresh(postId, commentBox) {
            stopCommentsAutoRefresh(postId);
            const commentsContainer = commentBox?.querySelector('.comments-list');
            if (!commentsContainer) return;
            const timer = setInterval(async () => {
                if (!openCommentPosts.has(postId)) return;
                await loadComments(postId, commentsContainer, commentBox, false);
            }, 8000);
            commentRefreshTimers.set(postId, timer);
        }

        function stopCommentsAutoRefresh(postId) {
            const existing = commentRefreshTimers.get(postId);
            if (existing) {
                clearInterval(existing);
                commentRefreshTimers.delete(postId);
            }
        }

        async function prependNewlyCreatedPost(postId) {
            const response = await makeApiCall(`get_feed.php?page=1&filter=all&sort=newest`);
            if (!response || !(response.success || response.status === 'success') || !Array.isArray(response.data)) {
                await reloadFeed();
                return;
            }

            const created = response.data.find((p) => Number(p.id) === Number(postId));
            if (!created) {
                await reloadFeed();
                return;
            }

            const isVisibleInCurrentFilter =
                currentFilter === 'all' ||
                (currentFilter === 'announcements' && created.post_type === 'announcement');

            if (!isVisibleInCurrentFilter) {
                return;
            }

            const postElement = await createPostElement(created, false);
            const feedContainer = document.getElementById('feedContainer');
            let postsContainer = feedContainer.querySelector('.space-y-6');
            if (!postsContainer) {
                feedContainer.innerHTML = '<div class="space-y-6"></div>';
                postsContainer = feedContainer.querySelector('.space-y-6');
            }
            postsContainer.prepend(postElement);
            lucide.createIcons();
        }
        
        async function togglePinPost(postId, isCurrentlyPinned) {
            try {
                const endpoint = isCurrentlyPinned ? 'unpin_post.php' : 'pin_post.php';
                const response = await makeApiCall(endpoint, 'POST', {
                    post_id: postId
                });
                
                if (response && (response.success || response.status === 'success')) {
                    await reloadFeed();
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

            const toggleMediaBtn = document.getElementById('togglePostMediaBtn');
            const mediaPanel = document.getElementById('postMediaPanel');
            if (toggleMediaBtn && mediaPanel) {
                toggleMediaBtn.addEventListener('click', () => mediaPanel.classList.toggle('hidden'));
            }

        }
        
        function changeFilter(filter) {
            currentFilter = filter;
            currentPage = 1;
            
            // Update active button
            document.querySelectorAll('[id="allPosts"], [id="announcements"], [id="following"]').forEach(btn => {
                const mappedId = filter === 'all' ? 'allPosts' : filter;
                if (btn.id === mappedId) {
                    btn.className = 'px-4 py-2 rounded-lg bg-blue-100 text-blue-700 font-medium';
                } else {
                    btn.className = 'px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 font-medium';
                }
            });
            
            reloadFeed();
        }
        
        function reloadFeed() {
            currentPage = 1;
            openCommentPosts.clear();
            for (const timer of commentRefreshTimers.values()) {
                clearInterval(timer);
            }
            commentRefreshTimers.clear();
            document.getElementById('feedContainer').innerHTML = `
                <div class="space-y-6">
                    <div class="text-center py-12">
                        <i data-lucide="loader" class="h-8 w-8 animate-spin text-blue-600 mx-auto mb-4"></i>
                        <p class="text-gray-500">Loading posts...</p>
                    </div>
                </div>
            `;
            return loadFeed();
        }

        function startFeedAutoRefresh() {
            if (feedRefreshTimer) clearInterval(feedRefreshTimer);
            feedRefreshTimer = setInterval(async () => {
                await silentlyRefreshFeed();
            }, 8000);
        }

        async function silentlyRefreshFeed() {
            if (isLoading) return;
            const response = await makeApiCall(`get_feed.php?page=1&filter=${currentFilter}&sort=${currentSort}`);
            if (!(response && (response.success || response.status === 'success') && Array.isArray(response.data))) {
                return;
            }

            const freshPosts = response.data;
            if (!freshPosts.length) return;

            const existingIds = new Set(
                Array.from(document.querySelectorAll('.post-card')).map((el) => Number(el.dataset.postId))
            );
            const newOnes = freshPosts.filter((p) => !existingIds.has(Number(p.id)));
            if (!newOnes.length) return;

            const feedContainer = document.getElementById('feedContainer');
            let postsContainer = feedContainer.querySelector('.space-y-6');
            if (!postsContainer) {
                feedContainer.innerHTML = '<div class="space-y-6"></div>';
                postsContainer = feedContainer.querySelector('.space-y-6');
            }

            for (const post of newOnes.reverse()) {
                const postElement = await createPostElement(post);
                postsContainer.prepend(postElement);
            }
            lucide.createIcons();
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
            const gifUrl = document.getElementById('postGifUrl').value.trim();
            
            if (!content && !gifUrl && document.getElementById('postImages').files.length === 0 && document.getElementById('postFiles').files.length === 0) {
                alert('Please add content or media for your post');
                return;
            }
            
            const formData = new FormData();
            formData.append('content', content);
            formData.append('allow_comments', allowComments ? '1' : '0');
            if (gifUrl) {
                formData.append('gif_url', gifUrl);
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
            const responseText = await responseRaw.text();
            let response = {};
            try {
                response = responseText ? JSON.parse(responseText) : {};
            } catch (_error) {
                response = { success: false, status: 'error', message: responseText || 'Invalid server response' };
            }
                
                if (response && (response.success || response.status === 'success')) {
                    clearPostForm();
                    await prependNewlyCreatedPost(response.post_id);
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
            document.getElementById('postGifUrl').value = '';
            document.getElementById('allowComments').checked = true;
            document.getElementById('imagePreviews').innerHTML = '';
            document.getElementById('filePreviews').innerHTML = '';
            document.getElementById('postImages').value = '';
            document.getElementById('postFiles').value = '';
            const mediaPanel = document.getElementById('postMediaPanel');
            if (mediaPanel) mediaPanel.classList.add('hidden');
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

        function goToProfile(userId) {
            const uid = Number(userId || 0);
            if (!uid) return;
            window.location.href = `profile.php?id=${uid}`;
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
        
        function showErrorMessage(message = 'Unable to load posts') {
            const feedContainer = document.getElementById('feedContainer');
            feedContainer.innerHTML = `
                <div class="text-center py-12">
                    <i data-lucide="alert-circle" class="h-12 w-12 text-red-300 mx-auto mb-4"></i>
                    <p class="text-gray-500">${message}</p>
                    <button onclick="reloadFeed()" class="mt-4 text-blue-600 hover:text-blue-800">
                        Try Again
                    </button>
                </div>
            `;
        }
    </script>
</body>
</html>
