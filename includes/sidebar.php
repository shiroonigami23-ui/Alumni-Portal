<?php
$isAdminPath = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$basePrefix = $isAdminPath ? '../' : '';
$currentPage = basename($_SERVER['PHP_SELF']);
$isAdminDashboardPage = $currentPage === 'dashboard.php' && strpos($_SERVER['REQUEST_URI'], 'admin') !== false;
?>
<!-- Sidebar Navigation -->
<style>
    .sidebar-collapsed .sidebar-label,
    .sidebar-collapsed .sidebar-meta,
    .sidebar-collapsed .sidebar-usertext,
    .sidebar-collapsed .sidebar-section-title {
        display: none !important;
    }
    .sidebar-collapsed nav {
        padding-left: 0.25rem !important;
        padding-right: 0.25rem !important;
    }
    .sidebar-collapsed nav a {
        justify-content: center;
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
        min-height: 2.75rem;
        color: #334155 !important;
    }
    .sidebar-collapsed nav a i,
    .sidebar-collapsed nav a svg {
        margin-right: 0 !important;
        display: inline-flex !important;
        opacity: 1 !important;
        visibility: visible !important;
        width: 1.25rem !important;
        height: 1.25rem !important;
        min-width: 1.25rem !important;
        min-height: 1.25rem !important;
        stroke-width: 2.25 !important;
    }
    .sidebar-collapsed .sidebar-link {
        border-radius: 0.75rem !important;
    }
    .sidebar-collapsed .flex-shrink-0 .flex.items-center {
        justify-content: center;
        width: 100%;
    }
    .sidebar-collapsed .flex-shrink-0 .ml-auto {
        margin-left: 0 !important;
    }
    .sidebar-collapsed .flex-shrink-0 {
        padding: 0.5rem !important;
    }
    .sidebar-collapsed #sidebarAvatarImage,
    .sidebar-collapsed #sidebarAvatarIcon {
        width: 1.75rem !important;
        height: 1.75rem !important;
    }

    /* Reclaim content space when desktop sidebar is collapsed */
    @media (min-width: 768px) {
        body.portal-sidebar-collapsed .md\:pl-64 {
            padding-left: 3.5rem !important;
        }
    }

    /* Keep sidebar above page content edge while collapsed */
    #desktopSidebar {
        z-index: 30;
    }

    @media (max-width: 767px) {
        body {
            padding-bottom: 5.75rem;
        }

        .mobile-nav-shell {
            background: rgba(17, 24, 39, 0.92);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-top: 1px solid rgba(71, 85, 105, 0.35);
            box-shadow: 0 -12px 30px rgba(2, 6, 23, 0.35);
        }

        .mobile-nav-link {
            color: #94a3b8;
            transition: color 0.18s ease, transform 0.18s ease;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        .mobile-nav-link.mobile-nav-active {
            color: #60a5fa;
        }

        .mobile-nav-link.mobile-nav-active .mobile-nav-pill {
            background: rgba(59, 130, 246, 0.18);
            border-color: rgba(96, 165, 250, 0.38);
        }

        .mobile-nav-pill {
            border: 1px solid transparent;
            transition: transform 0.18s ease, background-color 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
        }

        .mobile-nav-sheet {
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.98) 0%, rgba(2, 6, 23, 0.98) 100%);
            border-top: 1px solid rgba(71, 85, 105, 0.4);
            box-shadow: 0 -16px 42px rgba(2, 6, 23, 0.45);
        }

        .mobile-nav-link:active .mobile-nav-pill,
        .mobile-nav-link.is-pressed .mobile-nav-pill {
            transform: scale(0.94) translateY(1px);
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.22) inset;
        }

        .mobile-nav-link:active,
        .mobile-nav-link.is-pressed {
            color: #cbd5e1;
        }
    }
