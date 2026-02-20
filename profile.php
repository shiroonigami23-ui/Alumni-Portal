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
    <title>Profile - RJIT Alumni Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Roboto+Slab:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/variety-ui.css">
    <script src="includes/auth-check.js"></script>
    <script src="assets/js/variety-ui.js" defer></script>
    
    <style>
        .cover-image {
            height: 300px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        <div class="cover-image relative">
            <div class="absolute inset-0 bg-black bg-opacity-30"></div>
            <div class="absolute bottom-6 left-8 text-white">
                <h1 class="text-3xl font-bold" id="profileName">Loading...</h1>
                <p class="text-lg opacity-90" id="profileTitle">RJIT Community</p>
            </div>
            
            <!-- Edit Cover Button (only for own profile) -->
            <div id="editCoverBtn" class="absolute bottom-6 right-8 hidden">
                <button class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg flex items-center">
                    <i data-lucide="camera" class="h-4 w-4 mr-2"></i>
                    Edit Cover
                </button>
            </div>
        </div>

        <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Profile Header -->
            <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-8">
                <div class="flex items-end">
                    <!-- Profile Avatar -->
                    <div class="relative">
                        <img id="profileAvatar" 
                             src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='150' height='150' viewBox='0 0 150 150'%3E%3Crect width='150' height='150' fill='%23dbeafe'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='20' fill='%233b82f6'%3EU%3C/text%3E%3C/svg%3E" 
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
                    <button data-tab="settings" 
                            class="tab-button py-3 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hidden"
                            id="settingsTab">
                        <i data-lucide="settings" class="h-4 w-4 inline mr-2"></i>
                        Settings
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
                                     src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='48' height='48' viewBox='0 0 48 48'%3E%3Crect width='48' height='48' fill='%23dbeafe'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='14' fill='%233b82f6'%3EU%3C/text%3E%3C/svg%3E" 
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
                                            <div class="flex items-center">
                                                <input type="checkbox" id="timelinePinPost" class="h-4 w-4 text-amber-600 rounded">
                                                <label for="timelinePinPost" class="ml-2 text-sm text-gray-600">Pin to profile</label>
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
                    <div id="timelinePosts" class="space-y-6">
                        <!-- Posts will be loaded here -->
                        <div class="text-center py-12">
                            <i data-lucide="loader" class="h-8 w-8 animate-spin text-blue-600 mx-auto mb-4"></i>
                            <p class="text-gray-500">Loading posts...</p>
                        </div>
                    </div>
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
                                <h3 class="font-semibold text-gray-900 mb-4">Contact Information</h3>
                                
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

                            <!-- Privacy Settings (only for own profile) -->
                            <div id="privacySettings" class="bg-white rounded-xl shadow-sm p-6 hidden">
                                <h3 class="font-semibold text-gray-900 mb-4">Privacy Settings</h3>
                                
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-medium text-gray-900">Profile Visibility</p>
                                            <p class="text-sm text-gray-500">Who can see your profile</p>
                                        </div>
                                        <button id="togglePrivacy" class="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-200">
                                            <span id="privacyToggle" class="inline-block h-4 w-4 translate-x-1 transform rounded-full bg-white transition"></span>
                                        </button>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-medium text-gray-900">Message Requests</p>
                                            <p class="text-sm text-gray-500">Who can send you messages</p>
                                        </div>
                                        <select id="messagePrivacy" class="px-3 py-1 border border-gray-300 rounded-lg text-sm">
                                            <option value="everyone">Everyone</option>
                                            <option value="connections">Connections Only</option>
                                            <option value="none">No One</option>
                                        </select>
                                    </div>
                                    
                                    <div class="pt-4 border-t border-gray-100">
                                        <button onclick="savePrivacySettings()" 
                                                class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 font-medium">
                                            Save Privacy Settings
                                        </button>
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

                <!-- Settings Tab (only for own profile) -->
                <div id="settingsTabContent" class="tab-content hidden">
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6">Profile Settings</h3>
                        
                        <div class="space-y-6">
                            <!-- Account Settings -->
                            <div>
                                <h4 class="font-medium text-gray-900 mb-4">Account Settings</h4>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                        <input type="email" 
                                               id="settingsEmail" 
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Change Password</label>
                                        <input type="password" 
                                               id="currentPassword" 
                                               placeholder="Current password"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent mb-2">
                                        <input type="password" 
                                               id="newPassword" 
                                               placeholder="New password"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Notification Settings -->
                            <div>
                                <h4 class="font-medium text-gray-900 mb-4">Notification Settings</h4>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="notifyPosts" checked class="h-4 w-4 text-blue-600 rounded">
                                        <label for="notifyPosts" class="ml-2 text-gray-700">New posts from connections</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="notifyMessages" checked class="h-4 w-4 text-blue-600 rounded">
                                        <label for="notifyMessages" class="ml-2 text-gray-700">New messages</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="notifyEvents" checked class="h-4 w-4 text-blue-600 rounded">
                                        <label for="notifyEvents" class="ml-2 text-gray-700">Upcoming events</label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Danger Zone -->
                            <div class="pt-6 border-t border-gray-200">
                                <h4 class="font-medium text-red-700 mb-4">Danger Zone</h4>
                                <div class="space-y-3">
                                    <button onclick="exportData()" 
                                            class="w-full text-left px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-700">
                                        <div class="font-medium">Export My Data</div>
                                        <div class="text-sm text-gray-500">Download all your data from the portal</div>
                                    </button>
                                    
                                    <button onclick="deactivateAccount()" 
                                            class="w-full text-left px-4 py-3 border border-red-300 rounded-lg hover:bg-red-50 text-red-700">
                                        <div class="font-medium">Deactivate Account</div>
                                        <div class="text-sm text-red-500">Temporarily disable your account</div>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Save Button -->
                            <div class="pt-6 border-t border-gray-200">
                                <button onclick="saveProfileSettings()" 
                                        class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-medium">
                                    Save Changes
                                </button>
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
                    currentUserId = user.id;
                    
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
                const response = await makeApiCall(`get_user_profile.php?user_id=${profileUserId}`);
                
                if (response && (response.success || response.status === 'success')) {
                    profileData = response.data;
                    renderProfile(profileData);
                    await loadProfilePosts();
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
                        <button onclick="connect(${data.id})" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                            <i data-lucide="user-plus" class="h-4 w-4 inline mr-2"></i>
                            Connect
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
                const response = await makeApiCall(`get_feed.php?user_id=${profileUserId}`);
                const timelinePosts = document.getElementById('timelinePosts');
                
                if (response && (response.success || response.status === 'success') && response.data) {
                    const posts = response.data;
                    
                    // Check for pinned posts
                    const pinnedPosts = posts.filter(post => post.is_pinned);
                    if (pinnedPosts.length > 0) {
                        await loadPinnedPosts(pinnedPosts);
                    }
                    
                    // Show create post section for own profile
                    if (isOwnProfile) {
                        document.getElementById('createPostSection').classList.remove('hidden');
                    }
                    
                    // Filter out pinned posts from regular timeline
                    const regularPosts = posts.filter(post => !post.is_pinned);
                    
                    if (regularPosts.length === 0) {
                        timelinePosts.innerHTML = `
                            <div class="text-center py-12">
                                <i data-lucide="newspaper" class="h-12 w-12 text-gray-300 mx-auto mb-4"></i>
                                <p class="text-gray-500">No posts to show yet</p>
                                ${isOwnProfile ? 
                                    '<p class="text-gray-400 text-sm mt-2">Share your first post with the community!</p>' : 
                                    ''}
                            </div>
                        `;
                    } else {
                        timelinePosts.innerHTML = '';
                        for (const post of regularPosts) {
                            const postElement = await createPostElement(post);
                            timelinePosts.appendChild(postElement);
                        }
                    }
                } else {
                    timelinePosts.innerHTML = `
                        <div class="text-center py-12">
                            <i data-lucide="newspaper" class="h-12 w-12 text-gray-300 mx-auto mb-4"></i>
                            <p class="text-gray-500">No posts to show yet</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading profile posts:', error);
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
            // Similar to feed.php createPostElement function
            // You can reuse the same logic or create a simplified version
            const postElement = document.createElement('div');
            postElement.className = `bg-white rounded-xl shadow-sm p-6 ${isPinned ? 'border-l-4 border-amber-500 bg-amber-50' : ''}`;
            
            // Fetch content from file
            let content = '';
            if (post.content_file_path) {
                try {
                    content = await fetchTextContent(post.content_file_path);
                } catch (error) {
                    content = 'Content not available';
                }
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
                    <span class="flex items-center mr-4">
                        <i data-lucide="heart" class="h-4 w-4 mr-1"></i>
                        ${post.likes_count || 0}
                    </span>
                    <span class="flex items-center">
                        <i data-lucide="message-square" class="h-4 w-4 mr-1"></i>
                        ${post.comments_count || 0}
                    </span>
                </div>
            `;
            
            lucide.createIcons();
            return postElement;
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
            
            // Show settings tab
            document.getElementById('settingsTab').classList.remove('hidden');
            
            // Show privacy settings
            document.getElementById('privacySettings').classList.remove('hidden');
            
            // Setup privacy toggle
            setupPrivacyToggle();
        }
        
        function setupPrivacyToggle() {
            const toggleBtn = document.getElementById('togglePrivacy');
            const toggleCircle = document.getElementById('privacyToggle');
            
            if (profileData && profileData.is_private) {
                toggleBtn.classList.add('bg-green-600');
                toggleBtn.classList.remove('bg-gray-200');
                toggleCircle.classList.add('translate-x-6');
            }
            
            toggleBtn.addEventListener('click', function() {
                const isPrivate = toggleBtn.classList.contains('bg-green-600');
                
                if (isPrivate) {
                    // Switch to public
                    toggleBtn.classList.remove('bg-green-600');
                    toggleBtn.classList.add('bg-gray-200');
                    toggleCircle.classList.remove('translate-x-6');
                } else {
                    // Switch to private
                    toggleBtn.classList.add('bg-green-600');
                    toggleBtn.classList.remove('bg-gray-200');
                    toggleCircle.classList.add('translate-x-6');
                }
            });
        }
        
        function setupEventListeners() {
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
            
            // Create timeline post
            const timelinePostBtn = document.querySelector('#createPostSection button');
            if (timelinePostBtn) {
                timelinePostBtn.addEventListener('click', createTimelinePost);
            }
        }
        
                async function uploadAvatar(file) {
            try {
                const formData = new FormData();
                formData.append('avatar', file);
                
                const token = localStorage.getItem('jwt_token');
                const response = await fetch('api/upload_avatar.php', {
                    method: 'POST',
                    headers: { 'Authorization': 'Bearer ' + token },
                    body: formData
                });
                const result = await response.json();
                
                if (result.status === 'success') {
                    alert('Profile picture updated successfully!');
                    location.reload();
                } else {
                    alert(result.message || 'Failed to update profile picture');
                }
            } catch (error) {
                console.error('Error uploading avatar:', error);
                alert('Error uploading profile picture');
            }
        } else {
                    alert(response.message || 'Failed to update profile picture');
                }
            } catch (error) {
                console.error('Error uploading avatar:', error);
                alert('Error uploading profile picture');
            }
        }
        
        async function createTimelinePost() {
            const content = document.getElementById('timelinePostContent').value.trim();
            const allowComments = document.getElementById('timelineAllowComments').checked;
            const pinPost = document.getElementById('timelinePinPost').checked;
            
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
                    document.getElementById('timelinePinPost').checked = false;
                    
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
        
        async function savePrivacySettings() {
            const isPrivate = document.getElementById('togglePrivacy').classList.contains('bg-green-600');
            const messagePrivacy = document.getElementById('messagePrivacy').value;
            
            try {
                const response = await makeApiCall('update_privacy.php', 'POST', {
                    is_private: isPrivate ? 1 : 0,
                    message_privacy: messagePrivacy
                });
                
                if (response && (response.success || response.status === 'success')) {
                    alert('Privacy settings updated successfully!');
                } else {
                    alert(response.message || 'Failed to update privacy settings');
                }
            } catch (error) {
                console.error('Error saving privacy settings:', error);
                alert('Error saving privacy settings');
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
                // You would need to implement a connection API endpoint
                alert('Connection request sent!');
            } catch (error) {
                console.error('Error sending connection request:', error);
            }
        }
        
        function editProfile() {
            window.location.href = 'edit-profile.php';
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
