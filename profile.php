<?php
// Check authentication


// Get user ID from query parameter or use current user
$userId = isset($_GET['id']) ? $_GET['id'] : null;
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
    <title>Profile - RJIT Alumni Portal</title>
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
        .cover-image {
            height: 300px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-size: cover;
            background-position: center;
        }
        
        .profile-avatar {
            margin-top: -75px;
            border: 4px solid white;
        }
        
        .tab-active {
            border-bottom: 3px solid #2563eb;
            color: #2563eb;
            font-weight: 600;
        }
        
        .skill-badge {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .private-badge {
            background-color: #fef3c7;
            color: #92400e;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="md:pl-64">
        <!-- Cover Image -->
        <div id="profileCover" class="cover-image relative">
            <div class="absolute inset-0 bg-black bg-opacity-30"></div>
            <div class="absolute bottom-6 left-8 text-white">
                <h1 class="text-3xl font-bold" id="profileName">Loading...</h1>
                <p class="text-lg opacity-90" id="profileTitle">RJIT Community</p>
            </div>
            
            <!-- Edit Cover Button (only for own profile) -->
            <div id="editCoverBtn" class="absolute bottom-6 right-8 hidden">
                <button id="editCoverActionBtn" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg flex items-center" type="button">
                    <i data-lucide="camera" class="h-4 w-4 mr-2"></i>
                    Edit Cover
                </button>
                <input type="file" id="coverUpload" accept="image/*" class="hidden">
            </div>
        </div>

        <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Profile Header -->
            <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-8">
                <div class="flex items-end">
                    <!-- Profile Avatar -->
                    <div class="relative">
                        <img id="profileAvatar" 
                             src="https://via.placeholder.com/150?text=%20" 
                             alt="Profile" 
                             class="profile-avatar h-32 w-32 rounded-full">
                        
                        <!-- Edit Avatar Button (only for own profile) -->
                        <div id="editAvatarBtn" class="absolute bottom-0 right-0 hidden">
                            <label for="avatarUpload" class="cursor-pointer bg-blue-600 text-white p-2 rounded-full hover:bg-blue-700">
                                <i data-lucide="camera" class="h-4 w-4"></i>
                                <input type="file" id="avatarUpload" accept="image/*" class="hidden">
                            </label>
                        </div>
                    </div>
                    
                    <!-- Profile Info -->
                    <div class="ml-6 mb-2">
                        <div class="flex items-center space-x-3">
                            <h2 class="text-2xl font-bold text-gray-900" id="profileDisplayName">Loading...</h2>
                            <span id="profileRoleBadge" class="px-3 py-1 rounded-full text-sm font-medium"></span>
                            <span id="privacyBadge" class="private-badge px-3 py-1 rounded-full text-sm font-medium hidden">
                                <i data-lucide="lock" class="h-3 w-3 inline mr-1"></i>
                                Private
                            </span>
                        </div>
                        <p class="text-gray-600 mt-1" id="profileHeadline">Member of RJIT Community</p>
                        <div class="flex items-center space-x-4 mt-3 text-sm text-gray-500">
                            <span id="connectionCount">0 connections</span>
                            <span id="postCount">0 posts</span>
                            <span id="joinedDate">Joined recently</span>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="mt-4 md:mt-0 flex space-x-3" id="profileActions">
                    <!-- Actions will be loaded based on profile ownership/privacy -->
                </div>
            </div>

            <!-- Profile Tabs -->
            <div class="border-b border-gray-200 mb-8">
                <nav class="flex space-x-8">
                    <button data-tab="timeline" 
                            class="tab-button py-3 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 tab-active">
                        <i data-lucide="layout-grid" class="h-4 w-4 inline mr-2"></i>
                        Timeline
                    </button>
                    <button data-tab="about" 
                            class="tab-button py-3 px-1 text-sm font-medium text-gray-500 hover:text-gray-700">
                        <i data-lucide="user" class="h-4 w-4 inline mr-2"></i>
                        About
                    </button>
                    <button data-tab="badges" 
                            class="tab-button py-3 px-1 text-sm font-medium text-gray-500 hover:text-gray-700">
                        <i data-lucide="award" class="h-4 w-4 inline mr-2"></i>
                        Badges
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div id="tabContent">
                <!-- Timeline Tab -->
                <div id="timelineTab" class="tab-content active">
                    <!-- Pinned Posts -->
                    <div id="pinnedPostsSection" class="mb-8 hidden">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i data-lucide="pin" class="h-5 w-5 text-amber-500 mr-2"></i>
                            Pinned Posts
                        </h3>
                        <div id="pinnedPosts" class="space-y-4">
                            <!-- Pinned posts will be loaded here -->
                        </div>
                    </div>

                    <!-- Create Post (only for own profile) -->
                    <div id="createPostSection" class="mb-8 hidden">
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <div class="flex items-start">
                                <img id="timelineAvatar" 
                                     src="https://via.placeholder.com/48?text=%20" 
                                     alt="Profile" 
                                     class="h-12 w-12 rounded-full mr-4">
                                <div class="flex-1">
                                    <textarea id="timelinePostContent" 
                                              rows="3"
                                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                                              placeholder="Share an update..."></textarea>
                                    
                                    <div class="flex items-center justify-between mt-4">
                                        <div class="flex items-center space-x-4">
                                            <label for="timelineImage" class="cursor-pointer text-gray-600 hover:text-blue-600">
                                                <i data-lucide="image" class="h-5 w-5"></i>
                                                <input type="file" id="timelineImage" accept="image/*" class="hidden">
                                            </label>
                                            <label for="timelineFile" class="cursor-pointer text-gray-600 hover:text-blue-600">
                                                <i data-lucide="paperclip" class="h-5 w-5"></i>
                                                <input type="file" id="timelineFile" class="hidden">
                                            </label>
                                            <div class="flex items-center">
                                                <input type="checkbox" id="timelineAllowComments" checked class="h-4 w-4 text-blue-600 rounded">
                                                <label for="timelineAllowComments" class="ml-2 text-sm text-gray-600">Allow comments</label>
                                            </div>
                                        </div>
                                        <button onclick="createTimelinePost()" 
                                                class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-medium">
                                            Post
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline Posts -->
                    <div class="mb-4 border-b border-gray-200">
                        <div class="flex items-center gap-4 text-sm">
                            <button class="timeline-subtab px-3 py-2 font-medium text-blue-600 border-b-2 border-blue-600" data-subtab="posts">Posts</button>
                            <button class="timeline-subtab px-3 py-2 font-medium text-gray-600 hover:text-gray-800" data-subtab="replies">Replies</button>
                            <button class="timeline-subtab px-3 py-2 font-medium text-gray-600 hover:text-gray-800" data-subtab="media">Media</button>
                            <button class="timeline-subtab px-3 py-2 font-medium text-gray-600 hover:text-gray-800" data-subtab="reposts">Reposts</button>
                        </div>
                    </div>
                    <div id="timelinePosts" class="space-y-6">
                        <!-- Posts will be loaded here -->
                        <div class="text-center py-12">
                            <i data-lucide="loader" class="h-8 w-8 animate-spin text-blue-600 mx-auto mb-4"></i>
                            <p class="text-gray-500">Loading posts...</p>
                        </div>
                    </div>
                    <div id="timelineReplies" class="space-y-4 hidden"></div>
                </div>

                <!-- About Tab -->
                <div id="aboutTab" class="tab-content hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Left Column: Basic Info -->
                        <div class="lg:col-span-2">
                            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>
                                    <button id="editBasicInfo" class="text-blue-600 hover:text-blue-800 text-sm font-medium hidden">
                                        Edit
                                    </button>
                                </div>
                                
                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-500">Full Name</label>
                                            <p id="aboutName" class="mt-1 text-gray-900">Loading...</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-500">Email</label>
                                            <p id="aboutEmail" class="mt-1 text-gray-900">Loading...</p>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-500">Role</label>
                                            <p id="aboutRole" class="mt-1 text-gray-900">Loading...</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-500">Branch/Department</label>
                                            <p id="aboutBranch" class="mt-1 text-gray-900">Loading...</p>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-500">Graduation Year</label>
                                            <p id="aboutGraduationYear" class="mt-1 text-gray-900">-</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-500">Current Status</label>
                                            <p id="aboutCurrentStatus" class="mt-1 text-gray-900">-</p>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Bio</label>
                                        <p id="aboutBio" class="mt-1 text-gray-900 whitespace-pre-line">No bio yet</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Education & Work -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-semibold text-gray-900">Education & Work</h3>
                                    <button id="editEducationWork" class="text-blue-600 hover:text-blue-800 text-sm font-medium hidden">
                                        Edit
                                    </button>
                                </div>
                                
                                <div class="space-y-6">
                                    <!-- Education -->
                                    <div>
                                        <h4 class="font-medium text-gray-900 mb-3 flex items-center">
                                            <i data-lucide="graduation-cap" class="h-5 w-5 text-blue-600 mr-2"></i>
                                            Education
                                        </h4>
                                        <div id="educationList">
                                            <div class="pl-7">
                                                <p class="font-medium text-gray-900">Rajiv Gandhi Institute of Technology</p>
                                                <p class="text-gray-600" id="educationDetails">Loading...</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Work Experience -->
                                    <div>
                                        <h4 class="font-medium text-gray-900 mb-3 flex items-center">
                                            <i data-lucide="briefcase" class="h-5 w-5 text-green-600 mr-2"></i>
                                            Work Experience
                                        </h4>
                                        <div id="workExperienceList">
                                            <div class="pl-7">
                                                <p class="text-gray-500">No work experience added</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Skills & Contact -->
                        <div class="space-y-6">
                            <!-- Skills -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="font-semibold text-gray-900">Skills</h3>
                                    <button id="editSkills" class="text-blue-600 hover:text-blue-800 text-sm font-medium hidden">
                                        Edit
                                    </button>
                                </div>
                                
                                <div id="skillsList" class="flex flex-wrap gap-2">
                                    <!-- Skills will be loaded here -->
                                    <p class="text-gray-500">No skills added</p>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="font-semibold text-gray-900">Contact Information</h3>
                                    <button id="editContactInfo" class="text-blue-600 hover:text-blue-800 text-sm font-medium hidden">
                                        Edit
                                    </button>
                                </div>
                                
                                <div class="space-y-3" id="contactInfo">
                                    <div class="flex items-center">
                                        <i data-lucide="mail" class="h-4 w-4 text-gray-400 mr-3"></i>
                                        <span id="contactEmail" class="text-gray-700">Loading...</span>
                                    </div>
                                    <div class="flex items-center">
                                        <i data-lucide="phone" class="h-4 w-4 text-gray-400 mr-3"></i>
                                        <span id="contactPhone" class="text-gray-700">Not provided</span>
                                    </div>
                                    <div class="flex items-center">
                                        <i data-lucide="map-pin" class="h-4 w-4 text-gray-400 mr-3"></i>
                                        <span id="contactLocation" class="text-gray-700">Not provided</span>
                                    </div>
                                    <div class="flex items-center">
                                        <i data-lucide="link" class="h-4 w-4 text-gray-400 mr-3"></i>
                                        <span id="contactWebsite" class="text-gray-700">Not provided</span>
                                    </div>
                                </div>
                                
                                <!-- Social Links -->
                                <div class="mt-6" id="socialLinks">
                                    <h4 class="font-medium text-gray-900 mb-3">Social Links</h4>
                                    <div class="space-y-2">
                                        <!-- Social links will be loaded here -->
                                        <p class="text-gray-500 text-sm">No social links added</p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Badges Tab -->
                <div id="badgesTab" class="tab-content hidden">
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6">Achievements & Badges</h3>
                        
                        <div id="badgesList" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                            <!-- Badges will be loaded here -->
                            <div class="text-center py-8">
                                <i data-lucide="loader" class="h-8 w-8 animate-spin text-blue-600 mx-auto mb-4"></i>
                                <p class="text-gray-500">Loading badges...</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Global variables
        let currentUserId = null;
        let profileUserId = <?php echo $userId ? "'$userId'" : 'null'; ?>;
        let isOwnProfile = false;
        let profileData = null;
        let timelinePostsData = [];
        let timelineRepliesData = [];
        let currentTimelineSubtab = 'posts';
        
        // Load profile data
        document.addEventListener('DOMContentLoaded', async function() {
            await loadCurrentUser();
            await loadProfileData();
            setupEventListeners();
        });
        
        async function loadCurrentUser() {
            try {
                const userData = localStorage.getItem('user_data');
                if (userData) {
                    const user = JSON.parse(userData);
                    currentUserId = parseInt(user.user_id || user.id || 0, 10) || null;
                    
                    // If no profile ID specified, show current user's profile
                    if (!profileUserId) {
                        profileUserId = currentUserId;
                        isOwnProfile = true;
                    } else {
                        isOwnProfile = (parseInt(profileUserId) === currentUserId);
                    }
                }
            } catch (error) {
                console.error('Error loading current user:', error);
            }
        }
        
        async function loadProfileData() {
            try {
                if (!profileUserId) {
                    showProfileError();
                    return;
                }
                const response = await makeApiCall(`get_user_profile.php?user_id=${profileUserId}`);
                
                if (response && (response.success || response.status === 'success')) {
                    profileData = normalizeProfileData(response.data || {});
                    renderProfile(profileData);
                    await loadProfilePosts();
                    await loadProfileReplies();
                    renderTimelineSubtab();
                    await loadBadges();
                    
                    if (isOwnProfile) {
                        showEditButtons();
                    }
                } else {
                    showProfileError();
                }
            } catch (error) {
                console.error('Error loading profile:', error);
                showProfileError();
            }
        }

        function normalizeProfileData(data) {
            return {
                ...data,
                id: data.id || data.user_id || profileUserId,
                name: data.name || data.full_name || 'User',
                avatar: data.avatar || data.profile_picture_url || data.profile_picture || '',
                cover_photo_url: data.cover_photo_url || data.cover || '',
                headline: data.headline || data.bio || 'Member of RJIT Community',
                current_position: data.current_position || data.job_role || '',
                current_company: data.current_company || '',
                phone: data.phone || data.contact_number || '',
                location: data.location || [data.location_city, data.location_country].filter(Boolean).join(', '),
                website: data.website || data.personal_website || '',
                social_links: data.social_links || {
                    linkedin: data.linkedin_url || '',
                    github: data.github_url || '',
                    twitter: data.twitter_url || ''
                }
            };
        }
        
        function renderProfile(data) {
            // Update profile header
            document.getElementById('profileName').textContent = data.name || 'User';
            document.getElementById('profileDisplayName').textContent = data.name || 'User';
            document.getElementById('profileTitle').textContent = getProfileTitle(data);
            document.getElementById('profileHeadline').textContent = data.headline || 'Member of RJIT Community';
            
            // Update avatar
            if (data.avatar) {
                document.getElementById('profileAvatar').src = data.avatar;
                document.getElementById('timelineAvatar').src = data.avatar;
            }
            applyProfileCover(data);
            
            // Update role badge
            const roleBadge = document.getElementById('profileRoleBadge');
            roleBadge.textContent = data.role ? data.role.toUpperCase() : 'MEMBER';
            roleBadge.className = getRoleBadgeClass(data.role);
            
            // Update privacy badge
            const privacyBadge = document.getElementById('privacyBadge');
            if (data.is_private) {
                privacyBadge.classList.remove('hidden');
            }
            
            // Update stats
            document.getElementById('connectionCount').textContent = `${data.connections_count || 0} connections`;
            document.getElementById('postCount').textContent = `${data.posts_count || 0} posts`;
            document.getElementById('joinedDate').textContent = `Joined ${formatDate(data.created_at, 'MMMM YYYY')}`;
            
            // Update action buttons
            renderActionButtons(data);
            
            // Update about tab
            updateAboutTab(data);
        }
        
        function getProfileTitle(data) {
            if (data.role === 'alumni') {
                return `Class of ${data.graduation_year || 'N/A'} • ${data.current_position || 'Alumni'}`;
            } else if (data.role === 'faculty') {
                return `${data.designation || 'Faculty'} • ${data.department || 'RJIT'}`;
            } else if (data.role === 'student') {
                return `Student • ${data.branch || 'RJIT'} • Class of ${data.graduation_year || 'N/A'}`;
            }
            return 'RJIT Community Member';
        }

        function applyProfileCover(data) {
            const cover = document.getElementById('profileCover');
            if (!cover) return;
            const fallbackFaculty = 'assets/images/rjit_updates/anjuman_1.jpeg';
            const url = data.cover_photo_url || (String(data.role || '').toLowerCase() === 'faculty' ? fallbackFaculty : '');
            if (url) {
                cover.style.backgroundImage = `url('${url}')`;
            } else {
                cover.style.backgroundImage = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
            }
        }
        
        function getRoleBadgeClass(role) {
            switch(role) {
                case 'admin': return 'px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-sm font-medium';
                case 'faculty': return 'px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium';
                case 'alumni': return 'px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium';
                case 'student': return 'px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium';
                default: return 'px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm font-medium';
            }
        }
        
        function renderActionButtons(data) {
            const actionsContainer = document.getElementById('profileActions');
            
            if (isOwnProfile) {
                actionsContainer.innerHTML = `
                    <button onclick="window.location.href='settings.php'" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
                        <i data-lucide="settings" class="h-4 w-4 inline mr-2"></i>
                        Settings
                    </button>
                    <button onclick="editProfile()" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                        <i data-lucide="edit" class="h-4 w-4 inline mr-2"></i>
                        Edit Profile
                    </button>
                `;
            } else {
                if (data.is_private) {
                    actionsContainer.innerHTML = `
                        <button disabled
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-400 font-medium opacity-50 cursor-not-allowed">
                            <i data-lucide="lock" class="h-4 w-4 inline mr-2"></i>
                            Private Profile
                        </button>
                    `;
                } else {
                    actionsContainer.innerHTML = `
                        <button onclick="sendMessage(${data.id})" 
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
                            <i data-lucide="message-square" class="h-4 w-4 inline mr-2"></i>
                            Message
                        </button>
                        <button id="connectBtn" onclick="connect(${data.id})" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                            <i data-lucide="user-plus" class="h-4 w-4 inline mr-2"></i>
                            ${data.is_connected ? 'Following' : 'Follow'}
                        </button>
                    `;
                }
            }
            
            lucide.createIcons();
        }
        
        function updateAboutTab(data) {
            // Basic Info
            document.getElementById('aboutName').textContent = data.name || 'N/A';
            document.getElementById('aboutEmail').textContent = data.email || 'N/A';
            document.getElementById('aboutRole').textContent = data.role ? data.role.charAt(0).toUpperCase() + data.role.slice(1) : 'N/A';
            document.getElementById('aboutBranch').textContent = data.branch || data.department || 'N/A';
            document.getElementById('aboutGraduationYear').textContent = data.graduation_year || '-';
            document.getElementById('aboutCurrentStatus').textContent = getCurrentStatus(data);
            document.getElementById('aboutBio').textContent = data.bio || 'No bio yet';
            
            // Education
            const educationDetails = document.getElementById('educationDetails');
            if (data.role === 'student' || data.role === 'alumni') {
                educationDetails.textContent = `${data.course || 'B.Tech'} in ${data.branch || 'CSE'} (${data.graduation_year || 'Expected'})`;
            } else if (data.role === 'faculty') {
                educationDetails.textContent = `${data.qualification || 'N/A'} • ${data.department || 'Department'}`;
            }
            
            // Work Experience
            const workExpList = document.getElementById('workExperienceList');
            if (data.current_company && data.current_position) {
                workExpList.innerHTML = `
                    <div class="pl-7">
                        <p class="font-medium text-gray-900">${data.current_position}</p>
                        <p class="text-gray-600">${data.current_company}</p>
                        ${data.work_experience ? `<p class="text-gray-500 text-sm mt-1">${data.work_experience}</p>` : ''}
                    </div>
                `;
            }
            
            // Skills
            const skillsList = document.getElementById('skillsList');
            if (data.skills && data.skills.length > 0) {
                skillsList.innerHTML = data.skills.map(skill => `
                    <span class="skill-badge px-3 py-1 rounded-full text-sm">${skill}</span>
                `).join('');
            }
            
            // Contact Info
            document.getElementById('contactEmail').textContent = data.email || 'N/A';
            document.getElementById('contactPhone').textContent = data.phone || 'Not provided';
            document.getElementById('contactLocation').textContent = data.location || 'Not provided';
            document.getElementById('contactWebsite').textContent = data.website || 'Not provided';
            
            // Social Links
            const socialLinks = document.getElementById('socialLinks');
            if (data.social_links && Object.keys(data.social_links).length > 0) {
                socialLinks.innerHTML = '<h4 class="font-medium text-gray-900 mb-3">Social Links</h4><div class="space-y-2"></div>';
                const container = socialLinks.querySelector('.space-y-2');
                
                Object.entries(data.social_links).forEach(([platform, url]) => {
                    const link = document.createElement('a');
                    link.href = url;
                    link.target = '_blank';
                    link.className = 'flex items-center text-blue-600 hover:text-blue-800';
                    link.innerHTML = `<i data-lucide="external-link" class="h-4 w-4 mr-2"></i>${platform}`;
                    container.appendChild(link);
                });
                
                lucide.createIcons();
            }
        }
        
        function getCurrentStatus(data) {
            if (data.role === 'alumni') {
                return `${data.current_position || 'Professional'} at ${data.current_company || 'Various Companies'}`;
            } else if (data.role === 'faculty') {
                return `${data.designation || 'Faculty'} at RJIT`;
            } else if (data.role === 'student') {
                return `Student at RJIT`;
            }
            return 'Community Member';
        }
        
        async function loadProfilePosts() {
            try {
                const response = await makeApiCall(`get_feed.php?user_id=${profileUserId}&filter=all`);
                if (response && (response.success || response.status === 'success') && response.data) {
                    const posts = response.data.filter((p) => !!p && !!p.id);
                    timelinePostsData = posts;
                    
                    // Check for pinned posts
                    const pinnedPosts = posts.filter(post => post.is_pinned);
                    if (pinnedPosts.length > 0) {
                        await loadPinnedPosts(pinnedPosts);
                    }
                    
                    // Show create post section for own profile
                    if (isOwnProfile) {
                        document.getElementById('createPostSection').classList.remove('hidden');
                    }
                    renderTimelineSubtab();
                } else {
                    timelinePostsData = [];
                    renderTimelineSubtab();
                }
            } catch (error) {
                console.error('Error loading profile posts:', error);
            }
        }

        async function loadProfileReplies() {
            try {
                const response = await makeApiCall(`get_user_replies.php?user_id=${profileUserId}`);
                if (response && (response.success || response.status === 'success') && Array.isArray(response.data)) {
                    timelineRepliesData = response.data;
                } else {
                    timelineRepliesData = [];
                }
            } catch (error) {
                console.error('Error loading profile replies:', error);
                timelineRepliesData = [];
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
            }
        }
        
        async function createPostElement(post, isPinned = false) {
            const postElement = document.createElement('div');
            postElement.className = `bg-white rounded-xl shadow-sm p-6 ${isPinned ? 'border-l-4 border-amber-500 bg-amber-50' : ''}`;
            postElement.id = `profile-post-${post.id}`;
            
            let content = '';
            if (post.content) {
                content = post.content;
            } else if (post.content_file_path) {
                try { content = await fetchTextContent(post.content_file_path); } catch (_) {}
            }
            
            postElement.innerHTML = `
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h4 class="font-semibold text-gray-900">${post.title || 'Post'}</h4>
                        <p class="text-sm text-gray-500 mt-1">${formatDate(post.created_at)}</p>
                    </div>
                    ${isPinned ? '<span class="text-amber-600 font-medium">📌 Pinned</span>' : ''}
                </div>
                <p class="text-gray-700 mb-4 whitespace-pre-line">${content}</p>
                <div class="flex items-center text-sm text-gray-500">
                    <button class="profile-like-btn flex items-center mr-4 hover:text-red-600 ${post.user_has_liked ? 'text-red-600' : ''}">
                        <i data-lucide="heart" class="h-4 w-4 mr-1"></i>
                        <span class="profile-like-count">${post.likes_count || 0}</span>
                    </button>
                    <a href="feed.php?open_comments=${post.id}#post-${post.id}" class="flex items-center mr-4 hover:text-blue-600">
                        <i data-lucide="message-square" class="h-4 w-4 mr-1"></i>
                        <span>${post.comments_count || 0}</span>
                    </a>
                    <button class="profile-repost-btn flex items-center mr-4 hover:text-emerald-600 ${post.user_has_reposted ? 'text-emerald-600' : ''}">
                        <i data-lucide="repeat-2" class="h-4 w-4 mr-1"></i>
                        <span class="profile-repost-count">${post.reposts_count || 0}</span>
                    </button>
                    <button class="profile-share-btn flex items-center mr-4 hover:text-green-600">
                        <i data-lucide="share-2" class="h-4 w-4 mr-1"></i>
                        <span>${post.shares_count || 0}</span>
                    </button>
                    <button class="profile-report-btn flex items-center hover:text-amber-700">
                        <i data-lucide="flag" class="h-4 w-4 mr-1"></i>
                        <span>Report</span>
                    </button>
                </div>
            `;
            
            const likeBtn = postElement.querySelector('.profile-like-btn');
            if (likeBtn) {
                likeBtn.addEventListener('click', async () => {
                    const res = await makeApiCall('react_to_post.php', 'POST', { post_id: post.id, reaction: 'like' });
                    if (res && (res.success || res.status === 'success')) {
                        likeBtn.classList.toggle('text-red-600', !!res.liked);
                        const countEl = postElement.querySelector('.profile-like-count');
                        if (countEl && typeof res.likes_count !== 'undefined') {
                            countEl.textContent = res.likes_count;
                        }
                    }
                });
            }

            const repostBtn = postElement.querySelector('.profile-repost-btn');
            if (repostBtn) {
                repostBtn.addEventListener('click', async () => {
                    const res = await makeApiCall('toggle_repost.php', 'POST', { post_id: post.id });
                    if (res && (res.success || res.status === 'success')) {
                        repostBtn.classList.toggle('text-emerald-600', !!res.reposted);
                        const countEl = postElement.querySelector('.profile-repost-count');
                        if (countEl && typeof res.reposts_count !== 'undefined') {
                            countEl.textContent = res.reposts_count;
                        }
                    }
                });
            }

            const shareBtn = postElement.querySelector('.profile-share-btn');
            if (shareBtn) {
                shareBtn.addEventListener('click', async () => {
                    const res = await makeApiCall('create_share_link.php', 'POST', { post_id: post.id });
                    if (res && (res.success || res.status === 'success')) {
                        const url = `${window.location.origin}/${res.share_path}`.replace(/([^:]\/)\/+/g, '$1');
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            await navigator.clipboard.writeText(url);
                        }
                        shareBtn.classList.add('text-green-600');
                    }
                });
            }

            const reportBtn = postElement.querySelector('.profile-report-btn');
            if (reportBtn) {
                reportBtn.addEventListener('click', async () => {
                    if (!confirm('Report this post?')) return;
                    const res = await makeApiCall('report_content.php', 'POST', { post_id: post.id, reason: 'spam' });
                    if (res && (res.success || res.status === 'success' || res.message)) {
                        alert(res.message || 'Post reported.');
                    }
                });
            }

            lucide.createIcons();
            return postElement;
        }

        function renderTimelineSubtab() {
            const postsContainer = document.getElementById('timelinePosts');
            const repliesContainer = document.getElementById('timelineReplies');
            if (!postsContainer || !repliesContainer) return;

            document.querySelectorAll('.timeline-subtab').forEach((btn) => {
                const active = btn.dataset.subtab === currentTimelineSubtab;
                btn.className = active
                    ? 'timeline-subtab px-3 py-2 font-medium text-blue-600 border-b-2 border-blue-600'
                    : 'timeline-subtab px-3 py-2 font-medium text-gray-600 hover:text-gray-800';
            });

            if (currentTimelineSubtab === 'replies') {
                postsContainer.classList.add('hidden');
                repliesContainer.classList.remove('hidden');
                renderRepliesTimeline();
                return;
            }

            repliesContainer.classList.add('hidden');
            postsContainer.classList.remove('hidden');

            let visible = timelinePostsData.filter((p) => !p.is_pinned);
            if (currentTimelineSubtab === 'media') {
                visible = visible.filter((p) => Array.isArray(p.attachments) && p.attachments.length > 0);
            } else if (currentTimelineSubtab === 'reposts') {
                visible = visible.filter((p) => !!p.user_has_reposted);
            }

            if (!visible.length) {
                postsContainer.innerHTML = `<div class="text-center py-10 text-gray-500">No ${currentTimelineSubtab} yet.</div>`;
                return;
            }
            postsContainer.innerHTML = '';
            (async () => {
                for (const post of visible) {
                    postsContainer.appendChild(await createPostElement(post));
                }
            })();
        }

        function renderRepliesTimeline() {
            const repliesContainer = document.getElementById('timelineReplies');
            if (!repliesContainer) return;
            if (!timelineRepliesData.length) {
                repliesContainer.innerHTML = `<div class="text-center py-10 text-gray-500">No replies yet.</div>`;
                return;
            }
            repliesContainer.innerHTML = timelineRepliesData.map((r) => `
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <p class="text-sm text-gray-500 mb-1">${formatDate(r.created_at)} - on post #${r.post_id}</p>
                    <p class="text-gray-800 whitespace-pre-line">${r.content || ''}</p>
                    <a href="feed.php?open_comments=${r.post_id}#post-${r.post_id}" class="inline-flex mt-3 text-blue-600 hover:text-blue-800 text-sm">Open Thread</a>
                </div>
            `).join('');
        }
        
        async function loadBadges() {
            try {
                const response = await makeApiCall(`get_badges.php?user_id=${profileUserId}`);
                const badgesList = document.getElementById('badgesList');
                
                if (response && (response.success || response.status === 'success') && response.data) {
                    badgesList.innerHTML = response.data.map(badge => `
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 ${badge.color || 'bg-blue-100'} rounded-full mb-3">
                                <i data-lucide="${badge.icon || 'award'}" class="h-8 w-8 ${badge.text_color || 'text-blue-600'}"></i>
                            </div>
                            <h4 class="font-medium text-gray-900">${badge.name}</h4>
                            <p class="text-sm text-gray-500 mt-1">${badge.description}</p>
                            <p class="text-xs text-gray-400 mt-2">Earned ${formatDate(badge.earned_at)}</p>
                        </div>
                    `).join('');
                } else {
                    badgesList.innerHTML = `
                        <div class="col-span-4 text-center py-8">
                            <i data-lucide="award" class="h-12 w-12 text-gray-300 mx-auto mb-4"></i>
                            <p class="text-gray-500">No badges yet</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading badges:', error);
            }
        }
        
        function showEditButtons() {
            // Show edit cover button
            document.getElementById('editCoverBtn').classList.remove('hidden');
            
            // Show edit avatar button
            document.getElementById('editAvatarBtn').classList.remove('hidden');
            
            // Show edit buttons in about tab
            document.getElementById('editBasicInfo').classList.remove('hidden');
            document.getElementById('editEducationWork').classList.remove('hidden');
            document.getElementById('editSkills').classList.remove('hidden');
            document.getElementById('editContactInfo').classList.remove('hidden');
            
        }
        
        function setupEventListeners() {
            document.querySelectorAll('.timeline-subtab').forEach((btn) => {
                btn.addEventListener('click', () => {
                    currentTimelineSubtab = btn.dataset.subtab || 'posts';
                    renderTimelineSubtab();
                });
            });

            // Tab switching
            document.querySelectorAll('.tab-button').forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.dataset.tab;
                    
                    // Update active tab
                    document.querySelectorAll('.tab-button').forEach(t => {
                        t.classList.remove('tab-active');
                    });
                    this.classList.add('tab-active');
                    
                    // Show corresponding content
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.classList.add('hidden');
                        content.classList.remove('active');
                    });
                    
                    const contentId = `${tabId}Tab`;
                    document.getElementById(contentId).classList.remove('hidden');
                    document.getElementById(contentId).classList.add('active');
                });
            });
            
            // Avatar upload
            const avatarUpload = document.getElementById('avatarUpload');
            if (avatarUpload) {
                avatarUpload.addEventListener('change', async function() {
                    if (this.files[0]) {
                        await uploadAvatar(this.files[0]);
                    }
                });
            }
            const coverUpload = document.getElementById('coverUpload');
            const coverBtn = document.getElementById('editCoverActionBtn');
            if (coverBtn && coverUpload) {
                coverBtn.addEventListener('click', () => coverUpload.click());
                coverUpload.addEventListener('change', async function() {
                    if (this.files[0]) {
                        await uploadCover(this.files[0]);
                    }
                    this.value = '';
                });
            }
            
            // Create timeline post
            const timelinePostBtn = document.querySelector('#createPostSection button');
            if (timelinePostBtn) {
                timelinePostBtn.addEventListener('click', createTimelinePost);
            }

            const editBasicInfoBtn = document.getElementById('editBasicInfo');
            if (editBasicInfoBtn) {
                editBasicInfoBtn.addEventListener('click', () => {
                    window.location.href = 'settings.php?scope=basic#profile';
                });
            }

            const editEducationWorkBtn = document.getElementById('editEducationWork');
            if (editEducationWorkBtn) {
                editEducationWorkBtn.addEventListener('click', () => {
                    window.location.href = 'settings.php?scope=work#profile';
                });
            }

            const editSkillsBtn = document.getElementById('editSkills');
            if (editSkillsBtn) {
                editSkillsBtn.addEventListener('click', () => {
                    window.location.href = 'settings.php?scope=skills#profile';
                });
            }

            const editContactInfoBtn = document.getElementById('editContactInfo');
            if (editContactInfoBtn) {
                editContactInfoBtn.addEventListener('click', () => {
                    window.location.href = 'settings.php?scope=contact#profile';
                });
            }
        }
        
        async function uploadAvatar(file) {
            try {
                const formData = new FormData();
                formData.append('avatar', file);

                const token = localStorage.getItem('jwt_token');
                const response = await fetch(`${window.PORTAL_BASE_PREFIX || ''}api/upload_avatar.php`, {
                    method: 'POST',
                    headers: token ? { 'Authorization': `Bearer ${token}` } : {},
                    credentials: 'include',
                    body: formData
                });
                const result = await response.json();

                if (result && (result.success || result.status === 'success')) {
                    alert('Profile picture updated successfully!');
                    window.location.reload();
                } else {
                    alert((result && result.message) || 'Failed to update profile picture');
                }
            } catch (error) {
                console.error('Error uploading avatar:', error);
                alert('Error uploading profile picture');
            }
        }

        async function uploadCover(file) {
            try {
                const formData = new FormData();
                formData.append('cover', file);
                const token = localStorage.getItem('jwt_token');
                const response = await fetch(`${window.PORTAL_BASE_PREFIX || ''}api/upload_cover.php`, {
                    method: 'POST',
                    headers: token ? { 'Authorization': `Bearer ${token}` } : {},
                    credentials: 'include',
                    body: formData
                });
                const result = await response.json();
                if (result && result.success && result.cover_url) {
                    const cover = document.getElementById('profileCover');
                    if (cover) {
                        cover.style.backgroundImage = `url('${result.cover_url}')`;
                    }
                    alert('Cover photo updated successfully!');
                } else {
                    alert((result && result.message) || 'Failed to update cover photo');
                }
            } catch (error) {
                console.error('Error uploading cover:', error);
                alert('Error uploading cover photo');
            }
        }
        
        async function createTimelinePost() {
            const content = document.getElementById('timelinePostContent').value.trim();
            const allowComments = document.getElementById('timelineAllowComments').checked;
            
            if (!content) {
                alert('Please enter some content for your post');
                return;
            }
            
            const formData = new FormData();
            formData.append('content', content);
            formData.append('allow_comments', allowComments ? '1' : '0');
            
            // Add image if selected
            const imageInput = document.getElementById('timelineImage');
            if (imageInput.files[0]) {
                formData.append('image', imageInput.files[0]);
            }
            
            // Add file if selected
            const fileInput = document.getElementById('timelineFile');
            if (fileInput.files[0]) {
                formData.append('file', fileInput.files[0]);
            }
            
            try {
                const response = await makeApiCall('create_post.php', 'POST', formData);
                
                if (response && (response.success || response.status === 'success')) {
                    alert('Post created successfully!');
                    document.getElementById('timelinePostContent').value = '';
                    document.getElementById('timelineImage').value = '';
                    document.getElementById('timelineFile').value = '';
                    
                    // Reload posts
                    await loadProfilePosts();
                } else {
                    alert(response.message || 'Failed to create post');
                }
            } catch (error) {
                console.error('Error creating post:', error);
                alert('Error creating post');
            }
        }
        
        async function saveProfileSettings() {
            const email = document.getElementById('settingsEmail').value;
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            
            const updateData = {};
            
            if (email && email !== profileData.email) {
                updateData.email = email;
            }
            
            if (currentPassword && newPassword) {
                updateData.current_password = currentPassword;
                updateData.new_password = newPassword;
            }
            
            if (Object.keys(updateData).length === 0) {
                alert('No changes to save');
                return;
            }
            
            try {
                const response = await makeApiCall('update_profile.php', 'POST', updateData);
                
                if (response && (response.success || response.status === 'success')) {
                    alert('Profile settings updated successfully!');
                    
                    // Clear password fields
                    document.getElementById('currentPassword').value = '';
                    document.getElementById('newPassword').value = '';
                } else {
                    alert(response.message || 'Failed to update profile settings');
                }
            } catch (error) {
                console.error('Error saving profile settings:', error);
                alert('Error saving profile settings');
            }
        }
        
        async function sendMessage(userId) {
            window.location.href = `messages.php?user_id=${userId}`;
        }
        
        async function connect(userId) {
            try {
                const response = await makeApiCall('connect_user.php', 'POST', { user_id: userId });
                if (response && (response.success || response.status === 'success')) {
                    const btn = document.getElementById('connectBtn');
                    if (btn) {
                        const connected = !!response.connected;
                        btn.innerHTML = `<i data-lucide="user-plus" class="h-4 w-4 inline mr-2"></i>${connected ? 'Following' : 'Follow'}`;
                        btn.className = connected
                            ? 'px-4 py-2 border border-blue-300 text-blue-700 rounded-lg hover:bg-blue-50 font-medium'
                            : 'px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium';
                        lucide.createIcons();
                    }
                } else {
                    alert((response && response.message) || 'Failed to update follow state');
                }
            } catch (error) {
                console.error('Error sending connection request:', error);
            }
        }
        
        function editProfile() {
            window.location.href = 'settings.php#profile';
        }
        
        function exportData() {
            // Implement data export
            alert('Data export feature coming soon!');
        }
        
        function deactivateAccount() {
            if (confirm('Are you sure you want to deactivate your account? This action can be reversed later.')) {
                alert('Account deactivation feature coming soon!');
            }
        }
        
        function showProfileError() {
            const mainContent = document.querySelector('main');
            mainContent.innerHTML = `
                <div class="text-center py-12">
                    <i data-lucide="user-x" class="h-12 w-12 text-red-300 mx-auto mb-4"></i>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Profile Not Found</h2>
                    <p class="text-gray-500 mb-6">The profile you're looking for doesn't exist or you don't have permission to view it.</p>
                    <a href="dashboard.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-medium">
                        Back to Dashboard
                    </a>
                </div>
            `;
        }
    </script>
</body>
</html>