</style>
<div id="desktopSidebar" class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 md:pt-16 transition-all duration-200">
    <div class="flex-1 flex flex-col min-h-0 border-r border-gray-200 bg-white">
        <div class="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
            <nav class="flex-1 px-4 space-y-1">
                <!-- Dashboard -->
                <a href="<?php echo $basePrefix; ?>dashboard.php" class="sidebar-link group flex items-center px-3 py-3 text-sm font-medium rounded-lg <?php echo $currentPage == 'dashboard.php' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'; ?>">
                    <i data-lucide="layout-dashboard" class="h-5 w-5 mr-3"></i>
                    <span class="sidebar-label">Dashboard</span>
                </a>
                
                <!-- Feed -->
                <a href="<?php echo $basePrefix; ?>feed.php" class="sidebar-link group flex items-center px-3 py-3 text-sm font-medium rounded-lg <?php echo $currentPage == 'feed.php' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'; ?>">
                    <i data-lucide="newspaper" class="h-5 w-5 mr-3"></i>
                    <span class="sidebar-label">Feed</span>
                </a>
                
                <!-- Discovery -->
                <a href="<?php echo $basePrefix; ?>discovery.php" class="sidebar-link group flex items-center px-3 py-3 text-sm font-medium rounded-lg <?php echo $currentPage == 'discovery.php' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'; ?>">
                    <i data-lucide="search" class="h-5 w-5 mr-3"></i>
                    <span class="sidebar-label">Discovery</span>
                </a>
                
                <!-- Events -->
                <a href="<?php echo $basePrefix; ?>events.php" class="sidebar-link group flex items-center px-3 py-3 text-sm font-medium rounded-lg <?php echo $currentPage == 'events.php' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'; ?>">
                    <i data-lucide="calendar" class="h-5 w-5 mr-3"></i>
                    <span class="sidebar-label">Events</span>
                    <span id="liveEventsBadge" class="sidebar-meta ml-auto hidden">
                        <span class="live-indicator w-2 h-2 bg-red-500 rounded-full inline-block mr-1"></span>
                        <span class="text-xs text-red-600">Live</span>
                    </span>
                </a>
                
                <!-- Jobs -->
                <a href="<?php echo $basePrefix; ?>jobs.php" class="sidebar-link group flex items-center px-3 py-3 text-sm font-medium rounded-lg <?php echo $currentPage == 'jobs.php' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'; ?>">
                    <i data-lucide="briefcase" class="h-5 w-5 mr-3"></i>
                    <span class="sidebar-label">Jobs</span>
                </a>
                
                <!-- Mentorship -->
                <a href="<?php echo $basePrefix; ?>mentorship.php" class="sidebar-link group flex items-center px-3 py-3 text-sm font-medium rounded-lg <?php echo $currentPage == 'mentorship.php' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'; ?>">
                    <i data-lucide="users" class="h-5 w-5 mr-3"></i>
                    <span class="sidebar-label">Mentorship</span>
                </a>
                
                <!-- Messages -->
                <a href="<?php echo $basePrefix; ?>messages.php" class="sidebar-link group flex items-center px-3 py-3 text-sm font-medium rounded-lg <?php echo $currentPage == 'messages.php' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'; ?>">
                    <i data-lucide="message-square" class="h-5 w-5 mr-3"></i>
                    <span class="sidebar-label">Messages</span>
                    <span id="unreadMessagesBadge" class="sidebar-meta ml-auto hidden bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded-full">0</span>
                </a>
                
                <!-- Admin Dashboard (Only for admins) -->
                <div id="adminSection" class="hidden">
                    <div class="px-3 pt-6 pb-2">
                        <h3 class="sidebar-section-title text-xs font-semibold text-amber-600 uppercase tracking-wider">Admin</h3>
                    </div>
                    <a href="<?php echo $basePrefix; ?>admin/dashboard.php" class="sidebar-link group flex items-center px-3 py-3 text-sm font-medium rounded-lg <?php echo $isAdminDashboardPage ? 'bg-amber-50 text-amber-700 border-l-4 border-amber-500' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'; ?>">
                        <i data-lucide="shield" class="h-5 w-5 mr-3"></i>
                        <span class="sidebar-label">Admin Panel</span>
                    </a>
                    <a href="<?php echo $basePrefix; ?>admin/users.php" class="sidebar-link group flex items-center px-3 py-3 text-sm font-medium rounded-lg <?php echo $currentPage == 'users.php' ? 'bg-amber-50 text-amber-700 border-l-4 border-amber-500' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'; ?>">
                        <i data-lucide="users" class="h-5 w-5 mr-3"></i>
                        <span class="sidebar-label">Manage Users</span>
                    </a>
                    <a href="<?php echo $basePrefix; ?>admin/reports.php" class="sidebar-link group flex items-center px-3 py-3 text-sm font-medium rounded-lg <?php echo $currentPage == 'reports.php' ? 'bg-amber-50 text-amber-700 border-l-4 border-amber-500' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'; ?>">
                        <i data-lucide="flag" class="h-5 w-5 mr-3"></i>
                        <span class="sidebar-label">Reports</span>
                    </a>
                    <a href="<?php echo $basePrefix; ?>admin/tokens.php" class="sidebar-link group flex items-center px-3 py-3 text-sm font-medium rounded-lg <?php echo $currentPage == 'tokens.php' ? 'bg-amber-50 text-amber-700 border-l-4 border-amber-500' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'; ?>">
                        <i data-lucide="key-round" class="h-5 w-5 mr-3"></i>
                        <span class="sidebar-label">Invite Tokens</span>
                    </a>
                    <a href="<?php echo $basePrefix; ?>admin/system-settings.php" class="sidebar-link group flex items-center px-3 py-3 text-sm font-medium rounded-lg <?php echo $currentPage == 'system-settings.php' ? 'bg-amber-50 text-amber-700 border-l-4 border-amber-500' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'; ?>">
                        <i data-lucide="settings-2" class="h-5 w-5 mr-3"></i>
                        <span class="sidebar-label">System Settings</span>
                    </a>
                </div>
                
                <!-- Faculty Tools (Only for faculty) -->
                <div id="facultySection" class="hidden">
                    <div class="px-3 pt-6 pb-2">
                        <h3 class="sidebar-section-title text-xs font-semibold text-blue-600 uppercase tracking-wider">Faculty</h3>
                    </div>
                    <a href="<?php echo $basePrefix; ?>profile.php" class="sidebar-link group flex items-center px-3 py-3 text-sm font-medium rounded-lg <?php echo $currentPage == 'profile.php' ? 'bg-blue-50 text-blue-700 border-l-4 border-blue-500' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'; ?>">
                        <i data-lucide="user-round" class="h-5 w-5 mr-3"></i>
                        <span class="sidebar-label">Profile</span>
                    </a>
                </div>

                <!-- Alumni Tools (Only for alumni) -->
                <div id="alumniSection" class="hidden">
                    <div class="px-3 pt-6 pb-2">
                        <h3 class="sidebar-section-title text-xs font-semibold text-emerald-600 uppercase tracking-wider">Alumni</h3>
                    </div>
                    <a href="<?php echo $basePrefix; ?>profile.php" class="sidebar-link group flex items-center px-3 py-3 text-sm font-medium rounded-lg <?php echo $currentPage == 'profile.php' ? 'bg-emerald-50 text-emerald-700 border-l-4 border-emerald-500' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'; ?>">
                        <i data-lucide="user-round" class="h-5 w-5 mr-3"></i>
                        <span class="sidebar-label">Profile</span>
                    </a>
                </div>
            </nav>
        </div>
        
        <!-- User Status -->
        <div class="flex-shrink-0 flex border-t border-gray-200 p-4">
            <div class="flex items-center">
                <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                    <img id="sidebarAvatarImage" src="" alt="Profile" class="h-8 w-8 rounded-full object-cover hidden">
                    <i id="sidebarAvatarIcon" data-lucide="user" class="h-5 w-5 text-blue-600"></i>
                </div>
                <div class="ml-3 sidebar-usertext">
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

