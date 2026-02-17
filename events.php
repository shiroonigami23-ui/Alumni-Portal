<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="md:pl-64 flex flex-col flex-1">
    <main class="flex-1">
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                <!-- Page Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Events</h1>
                    <p class="mt-2 text-gray-600">Discover and join alumni events, workshops, and networking opportunities</p>
                </div>

                <!-- Filter Tabs -->
                <div class="mb-6 border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8">
                        <button onclick="filterEvents('upcoming')" class="event-filter-tab border-b-2 border-blue-500 py-4 px-1 text-sm font-medium text-blue-600">
                            Upcoming Events
                        </button>
                        <button onclick="filterEvents('my-events')" class="event-filter-tab border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            My Events
                        </button>
                        <button onclick="filterEvents('past')" class="event-filter-tab border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Past Events
                        </button>
                    </nav>
                </div>

                <!-- Create Event Button (for faculty/admin) -->
                <div id="createEventSection" class="hidden mb-6">
                    <button onclick="showCreateEventModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center">
                        <i data-lucide="plus" class="h-5 w-5 mr-2"></i>
                        Create Event
                    </button>
                </div>

                <!-- Events Grid -->
                <div id="eventsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Events will be loaded here -->
                    <div class="col-span-full text-center py-12">
                        <i data-lucide="loader" class="h-12 w-12 mx-auto text-gray-400 animate-spin"></i>
                        <p class="mt-4 text-gray-500">Loading events...</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Create Event Modal -->
<div id="createEventModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Create New Event</h3>
            <button onclick="closeCreateEventModal()" class="text-gray-400 hover:text-gray-500">
                <i data-lucide="x" class="h-6 w-6"></i>
            </button>
        </div>
        <form id="createEventForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Event Title</label>
                <input type="text" id="eventTitle" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea id="eventDescription" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Date</label>
                    <input type="date" id="eventDate" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Time</label>
                    <input type="time" id="eventTime" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Location</label>
                <input type="text" id="eventLocation" placeholder="Physical location or virtual link" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">RSVP Limit (Optional)</label>
                <input type="number" id="eventRsvpLimit" placeholder="Leave empty for unlimited" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeCreateEventModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Create Event
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let currentFilter = 'upcoming';

    document.addEventListener('DOMContentLoaded', function() {
        // Check if user can create events
        const userData = localStorage.getItem('user_data');
        if (userData) {
            const user = JSON.parse(userData);
            if (user.role === 'admin' || user.role === 'faculty') {
                document.getElementById('createEventSection').classList.remove('hidden');
            }
        }

        loadEvents('upcoming');
        lucide.createIcons();
    });

    async function loadEvents(filter) {
        currentFilter = filter;
        const grid = document.getElementById('eventsGrid');
        grid.innerHTML = '<div class="col-span-full text-center py-12"><i data-lucide="loader" class="h-12 w-12 mx-auto text-gray-400 animate-spin"></i></div>';
        lucide.createIcons();

        try {
            const response = await makeApiCall(`events.php?action=list&filter=${filter}`);

            if (response && response.length > 0) {
                grid.innerHTML = response.map(event => `
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition">
                    ${event.banner_url ? `<img src="${event.banner_url}" alt="${event.title}" class="w-full h-48 object-cover">` : ''}
                    <div class="p-6">
                        <div class="flex items-center text-sm text-gray-500 mb-2">
                            <i data-lucide="calendar" class="h-4 w-4 mr-1"></i>
                            ${new Date(event.event_date).toLocaleDateString()}
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">${event.title}</h3>
                        <p class="text-gray-600 text-sm mb-4 line-clamp-2">${event.description}</p>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center text-sm text-gray-500">
                                <i data-lucide="map-pin" class="h-4 w-4 mr-1"></i>
                                ${event.location || 'TBD'}
                            </div>
                            <button onclick="rsvpEvent(${event.event_id})" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
                                RSVP
                            </button>
                        </div>
                        ${event.rsvp_count ? `<div class="mt-3 text-xs text-gray-500">${event.rsvp_count} attending</div>` : ''}
                    </div>
                </div>
            `).join('');
            } else {
                grid.innerHTML = '<div class="col-span-full text-center py-12"><i data-lucide="calendar-off" class="h-12 w-12 mx-auto text-gray-400"></i><p class="mt-4 text-gray-500">No events found</p></div>';
            }

            lucide.createIcons();
        } catch (error) {
            console.error('Error loading events:', error);
            grid.innerHTML = '<div class="col-span-full text-center py-12 text-red-500">Error loading events</div>';
        }
    }

    function filterEvents(filter) {
        // Update tab styling
        document.querySelectorAll('.event-filter-tab').forEach(tab => {
            tab.classList.remove('border-blue-500', 'text-blue-600');
            tab.classList.add('border-transparent', 'text-gray-500');
        });
        event.target.classList.remove('border-transparent', 'text-gray-500');
        event.target.classList.add('border-blue-500', 'text-blue-600');

        loadEvents(filter);
    }

    async function rsvpEvent(eventId) {
        try {
            const csrfToken = localStorage.getItem('csrf_token');
            const response = await makeApiCall('events.php?action=rsvp', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    event_id: eventId,
                    status: 'attending'
                })
            });

            if (response && response.message) {
                alert(response.message);
                loadEvents(currentFilter);
            }
        } catch (error) {
            console.error('Error RSVPing to event:', error);
            alert('Failed to RSVP. Please try again.');
        }
    }

    function showCreateEventModal() {
        document.getElementById('createEventModal').classList.remove('hidden');
    }

    function closeCreateEventModal() {
        document.getElementById('createEventModal').classList.add('hidden');
        document.getElementById('createEventForm').reset();
    }

    document.getElementById('createEventForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const csrfToken = localStorage.getItem('csrf_token');
        const formData = {
            title: document.getElementById('eventTitle').value,
            description: document.getElementById('eventDescription').value,
            event_date: document.getElementById('eventDate').value,
            event_time: document.getElementById('eventTime').value,
            location: document.getElementById('eventLocation').value,
            rsvp_limit: document.getElementById('eventRsvpLimit').value || null
        };

        try {
            const response = await makeApiCall('events.php?action=create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(formData)
            });

            if (response && response.message) {
                alert(response.message);
                closeCreateEventModal();
                loadEvents(currentFilter);
            }
        } catch (error) {
            console.error('Error creating event:', error);
            alert('Failed to create event. Please try again.');
        }
    });
</script>

<?php include 'includes/footer.php'; ?>