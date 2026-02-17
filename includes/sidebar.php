<!-- Sidebar Navigation -->
<div class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 md:pt-16">
    <div class="flex-1 flex flex-col min-h-0 border-r border-gray-200 bg-white">
        <div class="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
            <nav class="flex-1 px-4 space-y-1">
                <!-- Dashboard -->
                <a href="dashboard.php" class="sidebar-link group flex items-center px-3 py-3 text-sm font-medium rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'; ?>">
                    <i data-lucide="layout-dashboard" class="h-5 w-5 mr-3"></i>
                    Dashboard
                </a>
                
                <!-- Feed -->
                <a href="feed.php" class="sidebar-link group flex items-center px-3 py-3 text-sm font-medium rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'feed.php' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'; ?>">
                    <i data-lucide="newspaper" class="h-5 w-5 mr-3"></i>
                    Feed
                </a>
                
                <!-- Discovery -->
                <a href="discovery.php" class="sidebar-link group flex items-center px-3 py-3 text-sm font-medium rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'discovery.php' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'; ?>">
                    <i data-lucide="search" class="h-5 w-5 mr-3"></i>
                    Discovery
                </a>
                
                <!-- Events -->
                <a href="events.php" class="sidebar-link group flex items-center px-3 py-3 text-sm font-medium rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'events.php' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'; ?>">
                    <i data-lucide="calendar" class="h-5 w-5 mr-3"></i>
                    Events
                    <span id="liveEventsBadge" class="ml-auto hidden">
                        <span class="live-indicator w-2 h-2 bg-red-500 rounded-full inline-block mr-1"></span>
                        <span class="text-xs text-red-600">Live</span>
                    </span>
                </a>
                
                <!-- Jobs -->
                <a href="jobs.php" class="sidebar-link group flex items-center px-3 py-3 text-sm font-medium rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'jobs.php' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'; ?>">
                    <i data-lucide="briefcase" class="h-5 w-5 mr-3"></i>
                    Jobs
                </a>
                
                <!-- Mentorship -->
                <a href="mentorship.php" class="sidebar-link group flex items-center px-3 py-3 text-sm font-medium rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'mentorship.php' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'; ?>">
                    <i data-lucide="users" class="h-5 w-5 mr-3"></i>
                    Mentorship
                </a>
                
                <!-- Messages -->
                <a href="messages.php" class="sidebar-link group flex items-center px-3 py-3 text-sm font-medium rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'; ?>">
                    <i data-lucide="message-square" class="h-5 w-5 mr-3"></i>
                    Messages
                    <span id="unreadMessagesBadge" class="ml-auto hidden bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded-full">0</span>
                </a>
                
                <!-- Admin Dashboard (Only for admins) -->
                <div id="adminSection" class="hidden">
                    <div class="px-3 pt-6 pb-2">
                        <h3 class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Admin</h3>
                    </div>
                    <a href="admin/dashboard.php" class="sidebar-link group flex items-center px-3 py-3 text-sm font-medium rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' && strpos($_SERVER['REQUEST_URI'], 'admin') !== false ? 'bg-amber-50 text-amber-700 border-l-4 border-amber-500' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'; ?>">
                        <i data-lucide="shield" class="h-5 w-5 mr-3"></i>
                        Admin Panel
                    </a>
                </div>
                
                <!-- Faculty Tools (Only for faculty) -->
                <div id="facultySection" class="hidden">
                    <div class="px-3 pt-6 pb-2">
                        <h3 class="text-xs font-semibold text-blue-600 uppercase tracking-wider">Faculty</h3>
                    </div>
                    <a href="faculty/tools.php" class="sidebar-link group flex items-center px-3 py-3 text-sm font-medium rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'tools.php' && strpos($_SERVER['REQUEST_URI'], 'faculty') !== false ? 'bg-blue-50 text-blue-700 border-l-4 border-blue-500' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'; ?>">
                        <i data-lucide="clipboard-list" class="h-5 w-5 mr-3"></i>
                        Faculty Tools
                    </a>
                </div>
            </nav>
        </div>
        
        <!-- User Status -->
        <div class="flex-shrink-0 flex border-t border-gray-200 p-4">
            <div class="flex items-center">
                <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                    <i data-lucide="user" class="h-5 w-5 text-blue-600"></i>
                </div>
                <div class="ml-3">
                    <p id="sidebarUserName" class="text-sm font-medium text-gray-700">Loading...</p>
                    <p id="sidebarUserRole" class="text-xs text-gray-500">Member</p>
                </div>
                <button onclick="toggleSidebar()" class="ml-auto p-1 rounded-md hover:bg-gray-100">
                    <i data-lucide="chevron-left" id="sidebarToggleIcon" class="h-5 w-5 text-gray-400"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Sidebar (Bottom Navigation) -->