<!-- Mobile More Sheet -->
<div id="mobileSidebarOverlay" class="md:hidden fixed inset-0 bg-black/40 z-40 hidden"></div>
<aside id="mobileSidebarDrawer" class="mobile-nav-sheet md:hidden fixed inset-x-0 bottom-0 z-50 rounded-t-3xl transform translate-y-full transition-transform duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]">
    <div class="px-5 pt-3 pb-5">
        <div class="mx-auto mb-4 h-1.5 w-14 rounded-full bg-slate-600/70"></div>
        <div class="mb-4 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">More</p>
                <h3 class="text-lg font-semibold text-white">Quick access</h3>
            </div>
            <button id="mobileSidebarClose" type="button" class="rounded-full border border-slate-700 p-2 text-slate-300 hover:bg-slate-800" aria-label="Close more menu">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>
        <nav class="grid grid-cols-2 gap-3">
            <a href="<?php echo $basePrefix; ?>events.php" class="rounded-2xl border border-slate-800 bg-slate-900/70 px-4 py-4 text-sm font-medium text-slate-100">
                <div class="mb-2 inline-flex rounded-xl bg-slate-800 p-2 text-blue-300"><i data-lucide="calendar" class="h-4 w-4"></i></div>
                <div>Events</div>
            </a>
            <a href="<?php echo $basePrefix; ?>jobs.php" class="rounded-2xl border border-slate-800 bg-slate-900/70 px-4 py-4 text-sm font-medium text-slate-100">
                <div class="mb-2 inline-flex rounded-xl bg-slate-800 p-2 text-amber-300"><i data-lucide="briefcase" class="h-4 w-4"></i></div>
                <div>Jobs</div>
            </a>
            <a href="<?php echo $basePrefix; ?>mentorship.php" class="rounded-2xl border border-slate-800 bg-slate-900/70 px-4 py-4 text-sm font-medium text-slate-100">
                <div class="mb-2 inline-flex rounded-xl bg-slate-800 p-2 text-emerald-300"><i data-lucide="users" class="h-4 w-4"></i></div>
                <div>Mentorship</div>
            </a>
            <a href="<?php echo $basePrefix; ?>settings.php" class="rounded-2xl border border-slate-800 bg-slate-900/70 px-4 py-4 text-sm font-medium text-slate-100">
                <div class="mb-2 inline-flex rounded-xl bg-slate-800 p-2 text-slate-300"><i data-lucide="settings" class="h-4 w-4"></i></div>
                <div>Settings</div>
            </a>
            <a href="<?php echo $basePrefix; ?>profile.php" class="rounded-2xl border border-slate-800 bg-slate-900/70 px-4 py-4 text-sm font-medium text-slate-100">
                <div class="mb-2 inline-flex rounded-xl bg-slate-800 p-2 text-violet-300"><i data-lucide="user-round" class="h-4 w-4"></i></div>
                <div>Profile</div>
            </a>
            <a href="<?php echo $basePrefix; ?>admin/dashboard.php" id="mobileAdminLink" class="hidden rounded-2xl border border-amber-800/60 bg-amber-950/60 px-4 py-4 text-sm font-medium text-amber-200">
                <div class="mb-2 inline-flex rounded-xl bg-amber-900/60 p-2 text-amber-200"><i data-lucide="shield" class="h-4 w-4"></i></div>
                <div>Admin Panel</div>
            </a>
        </nav>
        <div id="mobileRoleLinks" class="mt-3 space-y-2">
            <a href="<?php echo $basePrefix; ?>profile.php" id="mobileFacultyLink" class="hidden block rounded-2xl border border-blue-800/50 bg-blue-950/50 px-4 py-3 text-sm font-medium text-blue-200">Faculty profile</a>
            <a href="<?php echo $basePrefix; ?>profile.php" id="mobileAlumniLink" class="hidden block rounded-2xl border border-emerald-800/50 bg-emerald-950/50 px-4 py-3 text-sm font-medium text-emerald-200">Alumni profile</a>
        </div>
    </div>
