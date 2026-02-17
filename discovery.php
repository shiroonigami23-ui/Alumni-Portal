<?php
// Check authentication

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discover Alumni - RJIT Alumni Portal</title>

    <style>
        .alumni-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .alumni-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .filter-section {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="md:pl-64">
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Discover Alumni</h1>
                <p class="text-gray-600 mt-1">Connect with RJITians across generations and industries</p>
            </div>

            <!-- Search and Filters -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                <!-- Search Bar -->
                <div class="mb-6">
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-4 top-3.5 h-5 w-5 text-gray-400"></i>
                        <input type="text"
                            id="searchAlumni"
                            placeholder="Search by name, company, position, or skills..."
                            class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                </div>

                <!-- Filter Row -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Graduation Year -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Graduation Year</label>
                        <select id="filterYear" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Years</option>
                            <?php
                            $currentYear = date('Y');
                            for ($i = $currentYear; $i >= 1990; $i--) {
                                echo "<option value=\"$i\">$i</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Branch -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Branch</label>
                        <select id="filterBranch" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Branches</option>
                            <option value="CSE">Computer Science & Engineering</option>
                            <option value="IT">Information Technology</option>
                            <option value="ECE">Electronics & Communication</option>
                            <option value="EE">Electrical Engineering</option>
                            <option value="ME">Mechanical Engineering</option>
                            <option value="CE">Civil Engineering</option>
                        </select>
                    </div>

                    <!-- Company -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Company</label>
                        <input type="text"
                            id="filterCompany"
                            placeholder="Google, Microsoft, etc."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Role -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">User Type</label>
                        <select id="filterRole" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Types</option>
                            <option value="alumni">Alumni Only</option>
                            <option value="faculty">Faculty Only</option>
                            <option value="student">Students Only</option>
                        </select>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between items-center mt-6 pt-6 border-t border-gray-200">
                    <div class="text-sm text-gray-500" id="resultCount">
                        Loading alumni...
                    </div>
                    <div class="flex space-x-3">
                        <button onclick="resetFilters()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
                            Reset Filters
                        </button>
                        <button onclick="applyFilters()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                            Apply Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Results Grid -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-semibold text-gray-900">Alumni Directory</h2>
                    <div class="flex items-center space-x-4">
                        <select id="sortBy" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" onchange="loadAlumni()">
                            <option value="name_asc">Name: A-Z</option>
                            <option value="name_desc">Name: Z-A</option>
                            <option value="year_desc">Year: Newest First</option>
                            <option value="year_asc">Year: Oldest First</option>
                        </select>
                        <div class="flex items-center space-x-2">
                            <button id="gridView" class="p-2 rounded-lg bg-blue-100 text-blue-600">
                                <i data-lucide="grid" class="h-5 w-5"></i>
                            </button>
                            <button id="listView" class="p-2 rounded-lg hover:bg-gray-100 text-gray-600">
                                <i data-lucide="list" class="h-5 w-5"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div id="loadingState" class="text-center py-12">
                    <i data-lucide="loader" class="h-8 w-8 animate-spin text-blue-600 mx-auto mb-4"></i>
                    <p class="text-gray-500">Loading alumni directory...</p>
                </div>

                <!-- Grid View -->
                <div id="gridResults" class="hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Alumni cards will be loaded here -->
                </div>

                <!-- List View -->
                <div id="listResults" class="hidden space-y-4">
                    <!-- Alumni list items will be loaded here -->
                </div>

                <!-- Empty State -->
                <div id="emptyState" class="hidden text-center py-12">
                    <i data-lucide="users" class="h-12 w-12 text-gray-300 mx-auto mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No alumni found</h3>
                    <p class="text-gray-500 mb-6">Try adjusting your search or filters</p>
                    <button onclick="resetFilters()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-medium">
                        Reset Filters
                    </button>
                </div>

                <!-- Error State -->
                <div id="errorState" class="hidden text-center py-12">
                    <i data-lucide="alert-circle" class="h-12 w-12 text-red-300 mx-auto mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Unable to load alumni</h3>
                    <p class="text-gray-500 mb-6">Please try again later</p>
                    <button onclick="loadAlumni()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-medium">
                        Try Again
                    </button>
                </div>

                <!-- Pagination -->
                <div id="pagination" class="mt-8 flex justify-center hidden">
                    <nav class="flex items-center space-x-2">
                        <button id="prevPage" class="p-2 rounded-lg border border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i data-lucide="chevron-left" class="h-4 w-4"></i>
                        </button>
                        <div class="flex items-center space-x-1" id="pageNumbers"></div>
                        <button id="nextPage" class="p-2 rounded-lg border border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i data-lucide="chevron-right" class="h-4 w-4"></i>
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Featured Companies -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-6">Top Companies</h2>
                <div id="topCompanies" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <!-- Companies will be loaded here -->
                    <div class="text-center">
                        <div class="h-16 w-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-2">
                            <i data-lucide="briefcase" class="h-8 w-8 text-blue-600"></i>
                        </div>
                        <p class="text-sm text-gray-600">Loading...</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Message Modal -->
    <div id="messageModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Send Message</h3>
                    <button onclick="closeMessageModal()" class="p-2 rounded-full hover:bg-gray-100">
                        <i data-lucide="x" class="h-5 w-5 text-gray-500"></i>
                    </button>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">To:</label>
                    <div id="recipientInfo" class="flex items-center p-3 bg-gray-50 rounded-lg">
                        <i data-lucide="user" class="h-5 w-5 text-gray-400 mr-3"></i>
                        <span id="recipientName" class="font-medium">Loading...</span>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                    <textarea id="messageContent"
                        rows="4"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                        placeholder="Type your message here..."></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <button onclick="closeMessageModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
                        Cancel
                    </button>
                    <button onclick="sendMessage()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                        Send Message
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // State variables
        let currentView = 'grid';
        let currentPage = 1;
        let totalPages = 1;
        let currentRecipientId = null;
        let currentFilters = {
            search: '',
            year: '',
            branch: '',
            company: '',
            role: '',
            sort: 'name_asc'
        };

        // Load alumni on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize from URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            currentFilters.search = urlParams.get('search') || '';
            currentFilters.year = urlParams.get('year') || '';
            currentFilters.branch = urlParams.get('branch') || '';
            currentFilters.company = urlParams.get('company') || '';
            currentFilters.role = urlParams.get('role') || '';
            currentFilters.sort = urlParams.get('sort') || 'name_asc';

            // Update form inputs
            if (currentFilters.search) {
                document.getElementById('searchAlumni').value = currentFilters.search;
            }
            if (currentFilters.year) {
                document.getElementById('filterYear').value = currentFilters.year;
            }
            if (currentFilters.branch) {
                document.getElementById('filterBranch').value = currentFilters.branch;
            }
            if (currentFilters.company) {
                document.getElementById('filterCompany').value = currentFilters.company;
            }
            if (currentFilters.role) {
                document.getElementById('filterRole').value = currentFilters.role;
            }
            if (currentFilters.sort) {
                document.getElementById('sortBy').value = currentFilters.sort;
            }

            // Setup event listeners
            setupEventListeners();

            // Load initial data
            loadAlumni();
            loadTopCompanies();
        });

        function setupEventListeners() {
            // Search input
            const searchInput = document.getElementById('searchAlumni');
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentFilters.search = this.value;
                    currentPage = 1;
                    loadAlumni();
                }, 500);
            });

            // View toggle
            document.getElementById('gridView').addEventListener('click', function() {
                setView('grid');
            });

            document.getElementById('listView').addEventListener('click', function() {
                setView('list');
            });

            // Sort change
            document.getElementById('sortBy').addEventListener('change', function() {
                currentFilters.sort = this.value;
                loadAlumni();
            });

            // Filter change
            document.getElementById('filterYear').addEventListener('change', function() {
                currentFilters.year = this.value;
                currentPage = 1;
                loadAlumni();
            });

            document.getElementById('filterBranch').addEventListener('change', function() {
                currentFilters.branch = this.value;
                currentPage = 1;
                loadAlumni();
            });

            document.getElementById('filterRole').addEventListener('change', function() {
                currentFilters.role = this.value;
                currentPage = 1;
                loadAlumni();
            });

            // Company filter
            const companyInput = document.getElementById('filterCompany');
            let companyTimeout;
            companyInput.addEventListener('input', function() {
                clearTimeout(companyTimeout);
                companyTimeout = setTimeout(() => {
                    currentFilters.company = this.value;
                    currentPage = 1;
                    loadAlumni();
                }, 500);
            });

            // Pagination
            document.getElementById('prevPage').addEventListener('click', function() {
                if (currentPage > 1) {
                    currentPage--;
                    loadAlumni();
                }
            });

            document.getElementById('nextPage').addEventListener('click', function() {
                if (currentPage < totalPages) {
                    currentPage++;
                    loadAlumni();
                }
            });
        }

        function setView(view) {
            currentView = view;

            // Update button styles
            const gridBtn = document.getElementById('gridView');
            const listBtn = document.getElementById('listView');

            if (view === 'grid') {
                gridBtn.className = 'p-2 rounded-lg bg-blue-100 text-blue-600';
                listBtn.className = 'p-2 rounded-lg hover:bg-gray-100 text-gray-600';
                document.getElementById('gridResults').classList.remove('hidden');
                document.getElementById('listResults').classList.add('hidden');
            } else {
                gridBtn.className = 'p-2 rounded-lg hover:bg-gray-100 text-gray-600';
                listBtn.className = 'p-2 rounded-lg bg-blue-100 text-blue-600';
                document.getElementById('gridResults').classList.add('hidden');
                document.getElementById('listResults').classList.remove('hidden');
            }
        }

        async function loadAlumni() {
            // Show loading state
            showLoadingState();

            // Build query parameters
            const params = new URLSearchParams({
                page: currentPage,
                limit: 12,
                ...currentFilters
            });

            // Remove empty values
            for (const [key, value] of params.entries()) {
                if (!value) params.delete(key);
            }

            try {
                const response = await makeApiCall(`search_directory.php?${params}`);

                if (response && (response.success || response.status === 'success')) {
                    const alumni = response.data || [];
                    const total = response.total || 0;
                    const perPage = response.per_page || 12;

                    totalPages = Math.ceil(total / perPage);

                    // Update result count
                    document.getElementById('resultCount').textContent =
                        `Showing ${alumni.length} of ${total} alumni`;

                    if (alumni.length === 0) {
                        showEmptyState();
                    } else {
                        hideAllStates();
                        renderAlumni(alumni);
                        renderPagination();
                    }

                    // Update URL without reloading
                    updateURL();
                } else {
                    showErrorState();
                }
            } catch (error) {
                console.error('Error loading alumni:', error);
                showErrorState();
            }
        }

        function renderAlumni(alumni) {
            const gridContainer = document.getElementById('gridResults');
            const listContainer = document.getElementById('listResults');

            // Clear containers
            gridContainer.innerHTML = '';
            listContainer.innerHTML = '';

            // Render alumni
            alumni.forEach(user => {
                // Grid view card
                const gridCard = createGridCard(user);
                gridContainer.appendChild(gridCard);

                // List view item
                const listItem = createListItem(user);
                listContainer.appendChild(listItem);
            });

            // Show current view
            setView(currentView);

            // Re-initialize icons
            lucide.createIcons();
        }

        function createGridCard(user) {
            const card = document.createElement('div');
            card.className = 'alumni-card bg-white rounded-xl shadow-sm overflow-hidden';

            // Determine role color
            let roleColor = 'bg-gray-100 text-gray-800';
            let roleText = user.role || 'Member';

            if (user.role === 'alumni') {
                roleColor = 'bg-green-100 text-green-800';
                roleText = 'Alumni';
            } else if (user.role === 'faculty') {
                roleColor = 'bg-blue-100 text-blue-800';
                roleText = 'Faculty';
            } else if (user.role === 'student') {
                roleColor = 'bg-purple-100 text-purple-800';
                roleText = 'Student';
            } else if (user.role === 'admin') {
                roleColor = 'bg-amber-100 text-amber-800';
                roleText = 'Admin';
            }

            // Check if profile is private
            const isPrivate = user.is_private && user.role !== 'student';
            const canMessage = !isPrivate && user.role !== 'student';

            card.innerHTML = `
                <div class="p-6">
                    <!-- Profile Header -->
                    <div class="flex items-center mb-4">
                        <div class="relative">
                            ${user.avatar ? 
                                `<img src="${user.avatar}" alt="${user.name}" class="h-16 w-16 rounded-full object-cover">` : 
                                `<div class="h-16 w-16 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i data-lucide="user" class="h-8 w-8 text-blue-600"></i>
                                </div>`}
                            <span class="absolute -bottom-1 -right-1 ${roleColor} text-xs px-2 py-0.5 rounded-full">
                                ${roleText}
                            </span>
                        </div>
                        <div class="ml-4">
                            <h3 class="font-semibold text-gray-900">${user.name}</h3>
                            <p class="text-sm text-gray-600">
                                ${user.branch || user.department || 'RJIT'}
                                ${user.graduation_year ? `• ${user.graduation_year}` : ''}
                            </p>
                        </div>
                    </div>
                    
                    <!-- Current Info -->
                    <div class="mb-4">
                        ${user.current_position ? `
                            <p class="font-medium text-gray-900 text-sm">${user.current_position}</p>
                        ` : ''}
                        ${user.current_company ? `
                            <p class="text-gray-600 text-sm">${user.current_company}</p>
                        ` : ''}
                        ${user.location ? `
                            <p class="text-gray-500 text-sm mt-1">
                                <i data-lucide="map-pin" class="h-3 w-3 inline mr-1"></i>
                                ${user.location}
                            </p>
                        ` : ''}
                    </div>
                    
                    <!-- Skills -->
                    ${user.skills && user.skills.length > 0 ? `
                        <div class="mb-4">
                            <div class="flex flex-wrap gap-1">
                                ${user.skills.slice(0, 3).map(skill => `
                                    <span class="px-2 py-1 bg-blue-50 text-blue-700 text-xs rounded">${skill}</span>
                                `).join('')}
                                ${user.skills.length > 3 ? `
                                    <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded">+${user.skills.length - 3}</span>
                                ` : ''}
                            </div>
                        </div>
                    ` : ''}
                    
                    <!-- Actions -->
                    <div class="flex space-x-2 pt-4 border-t border-gray-100">
                        <button onclick="viewProfile(${user.id})" 
                                class="flex-1 px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 font-medium">
                            View Profile
                        </button>
                        
                        ${canMessage ? `
                            <button onclick="openMessageModal(${user.id}, '${user.name}')" 
                                    class="flex-1 px-3 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 font-medium">
                                Message
                            </button>
                        ` : `
                            <button disabled
                                    class="flex-1 px-3 py-2 border border-gray-300 text-gray-400 text-sm rounded-lg opacity-50 cursor-not-allowed">
                                ${isPrivate ? 'Private' : 'Message'}
                            </button>
                        `}
                    </div>
                </div>
            `;

            return card;
        }

        function createListItem(user) {
            const item = document.createElement('div');
            item.className = 'alumni-card bg-white rounded-xl shadow-sm p-6';

            // Determine role color
            let roleColor = 'bg-gray-100 text-gray-800';
            let roleText = user.role || 'Member';

            if (user.role === 'alumni') {
                roleColor = 'bg-green-100 text-green-800';
                roleText = 'Alumni';
            } else if (user.role === 'faculty') {
                roleColor = 'bg-blue-100 text-blue-800';
                roleText = 'Faculty';
            } else if (user.role === 'student') {
                roleColor = 'bg-purple-100 text-purple-800';
                roleText = 'Student';
            } else if (user.role === 'admin') {
                roleColor = 'bg-amber-100 text-amber-800';
                roleText = 'Admin';
            }

            // Check if profile is private
            const isPrivate = user.is_private && user.role !== 'student';
            const canMessage = !isPrivate && user.role !== 'student';

            item.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="relative">
                            ${user.avatar ? 
                                `<img src="${user.avatar}" alt="${user.name}" class="h-12 w-12 rounded-full object-cover">` : 
                                `<div class="h-12 w-12 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i data-lucide="user" class="h-6 w-6 text-blue-600"></i>
                                </div>`}
                            <span class="absolute -bottom-1 -right-1 ${roleColor} text-xs px-2 py-0.5 rounded-full">
                                ${roleText}
                            </span>
                        </div>
                        <div class="ml-4">
                            <h3 class="font-semibold text-gray-900">${user.name}</h3>
                            <p class="text-sm text-gray-600">
                                ${user.branch || user.department || 'RJIT'}
                                ${user.graduation_year ? `• Class of ${user.graduation_year}` : ''}
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Current Info -->
                        <div class="text-right">
                            ${user.current_position ? `
                                <p class="font-medium text-gray-900 text-sm">${user.current_position}</p>
                            ` : ''}
                            ${user.current_company ? `
                                <p class="text-gray-600 text-sm">${user.current_company}</p>
                            ` : ''}
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex space-x-2">
                            <button onclick="viewProfile(${user.id})" 
                                    class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 font-medium">
                                View
                            </button>
                            
                            ${canMessage ? `
                                <button onclick="openMessageModal(${user.id}, '${user.name}')" 
                                        class="px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 font-medium">
                                    Message
                                </button>
                            ` : `
                                <button disabled
                                        class="px-4 py-2 border border-gray-300 text-gray-400 text-sm rounded-lg opacity-50 cursor-not-allowed">
                                    ${isPrivate ? 'Private' : 'Message'}
                                </button>
                            `}
                        </div>
                    </div>
                </div>
            `;

            return item;
        }

        function renderPagination() {
            const pagination = document.getElementById('pagination');
            const prevBtn = document.getElementById('prevPage');
            const nextBtn = document.getElementById('nextPage');
            const pageNumbers = document.getElementById('pageNumbers');

            if (totalPages <= 1) {
                pagination.classList.add('hidden');
                return;
            }

            pagination.classList.remove('hidden');

            // Enable/disable buttons
            prevBtn.disabled = currentPage === 1;
            nextBtn.disabled = currentPage === totalPages;

            // Generate page numbers
            pageNumbers.innerHTML = '';
            const maxVisiblePages = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
            let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

            if (endPage - startPage + 1 < maxVisiblePages) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }

            for (let i = startPage; i <= endPage; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.className = `px-3 py-1 rounded-lg ${i === currentPage ? 'bg-blue-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50'}`;
                pageBtn.textContent = i;
                pageBtn.addEventListener('click', () => {
                    currentPage = i;
                    loadAlumni();
                });
                pageNumbers.appendChild(pageBtn);
            }
        }

        function showLoadingState() {
            hideAllStates();
            document.getElementById('loadingState').classList.remove('hidden');
        }

        function showEmptyState() {
            hideAllStates();
            document.getElementById('emptyState').classList.remove('hidden');
            document.getElementById('pagination').classList.add('hidden');
        }

        function showErrorState() {
            hideAllStates();
            document.getElementById('errorState').classList.remove('hidden');
            document.getElementById('pagination').classList.add('hidden');
        }

        function hideAllStates() {
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('emptyState').classList.add('hidden');
            document.getElementById('errorState').classList.add('hidden');
            document.getElementById('gridResults').classList.add('hidden');
            document.getElementById('listResults').classList.add('hidden');
        }

        function updateURL() {
            const params = new URLSearchParams();

            for (const [key, value] of Object.entries(currentFilters)) {
                if (value) params.set(key, value);
            }

            if (currentPage > 1) {
                params.set('page', currentPage);
            }

            const newURL = params.toString() ? `discovery.php?${params.toString()}` : 'discovery.php';
            window.history.replaceState({}, '', newURL);
        }

        function applyFilters() {
            currentFilters.year = document.getElementById('filterYear').value;
            currentFilters.branch = document.getElementById('filterBranch').value;
            currentFilters.company = document.getElementById('filterCompany').value;
            currentFilters.role = document.getElementById('filterRole').value;
            currentPage = 1;
            loadAlumni();
        }

        function resetFilters() {
            currentFilters = {
                search: '',
                year: '',
                branch: '',
                company: '',
                role: '',
                sort: 'name_asc'
            };

            document.getElementById('searchAlumni').value = '';
            document.getElementById('filterYear').value = '';
            document.getElementById('filterBranch').value = '';
            document.getElementById('filterCompany').value = '';
            document.getElementById('filterRole').value = '';
            document.getElementById('sortBy').value = 'name_asc';

            currentPage = 1;
            loadAlumni();
        }

        async function loadTopCompanies() {
            try {
                // This would come from an API endpoint
                const companies = [{
                        name: 'Google',
                        count: 45
                    },
                    {
                        name: 'Microsoft',
                        count: 38
                    },
                    {
                        name: 'Amazon',
                        count: 32
                    },
                    {
                        name: 'Infosys',
                        count: 28
                    },
                    {
                        name: 'TCS',
                        count: 25
                    },
                    {
                        name: 'Wipro',
                        count: 22
                    }
                ];

                const container = document.getElementById('topCompanies');
                container.innerHTML = '';

                companies.forEach(company => {
                    const companyEl = document.createElement('div');
                    companyEl.className = 'text-center';
                    companyEl.innerHTML = `
                        <div class="h-16 w-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-2">
                            <i data-lucide="building" class="h-8 w-8 text-blue-600"></i>
                        </div>
                        <p class="font-medium text-gray-900">${company.name}</p>
                        <p class="text-sm text-gray-600">${company.count} alumni</p>
                    `;
                    container.appendChild(companyEl);
                });

                lucide.createIcons();
            } catch (error) {
                console.error('Error loading companies:', error);
            }
        }

        function viewProfile(userId) {
            window.location.href = `profile.php?id=${userId}`;
        }

        function openMessageModal(userId, userName) {
            currentRecipientId = userId;
            document.getElementById('recipientName').textContent = userName;
            document.getElementById('messageModal').classList.remove('hidden');
            document.getElementById('messageContent').focus();
        }

        function closeMessageModal() {
            document.getElementById('messageModal').classList.add('hidden');
            document.getElementById('messageContent').value = '';
            currentRecipientId = null;
        }

        async function sendMessage() {
            const content = document.getElementById('messageContent').value.trim();

            if (!content) {
                alert('Please enter a message');
                return;
            }

            try {
                const response = await makeApiCall('send_message.php', 'POST', {
                    recipient_id: currentRecipientId,
                    content: content
                });

                if (response && (response.success || response.status === 'success')) {
                    alert('Message sent successfully!');
                    closeMessageModal();
                } else {
                    alert(response.message || 'Failed to send message');
                }
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Error sending message');
            }
        }
    </script>
</body>

</html>