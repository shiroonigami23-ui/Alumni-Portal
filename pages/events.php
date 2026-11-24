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
                        <button data-filter="upcoming" onclick="filterEvents('upcoming', this)" class="event-filter-tab border-b-2 border-blue-500 py-4 px-1 text-sm font-medium text-blue-600">
                            Upcoming Events
                        </button>
                        <button data-filter="my-events" onclick="filterEvents('my-events', this)" class="event-filter-tab border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            My Events
                        </button>
                        <button data-filter="past" onclick="filterEvents('past', this)" class="event-filter-tab border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Past Events
                        </button>
                        <button id="pendingEventsTab" data-filter="pending" onclick="filterEvents('pending', this)" class="event-filter-tab hidden border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Pending approval <span id="pendingEventsCount" class="ml-1 hidden text-xs font-semibold px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-800"></span>
                        </button>
                    </nav>
                </div>

                <!-- Create Event Button (for faculty/admin) -->
                <div id="createEventSection" class="hidden mb-6">
                    <button onclick="showCreateEventModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center">
                        <i data-lucide="plus" class="h-5 w-5 mr-2"></i>
                        <span id="createEventLabel">Create Event</span>
                    </button>
                    <p id="proposeEventHint" class="hidden mt-2 text-sm text-gray-500">Alumni events are reviewed by a faculty member or admin before they go live.</p>
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

    document.addEventListener('DOMContentLoaded', async function() {
        loadEvents('upcoming');   // start the list right away; role-specific controls follow
        // What this user may do here comes from the live session (the server enforces it too)
        const user = await (window.portalUser || Promise.resolve(null));
        if (user) {
            const createEventSection = document.getElementById('createEventSection');
            const isStaff = user.role === 'admin' || user.role === 'faculty';
            if ((isStaff || user.role === 'alumni') && createEventSection) {
                createEventSection.classList.remove('hidden');
            }
            if (user.role === 'alumni') {
                document.getElementById('createEventLabel').textContent = 'Propose an event';
                document.getElementById('proposeEventHint').classList.remove('hidden');
            }
            if (isStaff) {
                document.getElementById('pendingEventsTab').classList.remove('hidden');
                refreshPendingCount();
            }
        }
        lucide.createIcons();
    });

    async function loadEvents(filter) {
        currentFilter = filter;
        const grid = document.getElementById('eventsGrid');
        grid.innerHTML = '<div class="col-span-full text-center py-12"><i data-lucide="loader" class="h-12 w-12 mx-auto text-gray-400 animate-spin"></i></div>';
        lucide.createIcons();

        if (filter === 'pending' || filter === 'my-events') {
            return loadEventQueue(filter);
        }
        try {
            const response = await makeApiCall(`events.php?action=list&filter=${encodeURIComponent(filter)}`);

            if (response && response.length > 0) {
                grid.innerHTML = response.map(event => `
                <div data-event-card="${Number(event.event_id)}" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition">
                    ${event.banner_url ? `<img src="${escapeHtml(safeUrl(event.banner_url))}" alt="${escapeHtml(event.title)}" class="w-full h-48 object-cover">` : ''}
                    <div class="p-6">
                        <div class="flex items-center text-sm text-gray-500 mb-2">
                            <i data-lucide="calendar" class="h-4 w-4 mr-1"></i>
                            ${window.portalTime ? window.portalTime.format(event.event_date, 'date') : window.formatDate(event.event_date, 'date')}
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">${escapeHtml(event.title)}</h3>
                        <p class="text-gray-600 text-sm mb-4 line-clamp-2">${escapeHtml(event.description)}</p>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center text-sm text-gray-500">
                                <i data-lucide="map-pin" class="h-4 w-4 mr-1"></i>
                                ${escapeHtml(event.location || 'TBD')}
                            </div>
                            <button onclick="rsvpEvent(${escapeHtml(event.event_id)})" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
                                RSVP
                            </button>
                        </div>
                        ${event.rsvp_count ? `<div class="mt-3 text-xs text-gray-500">${escapeHtml(event.rsvp_count)} attending</div>` : ''}
                    </div>
                </div>
            `).join('');
                window.vuFocusFromUrl?.('id', (id) => `[data-event-card="${Number(id)}"]`);   // events.php?id=5 (from Ctrl+K)
            } else {
                grid.innerHTML = '<div class="col-span-full text-center py-12"><i data-lucide="calendar-off" class="h-12 w-12 mx-auto text-gray-400"></i><p class="mt-4 text-gray-500">No events found</p></div>';
            }

            lucide.createIcons();
        } catch (error) {
            console.error('Error loading events:', error);
            grid.innerHTML = '<div class="col-span-full text-center py-12 text-red-500">Error loading events</div>';
        }
    }

    const EVENT_STATUS = {
        approved: ['Live', 'bg-emerald-100 text-emerald-800'],
        pending_approval: ['Awaiting approval', 'bg-amber-100 text-amber-800'],
        rejected: ['Not approved', 'bg-red-100 text-red-700'],
        cancelled: ['Cancelled', 'bg-gray-100 text-gray-600'],
        completed: ['Finished', 'bg-gray-100 text-gray-600'],
        live: ['Happening now', 'bg-blue-100 text-blue-800'],
    };

    // "My events" (any status) and the staff "Pending approval" queue.
    async function loadEventQueue(kind) {
        const grid = document.getElementById('eventsGrid');
        const res = await makeApiCall(`events.php?action=${kind === 'pending' ? 'pending' : 'mine'}`);
        const rows = (res && Array.isArray(res.data)) ? res.data : [];
        if (kind === 'pending') updatePendingBadge(rows.length);
        if (!rows.length) {
            grid.innerHTML = `<div class="col-span-full text-center py-12"><i data-lucide="${kind === 'pending' ? 'check-circle' : 'calendar-off'}" class="h-12 w-12 mx-auto text-gray-400"></i>
                <p class="mt-4 text-gray-500">${kind === 'pending' ? 'Nothing waiting for approval.' : 'You haven\'t created any events yet.'}</p></div>`;
            lucide.createIcons();
            return;
        }
        grid.innerHTML = rows.map((ev) => {
            const [label, tone] = EVENT_STATUS[ev.status] || [ev.status, 'bg-gray-100 text-gray-600'];
            const when = window.portalTime ? window.portalTime.format(ev.start_datetime, 'datetime') : window.formatDate(ev.start_datetime);
            return `
            <div data-event-card="${Number(ev.event_id)}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 flex flex-col">
                <div class="flex items-start justify-between gap-2">
                    <h3 class="text-lg font-semibold text-gray-900">${escapeHtml(ev.title)}</h3>
                    <span class="shrink-0 text-xs font-semibold px-2 py-0.5 rounded-full ${tone}">${escapeHtml(label)}</span>
                </div>
                <p class="text-sm text-gray-500 mt-1">${escapeHtml(when)} · ${escapeHtml(ev.location_address || 'TBD')}</p>
                ${kind === 'pending' ? `<p class="text-sm text-gray-600 mt-1">Proposed by <a class="text-blue-600 hover:underline" href="profile.php?id=${Number(ev.creator_user_id)}">${escapeHtml(ev.creator_name)}</a> (${escapeHtml(ev.creator_role)})</p>` : ''}
                <p class="text-gray-600 text-sm mt-3 line-clamp-3">${escapeHtml(ev.description || '')}</p>
                ${kind === 'pending' ? `
                <div class="mt-4 flex gap-2">
                    <button class="px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700" onclick="reviewEvent(${Number(ev.event_id)}, 'approve')">Approve</button>
                    <button class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm hover:bg-gray-50" onclick="reviewEvent(${Number(ev.event_id)}, 'reject')">Decline</button>
                </div>` : ''}
            </div>`;
        }).join('');
        lucide.createIcons();
    }

    async function reviewEvent(eventId, decision) {
        let reason = '';
        if (decision === 'reject') {
            const r = await window.appPrompt?.('Tell the organiser why (optional).', { title: 'Decline event', confirmText: 'Decline', inputLabel: 'Reason' });
            if (r === null) return;
            reason = String(r || '');
        } else if (!await window.appConfirm('Approve this event? It goes live for everyone and the organiser is notified.', { title: 'Approve event', confirmText: 'Approve' })) {
            return;
        }
        const res = await makeApiCall(`events.php?action=${decision}`, 'POST', { event_id: eventId, reason });
        window.toast?.((res && res.message) || 'Done', res && res.success === false ? 'error' : 'success');
        loadEventQueue('pending');
    }

    function updatePendingBadge(n) {
        const b = document.getElementById('pendingEventsCount');
        if (!b) return;
        b.textContent = n;
        b.classList.toggle('hidden', !n);
    }
    async function refreshPendingCount() {
        const res = await makeApiCall('events.php?action=pending');
        updatePendingBadge((res && Array.isArray(res.data)) ? res.data.length : 0);
    }

    function filterEvents(filter, trigger = null) {
        // Update tab styling
        document.querySelectorAll('.event-filter-tab').forEach(tab => {
            tab.classList.remove('border-blue-500', 'text-blue-600');
            tab.classList.add('border-transparent', 'text-gray-500');
        });
        const activeTab = trigger || document.querySelector(`.event-filter-tab[data-filter="${escapeHtml(filter)}"]`);
        if (activeTab) {
            activeTab.classList.remove('border-transparent', 'text-gray-500');
            activeTab.classList.add('border-blue-500', 'text-blue-600');
        }

        loadEvents(filter);
    }

    async function rsvpEvent(eventId) {
        try {
            const response = await makeApiCall('events.php?action=rsvp', 'POST', {
                event_id: eventId,
                status: 'attending'
            });

            if (response && response.message) {
                await window.appAlert(response.message, {
                    title: 'RSVP updated',
                    icon: 'calendar-check',
                    iconTone: 'success'
                });
                loadEvents(currentFilter);
            }
        } catch (error) {
            console.error('Error RSVPing to event:', error);
            await window.appAlert('Failed to RSVP. Please try again.', {
                title: 'RSVP failed',
                icon: 'triangle-alert',
                iconTone: 'danger'
            });
        }
    }

    function showCreateEventModal() {
        const modal = document.getElementById('createEventModal');
        if (modal) modal.classList.remove('hidden');
    }

    function closeCreateEventModal() {
        const modal = document.getElementById('createEventModal');
        const form = document.getElementById('createEventForm');
        if (modal) modal.classList.add('hidden');
        if (form) form.reset();
    }

    const createEventForm = document.getElementById('createEventForm');
    if (createEventForm) createEventForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = {
            title: document.getElementById('eventTitle').value,
            description: document.getElementById('eventDescription').value,
            event_date: document.getElementById('eventDate').value,
            event_time: document.getElementById('eventTime').value,
            location: document.getElementById('eventLocation').value,
            rsvp_limit: document.getElementById('eventRsvpLimit').value || null
        };

        try {
            const response = await makeApiCall('events.php?action=create', 'POST', formData);

            if (response && response.success === false) {
                await window.appAlert(response.message || 'Could not create the event.', { title: 'Event not created', icon: 'triangle-alert', iconTone: 'danger' });
                return;
            }
            if (response && response.message) {
                await window.appAlert(response.message, {
                    title: response.status === 'pending_approval' ? 'Sent for approval' : 'Event created',
                    icon: 'calendar-plus',
                    iconTone: 'success'
                });
                closeCreateEventModal();
                loadEvents(currentFilter);
            }
        } catch (error) {
            console.error('Error creating event:', error);
            await window.appAlert('Failed to create event. Please try again.', {
                title: 'Event creation failed',
                icon: 'triangle-alert',
                iconTone: 'danger'
            });
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