</aside>

<!-- Mobile Bottom Navigation -->
<div class="mobile-nav-shell md:hidden fixed bottom-0 left-0 right-0 z-40">
    <div class="mx-auto flex h-20 max-w-screen-sm items-center justify-between px-3 pb-[max(0.5rem,env(safe-area-inset-bottom))] pt-2">
        <a href="<?php echo $basePrefix; ?>dashboard.php" class="mobile-nav-link <?php echo $currentPage === 'dashboard.php' && !$isAdminPath ? 'mobile-nav-active' : ''; ?> flex min-w-0 flex-1 flex-col items-center gap-1 px-1 text-[11px] font-semibold tracking-tight">
            <span class="mobile-nav-pill inline-flex rounded-2xl px-3 py-2.5"><i data-lucide="layout-dashboard" class="h-[1.35rem] w-[1.35rem]"></i></span>
            <span>Home</span>
        </a>
        <a href="<?php echo $basePrefix; ?>feed.php" class="mobile-nav-link <?php echo $currentPage === 'feed.php' ? 'mobile-nav-active' : ''; ?> flex min-w-0 flex-1 flex-col items-center gap-1 px-1 text-[11px] font-semibold tracking-tight">
            <span class="mobile-nav-pill inline-flex rounded-2xl px-3 py-2.5"><i data-lucide="newspaper" class="h-[1.35rem] w-[1.35rem]"></i></span>
            <span>Feed</span>
        </a>
        <a href="<?php echo $basePrefix; ?>discovery.php" class="mobile-nav-link <?php echo $currentPage === 'discovery.php' ? 'mobile-nav-active' : ''; ?> flex min-w-0 flex-1 flex-col items-center gap-1 px-1 text-[11px] font-semibold tracking-tight">
            <span class="mobile-nav-pill inline-flex rounded-2xl px-3 py-2.5"><i data-lucide="search" class="h-[1.35rem] w-[1.35rem]"></i></span>
            <span>Discover</span>
        </a>
        <a href="<?php echo $basePrefix; ?>messages.php" class="mobile-nav-link <?php echo $currentPage === 'messages.php' ? 'mobile-nav-active' : ''; ?> relative flex min-w-0 flex-1 flex-col items-center gap-1 px-1 text-[11px] font-semibold tracking-tight">
            <span class="mobile-nav-pill inline-flex rounded-2xl px-3 py-2.5"><i data-lucide="message-square" class="h-[1.35rem] w-[1.35rem]"></i></span>
            <span>Messages</span>
            <span id="mobileUnreadBadge" class="absolute right-5 top-0 hidden h-4 min-w-4 rounded-full bg-red-500 px-1 text-[10px] font-semibold leading-4 text-white"></span>
        </a>
        <button id="mobileSidebarToggle" type="button" class="mobile-nav-link flex min-w-0 flex-1 flex-col items-center gap-1 px-1 text-[11px] font-semibold tracking-tight">
            <span class="mobile-nav-pill inline-flex rounded-2xl px-3 py-2.5"><i data-lucide="ellipsis" class="h-[1.35rem] w-[1.35rem]"></i></span>
            <span>More</span>
        </button>
    </div>
