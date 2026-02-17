<?php
// settings.php
session_start();
#require_once 'includes/auth_check.php';

$pageTitle = "Settings - RJIT Alumni Portal";
include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Account Settings</h1>
            <p class="text-gray-600">Manage your account preferences and security</p>
        </div>
        
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Left Navigation -->
            <div class="lg:w-1/4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 sticky top-8">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center">
                            <img src="https://via.placeholder.com/48" alt="Profile" class="h-12 w-12 rounded-full">
                            <div class="ml-4">
                                <h3 class="font-bold text-gray-900">Aryan Singh</h3>
                                <p class="text-sm text-gray-600">Student</p>
                            </div>
                        </div>
                    </div>
                    
                    <nav class="p-4">
                        <a href="#profile" class="flex items-center px-4 py-3 text-blue-600 bg-blue-50 rounded-lg mb-2">
                            <i data-lucide="user" class="h-5 w-5 mr-3"></i>
                            Profile
                        </a>
                        <a href="#account" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg mb-2">
                            <i data-lucide="settings" class="h-5 w-5 mr-3"></i>
                            Account
                        </a>
                        <a href="#privacy" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg mb-2">
                            <i data-lucide="shield" class="h-5 w-5 mr-3"></i>
                            Privacy & Security
                        </a>
                        <a href="#notifications" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg mb-2">
                            <i data-lucide="bell" class="h-5 w-5 mr-3"></i>
                            Notifications
                        </a>
                        <a href="#connections" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg mb-2">
                            <i data-lucide="users" class="h-5 w-5 mr-3"></i>
                            Connections
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Right Content -->
            <div class="lg:w-3/4">
                <!-- Profile Section -->
                <div id="profile" class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900">Profile Information</h2>
                        <p class="text-gray-600 text-sm">Update your personal details and profile picture</p>
                    </div>
                    
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row items-start md:items-center mb-8">
                            <div class="mb-4 md:mb-0 md:mr-8">
                                <img src="https://via.placeholder.com/100" alt="Profile" class="h-24 w-24 rounded-full">
                            </div>
                            <div>
                                <button class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 font-medium mr-3">
                                    Upload New Photo
                                </button>
                                <button class="text-red-600 hover:text-red-800 font-medium">
                                    Remove Photo
                                </button>
                                <p class="text-sm text-gray-500 mt-2">Recommended: Square JPG, PNG at least 400x400 pixels</p>
                            </div>
                        </div>
                        
                        <form class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                                    <input type="text" 
                                           value="Aryan" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                                    <input type="text" 
                                           value="Singh" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <input type="email" 
                                       value="0902cs231028@rjit.ac.in" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Bio</label>
                                <textarea rows="4" 
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">Computer Science student passionate about web development and machine learning.</textarea>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="button" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 mr-3">
                                    Cancel
                                </button>
                                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Account Section -->
                <div id="account" class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900">Account Settings</h2>
                        <p class="text-gray-600 text-sm">Manage your account preferences</p>
                    </div>
                    
                    <div class="p-6 space-y-6">
                        <!-- Language -->
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-semibold text-gray-900">Language</h3>
                                <p class="text-sm text-gray-600">Choose your preferred language</p>
                            </div>
                            <select class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option>English</option>
                                <option>Hindi</option>
                                <option>Spanish</option>
                            </select>
                        </div>
                        
                        <!-- Timezone -->
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-semibold text-gray-900">Timezone</h3>
                                <p class="text-sm text-gray-600">Set your local timezone</p>
                            </div>
                            <select class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option>IST (UTC+5:30)</option>
                                <option>PST (UTC-8)</option>
                                <option>EST (UTC-5)</option>
                            </select>
                        </div>
                        
                        <!-- Account Deletion -->
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="font-semibold text-gray-900 mb-2">Delete Account</h3>
                            <p class="text-sm text-gray-600 mb-4">Once you delete your account, there is no going back. Please be certain.</p>
                            <button class="text-red-600 border border-red-600 px-4 py-2 rounded-lg hover:bg-red-50">
                                Delete Account
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Privacy & Security -->
                <div id="privacy" class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900">Privacy & Security</h2>
                        <p class="text-gray-600 text-sm">Manage your privacy settings and security</p>
                    </div>
                    
                    <div class="p-6 space-y-6">
                        <!-- Password Change -->
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-4">Change Password</h3>
                            <form class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                                    <input type="password" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                                    <input type="password" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                                    <input type="password" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                                    Update Password
                                </button>
                            </form>
                        </div>
                        
                        <!-- Two-Factor Authentication -->
                        <div class="border-t border-gray-200 pt-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="font-semibold text-gray-900">Two-Factor Authentication</h3>
                                    <p class="text-sm text-gray-600">Add an extra layer of security to your account</p>
                                </div>
                                <button class="bg-green-100 text-green-800 px-4 py-2 rounded-lg font-medium">
                                    Enable
                                </button>
                            </div>
                        </div>
                        
                        <!-- Login Sessions -->
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="font-semibold text-gray-900 mb-4">Active Login Sessions</h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <i data-lucide="monitor" class="h-5 w-5 text-gray-500 mr-3"></i>
                                        <div>
                                            <p class="font-medium">Windows • Chrome</p>
                                            <p class="text-sm text-gray-600">Current session • Delhi, India</p>
                                        </div>
                                    </div>
                                    <span class="text-green-600 text-sm font-medium">Active now</span>
                                </div>
                                
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <i data-lucide="smartphone" class="h-5 w-5 text-gray-500 mr-3"></i>
                                        <div>
                                            <p class="font-medium">iPhone • Safari</p>
                                            <p class="text-sm text-gray-600">2 hours ago • Mumbai, India</p>
                                        </div>
                                    </div>
                                    <button class="text-red-600 text-sm font-medium">Revoke</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Tab navigation
    document.querySelectorAll('nav a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all links
            document.querySelectorAll('nav a').forEach(l => {
                l.classList.remove('text-blue-600', 'bg-blue-50');
                l.classList.add('text-gray-700', 'hover:bg-gray-50');
            });
            
            // Add active class to clicked link
            this.classList.remove('text-gray-700', 'hover:bg-gray-50');
            this.classList.add('text-blue-600', 'bg-blue-50');
            
            // Scroll to section
            const targetId = this.getAttribute('href').substring(1);
            document.getElementById(targetId).scrollIntoView({ behavior: 'smooth' });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>