<div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200">
    <div class="flex justify-around items-center h-16">
        <a href="dashboard.php" class="flex flex-col items-center justify-center p-2">
            <i data-lucide="layout-dashboard" class="h-5 w-5"></i>
            <span class="text-xs mt-1">Home</span>
        </a>
        <a href="feed.php" class="flex flex-col items-center justify-center p-2">
            <i data-lucide="newspaper" class="h-5 w-5"></i>
            <span class="text-xs mt-1">Feed</span>
        </a>
        <a href="discovery.php" class="flex flex-col items-center justify-center p-2">
            <i data-lucide="search" class="h-5 w-5"></i>
            <span class="text-xs mt-1">Discover</span>
        </a>
        <a href="messages.php" class="flex flex-col items-center justify-center p-2 relative">
            <i data-lucide="message-square" class="h-5 w-5"></i>
            <span class="text-xs mt-1">Messages</span>
            <span id="mobileUnreadBadge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center hidden">0</span>
        </a>
        <a href="profile.php" class="flex flex-col items-center justify-center p-2">
            <i data-lucide="user" class="h-5 w-5"></i>
            <span class="text-xs mt-1">Profile</span>
        </a>
    </div>
</div>

<script>
    // Sidebar functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Load user data into sidebar
        const userData = localStorage.getItem('user_data');
        if (userData) {
            const user = JSON.parse(userData);
            document.getElementById('sidebarUserName').textContent = user.name || user.email;
            
            // Set role with badge
            let roleText = 'Member';
            let roleClass = '';
            
            switch(user.role) {
                case 'admin':
                    roleText = 'Administrator';
                    roleClass = 'text-amber-600';
                    document.getElementById('adminSection').classList.remove('hidden');
                    break;
                case 'faculty':
                    roleText = 'Faculty';
                    roleClass = 'text-blue-600';
                    document.getElementById('facultySection').classList.remove('hidden');
                    break;
                case 'alumni':
                    roleText = 'Alumni';
                    break;
                case 'student':
                    roleText = 'Student';
                    break;
            }
            
            document.getElementById('sidebarUserRole').textContent = roleText;
            document.getElementById('sidebarUserRole').className = `text-xs ${roleClass}`;
            
            // Add badge if admin or faculty
            if (user.role === 'admin' || user.role === 'faculty') {
                const roleBadge = document.createElement('span');
                roleBadge.className = user.role === 'admin' ? 'ml-2 px-2 py-0.5 text-xs bg-amber-100 text-amber-800 rounded-full' : 'ml-2 px-2 py-0.5 text-xs bg-blue-100 text-blue-800 rounded-full';
                roleBadge.textContent = user.role === 'admin' ? 'ADMIN' : 'FACULTY';
                document.getElementById('sidebarUserRole').parentNode.appendChild(roleBadge);
            }
        }
        
        // Initialize sidebar links
        const sidebarLinks = document.querySelectorAll('.sidebar-link');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                // Update active state
                sidebarLinks.forEach(l => l.classList.remove('bg-blue-50', 'text-blue-700'));
                this.classList.add('bg-blue-50', 'text-blue-700');
            });
        });
        
        // Load unread messages count
        loadUnreadCounts();
    });
    
    function toggleSidebar() {
        const sidebar = document.querySelector('[class*="md:w-64"]');
        const icon = document.getElementById('sidebarToggleIcon');
        
        if (sidebar.classList.contains('md:w-64')) {
            sidebar.classList.remove('md:w-64');
            sidebar.classList.add('md:w-16');
            icon.setAttribute('data-lucide', 'chevron-right');
        } else {
            sidebar.classList.remove('md:w-16');
            sidebar.classList.add('md:w-64');
            icon.setAttribute('data-lucide', 'chevron-left');
        }
        
        lucide.createIcons();
    }
    
    async function loadUnreadCounts() {
        try {
            // Load unread messages count
            const messagesResponse = await makeApiCall('get_inbox.php');
            if (messagesResponse && messagesResponse.success) {
                const unreadCount = messagesResponse.data.filter(msg => !msg.is_read).length;
                if (unreadCount > 0) {
                    document.getElementById('unreadMessagesBadge').textContent = unreadCount;
                    document.getElementById('unreadMessagesBadge').classList.remove('hidden');
                    document.getElementById('mobileUnreadBadge').textContent = unreadCount;
                    document.getElementById('mobileUnreadBadge').classList.remove('hidden');
                }
            }
        } catch (error) {
            console.error('Error loading unread counts:', error);
        }
    }
</script>