</div>

<script>
    window.PORTAL_BASE_PREFIX = "<?php echo $basePrefix; ?>";
    function sanitizeCachedAvatarUrl(url) {
        const value = String(url || '').trim();
        if (!value) return '';
        if (value.startsWith('data:image/')) return value;
        if (/\.php(\?|$)/i.test(value)) return value;
        const normalized = value.replace(/\\/g, '/').toLowerCase();
        if (normalized.includes('storage/profiles/') || normalized.includes('storage/covers/')) return '';
        if (!/^https?:\/\//i.test(value) && !value.startsWith('/') && !normalized.startsWith('api/asset.php')) return '';
        try {
            const resolved = new URL(value, window.location.origin);
            if (
                resolved.origin === window.location.origin &&
                (
                    resolved.pathname.toLowerCase().includes('/storage/profiles/') ||
                    resolved.pathname.toLowerCase().includes('/storage/covers/')
                )
            ) {
                return '';
            }
            return resolved.href;
        } catch (_) {
            return '';
        }
    }
    // Sidebar functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Load user data into sidebar
        const userData = localStorage.getItem('user_data');
        if (userData) {
            const user = JSON.parse(userData);
            document.getElementById('sidebarUserName').textContent = user.full_name || user.name || user.email;
            const avatar = sanitizeCachedAvatarUrl((user.profile_picture || user.profile_picture_url || '').replace(/\\/g, '/'));
            if (avatar) {
                const img = document.getElementById('sidebarAvatarImage');
                const icon = document.getElementById('sidebarAvatarIcon');
                if (img) {
                    img.onerror = function() {
                        this.onerror = null;
                        this.src = '';
                        this.classList.add('hidden');
                        if (icon) icon.classList.remove('hidden');
                    };
                    img.src = avatar;
                    img.classList.remove('hidden');
                }
                if (icon) icon.classList.add('hidden');
            } else {
                const img = document.getElementById('sidebarAvatarImage');
                const icon = document.getElementById('sidebarAvatarIcon');
                if (img) img.classList.add('hidden');
                if (icon) icon.classList.remove('hidden');
            }
            
            // Set role with badge
            let roleText = 'Member';
            let roleClass = '';
            
            switch(user.role) {
                case 'admin':
                    roleText = 'Administrator';
                    roleClass = 'text-amber-600';
                    document.getElementById('adminSection').classList.remove('hidden');
                    const mobileAdminLink = document.getElementById('mobileAdminLink');
                    if (mobileAdminLink) mobileAdminLink.classList.remove('hidden');
                    break;
                case 'faculty':
                    roleText = 'Faculty';
                    roleClass = 'text-blue-600';
                    document.getElementById('facultySection').classList.remove('hidden');
                    const mobileFacultyLink = document.getElementById('mobileFacultyLink');
                    if (mobileFacultyLink) mobileFacultyLink.classList.remove('hidden');
                    break;
                case 'alumni':
                    roleText = 'Alumni';
                    roleClass = 'text-emerald-600';
                    document.getElementById('alumniSection').classList.remove('hidden');
                    const mobileAlumniLink = document.getElementById('mobileAlumniLink');
                    if (mobileAlumniLink) mobileAlumniLink.classList.remove('hidden');
                    break;
                case 'student':
                    roleText = 'Student';
                    break;
            }
            
            document.getElementById('sidebarUserRole').textContent = roleText;
            document.getElementById('sidebarUserRole').className = `text-xs ${roleClass}`;
            
            // Add badge if admin, faculty, or alumni
            if (user.role === 'admin' || user.role === 'faculty' || user.role === 'alumni') {
                const roleBadge = document.createElement('span');
                if (user.role === 'admin') {
                    roleBadge.className = 'ml-2 px-2 py-0.5 text-xs bg-amber-100 text-amber-800 rounded-full';
                    roleBadge.textContent = 'ADMIN';
                } else if (user.role === 'faculty') {
                    roleBadge.className = 'ml-2 px-2 py-0.5 text-xs bg-blue-100 text-blue-800 rounded-full';
                    roleBadge.textContent = 'FACULTY';
                } else {
                    roleBadge.className = 'ml-2 px-2 py-0.5 text-xs bg-emerald-100 text-emerald-800 rounded-full';
                    roleBadge.textContent = 'ALUMNI';
                }
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
        refreshSidebarUser();

        // Mobile sheet behavior
        const openBtn = document.getElementById('mobileSidebarToggle');
        const closeBtn = document.getElementById('mobileSidebarClose');
        const overlay = document.getElementById('mobileSidebarOverlay');
        const drawer = document.getElementById('mobileSidebarDrawer');

        function openDrawer() {
            if (!overlay || !drawer) return;
            overlay.classList.remove('hidden');
            drawer.classList.remove('translate-y-full');
            document.body.classList.add('overflow-hidden');
        }

        function closeDrawer() {
            if (!overlay || !drawer) return;
            overlay.classList.add('hidden');
            drawer.classList.add('translate-y-full');
            document.body.classList.remove('overflow-hidden');
        }

        if (openBtn) openBtn.addEventListener('click', openDrawer);
        if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
        if (overlay) overlay.addEventListener('click', closeDrawer);
        if (drawer) {
            drawer.querySelectorAll('a').forEach((link) => {
                link.addEventListener('click', closeDrawer);
            });
        }

        document.querySelectorAll('.mobile-nav-link').forEach((link) => {
            link.addEventListener('touchstart', () => link.classList.add('is-pressed'), { passive: true });
            ['touchend', 'touchcancel', 'mouseleave', 'blur'].forEach((eventName) => {
                link.addEventListener(eventName, () => link.classList.remove('is-pressed'));
            });
        });
    });

    async function refreshSidebarUser() {
        try {
            const response = await makeApiCall('me.php');
            if (!response || !response.success || !response.data) return;
            const user = response.data;
            localStorage.setItem('user_data', JSON.stringify(user));
            document.getElementById('sidebarUserName').textContent = user.full_name || user.name || user.email;
            const avatar = sanitizeCachedAvatarUrl((user.profile_picture || user.profile_picture_url || '').replace(/\\/g, '/'));
            const img = document.getElementById('sidebarAvatarImage');
            const icon = document.getElementById('sidebarAvatarIcon');
            if (avatar) {
                if (img) {
                    img.onerror = function() {
                        this.onerror = null;
                        this.src = '';
                        this.classList.add('hidden');
                        if (icon) icon.classList.remove('hidden');
                    };
                    img.src = avatar;
                    img.classList.remove('hidden');
                }
                if (icon) icon.classList.add('hidden');
            } else {
                if (img) img.classList.add('hidden');
                if (icon) icon.classList.remove('hidden');
            }
        } catch (e) {
            console.error('Unable to refresh sidebar user', e);
        }
    }
    
    function toggleSidebar() {
        const sidebar = document.getElementById('desktopSidebar');
        const icon = document.getElementById('sidebarToggleIcon');
        if (!sidebar || !icon) return;
        
        if (sidebar.classList.contains('md:w-64')) {
            sidebar.classList.remove('md:w-64');
            sidebar.classList.add('md:w-14');
            sidebar.classList.add('sidebar-collapsed');
            icon.setAttribute('data-lucide', 'chevron-right');
            localStorage.setItem('portal_sidebar_collapsed', '1');
            document.body.classList.add('portal-sidebar-collapsed');
        } else {
            sidebar.classList.remove('md:w-14');
            sidebar.classList.add('md:w-64');
            sidebar.classList.remove('sidebar-collapsed');
            icon.setAttribute('data-lucide', 'chevron-left');
            localStorage.setItem('portal_sidebar_collapsed', '0');
            document.body.classList.remove('portal-sidebar-collapsed');
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

    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('desktopSidebar');
        const icon = document.getElementById('sidebarToggleIcon');
        const collapsed = localStorage.getItem('portal_sidebar_collapsed') === '1';
        if (sidebar && icon && collapsed) {
            sidebar.classList.remove('md:w-64');
            sidebar.classList.add('md:w-14', 'sidebar-collapsed');
            icon.setAttribute('data-lucide', 'chevron-right');
            document.body.classList.add('portal-sidebar-collapsed');
            lucide.createIcons();
        } else {
            document.body.classList.remove('portal-sidebar-collapsed');
        }
    });
</script>
