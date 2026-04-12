<?php
// settings.php
session_start();
#require_once 'includes/auth_check.php';

$pageTitle = "Settings - RJIT Alumni Portal";
include 'includes/header.php';
?>
<?php include 'includes/sidebar.php'; ?>
<style>
    .settings-collapsible-btn {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        background: #f8fafc;
        font-weight: 600;
        color: #111827;
    }
    .settings-collapsible-btn:hover { background: #f1f5f9; }
    .settings-collapsible-content { margin-top: 0.75rem; }
</style>

<div class="md:pl-64 pb-20 md:pb-0">
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
                            <img id="settingsSidebarAvatar" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='48' height='48' viewBox='0 0 48 48'%3E%3Crect width='48' height='48' fill='%23e2e8f0'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='14' fill='%2364748b'%3EU%3C/text%3E%3C/svg%3E" alt="Profile" class="h-12 w-12 rounded-full">
                            <div class="ml-4">
                                <h3 id="settingsSidebarName" class="font-bold text-gray-900">Loading...</h3>
                                <p id="settingsSidebarRole" class="text-sm text-gray-600">Member</p>
                            </div>
                        </div>
                    </div>
                    
                    <nav id="settingsLocalNav" class="p-4">
                        <a href="#profile" class="flex items-center px-4 py-3 text-blue-600 bg-blue-50 rounded-lg mb-2">
                            <i data-lucide="user" class="h-5 w-5 mr-3"></i>
                            Profile
                        </a>
                        <a href="#privacy" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg mb-2">
                            <i data-lucide="shield" class="h-5 w-5 mr-3"></i>
                            Privacy & Security
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
                                <img id="settingsProfileAvatar" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' fill='%23dbeafe'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='18' fill='%233b82f6'%3EUser%3C/text%3E%3C/svg%3E" alt="Profile" class="h-24 w-24 rounded-full object-cover">
                            </div>
                            <div>
                                <input type="file" id="settingsAvatarInput" accept="image/*" class="hidden">
                                <button id="settingsUploadPhotoBtn" type="button" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 font-medium mr-3">
                                    Upload New Photo
                                </button>
                                <button id="settingsRemovePhotoBtn" type="button" class="text-red-600 hover:text-red-800 font-medium">
                                    Remove Photo
                                </button>
                                <p class="text-sm text-gray-500 mt-2">Recommended: Square JPG, PNG at least 400x400 pixels</p>
                            </div>
                        </div>
                        
                        <form id="settingsProfileForm" class="space-y-6">
                            <div id="settingsBasicSection" data-scope="basic" class="pt-2 border-t border-gray-100">
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">Basic Profile</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                                    <input id="settingsFirstName" type="text" 
                                           value="" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                                    <input id="settingsLastName" type="text" 
                                           value="" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Login Email</label>
                                <input id="settingsEmailInput" type="email" 
                                       value="" 
                                       readonly
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed focus:outline-none">
                                <p id="settingsEmailHelp" class="mt-2 text-xs text-gray-500">Your sign-in email is managed separately from profile details.</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Bio</label>
                                <textarea id="settingsBioInput" rows="4" 
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                            </div>
                            </div>

                            <div id="settingsSkillsSection" data-scope="skills" class="pt-2 border-t border-gray-100">
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">Skills</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Skills (comma separated)</label>
                                        <input id="settingsSkillsInput" type="text"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               placeholder="Communication, C++, Data Analysis">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Tech Stack</label>
                                        <input id="settingsTechStackInput" type="text"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               placeholder="PHP, PostgreSQL, JavaScript">
                                    </div>
                                </div>
                            </div>

                            <div id="settingsWorkSection" data-scope="work" class="pt-2 border-t border-gray-100">
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">Education & Work</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Joining Year</label>
                                        <input id="settingsJoiningYearInput" type="number" min="1999" max="2099"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               placeholder="e.g. 2010">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Graduation Year</label>
                                        <input id="settingsGraduationYearInput" type="number" min="1999" max="2099"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               placeholder="e.g. 2014">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Company</label>
                                        <input id="settingsCurrentCompanyInput" type="text"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Job Role</label>
                                        <input id="settingsJobRoleInput" type="text"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Department / Branch</label>
                                        <input id="settingsDepartmentInput" type="text"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Designation</label>
                                        <input id="settingsDesignationInput" type="text"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>
                            </div>

                            <div id="settingsContactSection" data-scope="contact" class="pt-2 border-t border-gray-100">
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">Contact & Social</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Mobile Number</label>
                                        <input id="settingsContactNumberInput" type="text"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Website</label>
                                        <input id="settingsWebsiteInput" type="url"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Location City</label>
                                        <input id="settingsLocationCityInput" type="text"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Location Country</label>
                                        <input id="settingsLocationCountryInput" type="text"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">LinkedIn URL</label>
                                        <input id="settingsLinkedinInput" type="url"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">GitHub URL</label>
                                        <input id="settingsGithubInput" type="url"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Twitter URL</label>
                                        <input id="settingsTwitterInput" type="url"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">How You Can Help Your Alumni Mates</label>
                                        <textarea id="settingsHelpAlumniInput" rows="3"
                                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                  placeholder="Mentorship, referrals, interview prep, networking, etc."></textarea>
                                    </div>
                                </div>
                            </div>
                             
                            <div class="flex justify-end">
                                <button type="button" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 mr-3">
                                    Cancel
                                </button>
                                <button id="settingsSaveBtn" type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Privacy & Security -->
                <div id="privacy" data-scope="privacy" class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900">Privacy & Security</h2>
                        <p class="text-gray-600 text-sm">Manage your password and account privacy</p>
                    </div>
                    
                    <div class="p-6 space-y-6">
                        <div id="settingsPrivacySection" data-scope="privacy" class="pt-2 border-t border-gray-100">
                            <h3 class="font-semibold text-gray-900 mb-4">Profile Privacy</h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900">Profile Visibility</p>
                                        <p id="settingsPrivacyHelpText" class="text-sm text-gray-500">Public: everyone can view your profile</p>
                                        <p class="text-xs text-gray-400 mt-1">Toggle ON for Private, OFF for Public.</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span id="settingsPrivacyStateBadge" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Public</span>
                                        <button id="settingsTogglePrivacy" type="button" class="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-200" aria-pressed="false" aria-label="Toggle profile privacy">
                                            <span id="settingsPrivacyToggleKnob" class="inline-block h-4 w-4 translate-x-1 transform rounded-full bg-white transition"></span>
                                        </button>
                                    </div>
                                </div>
                                <div class="pt-2">
                                    <button id="settingsSavePrivacyBtn" type="button" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                        Save Privacy Settings
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- Password Change -->
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-4">Change Password</h3>
                            <form id="settingsPasswordForm" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                                    <input id="settingsCurrentPassword" type="password" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                                    <input id="settingsNewPassword" type="password" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                                    <input id="settingsConfirmPassword" type="password" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <button id="settingsPasswordSaveBtn" type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                                    Update Password
                                </button>
                            </form>
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
    function splitName(name) {
        const parts = (name || '').trim().split(/\s+/).filter(Boolean);
        if (!parts.length) return { first: '', last: '' };
        return { first: parts[0], last: parts.slice(1).join(' ') };
    }

    function formatRole(role) {
        if (!role) return 'Member';
        return role.charAt(0).toUpperCase() + role.slice(1);
    }

    function applyStudentProfileLock(role) {
        if (String(role || '').toLowerCase() !== 'student') return;
        const form = document.getElementById('settingsProfileForm');
        if (!form) return;
        form.querySelectorAll('input, textarea, select, button').forEach((el) => {
            if (el.id === 'settingsEmailInput') return;
            if (el.id === 'settingsSaveBtn' || el.id === 'settingsUploadPhotoBtn' || el.id === 'settingsRemovePhotoBtn') {
                el.disabled = true;
                el.classList.add('hidden');
                return;
            }
            if (el.tagName === 'BUTTON') return;
            el.disabled = true;
            el.classList.add('bg-gray-50', 'cursor-not-allowed');
        });
        const notice = document.createElement('div');
        notice.className = 'mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800';
        notice.textContent = 'Student profile editing is disabled by institute policy.';
        form.prepend(notice);
    }

    async function loadSettingsUser() {
        try {
            const response = await makeApiCall('me.php');
            if (!response || !response.success || !response.data) return;

            const user = response.data;
            localStorage.setItem('user_data', JSON.stringify(user));

            const fullName = user.full_name || user.name || (user.email ? user.email.split('@')[0] : 'Member');
            const roleText = formatRole(user.role);
            window.settingsCurrentUserRole = (user.role || '').toLowerCase();
            const { first, last } = splitName(fullName);
            const avatar = (user.profile_picture || user.profile_picture_url || '').replace(/\\/g, '/');

            document.getElementById('settingsSidebarName').textContent = fullName;
            document.getElementById('settingsSidebarRole').textContent = roleText;
            document.getElementById('settingsFirstName').value = first;
            document.getElementById('settingsLastName').value = last;
            document.getElementById('settingsEmailInput').value = user.email || '';
            const emailHelp = document.getElementById('settingsEmailHelp');
            if (emailHelp) {
                emailHelp.textContent = window.settingsCurrentUserRole === 'student'
                    ? 'Student login email is fixed by institute policy and cannot be changed here.'
                    : 'Login email is managed separately from your public profile. Use bio, contact number, website, or social links for public contact details.';
            }
            document.getElementById('settingsBioInput').value = user.bio || '';

            const sideImg = document.getElementById('settingsSidebarAvatar');
            const profileImg = document.getElementById('settingsProfileAvatar');
            if (avatar) {
                sideImg.src = avatar;
                profileImg.src = avatar;
            } else {
                sideImg.src = profileImg.src;
            }

            const profileRes = await makeApiCall(`get_user_profile.php?user_id=${user.user_id || user.id}`);
            if (profileRes && (profileRes.success || profileRes.status === 'success') && profileRes.data) {
                const p = profileRes.data;
                const setIf = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.value = val || '';
                };
                setIf('settingsSkillsInput', p.skills || '');
                setIf('settingsTechStackInput', p.tech_stack || '');
                setIf('settingsCurrentCompanyInput', p.current_company || '');
                setIf('settingsJobRoleInput', p.job_role || '');
                setIf('settingsDepartmentInput', p.department || p.branch || '');
                setIf('settingsDesignationInput', p.designation || '');
                setIf('settingsContactNumberInput', p.contact_number || '');
                setIf('settingsJoiningYearInput', p.joining_year || '');
                setIf('settingsGraduationYearInput', p.graduation_year || '');
                setIf('settingsLocationCityInput', p.location_city || '');
                setIf('settingsLocationCountryInput', p.location_country || '');
                setIf('settingsWebsiteInput', p.personal_website || '');
                setIf('settingsLinkedinInput', p.linkedin_url || '');
                setIf('settingsGithubInput', p.github_url || '');
                setIf('settingsTwitterInput', p.twitter_url || '');
                setIf('settingsHelpAlumniInput', p.help_alumni_mates || '');
            }
            applyStudentProfileLock(window.settingsCurrentUserRole);
        } catch (error) {
            console.error('Error loading settings user:', error);
        }
    }

    document.addEventListener('DOMContentLoaded', loadSettingsUser);

    async function uploadSettingsAvatar(file) {
        const formData = new FormData();
        formData.append('avatar', file);
        const token = localStorage.getItem('jwt_token');
        const apiBase = (window.getApiBase ? window.getApiBase() : ((window.PORTAL_BASE_PREFIX || '') + 'api'));
        const response = await fetch(`${apiBase}/upload_avatar.php`, {
            method: 'POST',
            headers: { 'Authorization': `Bearer ${token}` },
            body: formData
        });
        const result = await response.json();
        if (!result || !result.success) {
            throw new Error((result && result.message) || 'Failed to upload avatar');
        }
        return result.avatar_url;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const uploadBtn = document.getElementById('settingsUploadPhotoBtn');
        const avatarInput = document.getElementById('settingsAvatarInput');
        const removeBtn = document.getElementById('settingsRemovePhotoBtn');
        const saveForm = document.getElementById('settingsProfileForm');

        if (uploadBtn && avatarInput) {
            uploadBtn.addEventListener('click', function() { avatarInput.click(); });
            avatarInput.addEventListener('change', async function() {
                const file = this.files && this.files[0];
                if (!file) return;
                try {
                    const avatarUrl = await uploadSettingsAvatar(file);
                    document.getElementById('settingsProfileAvatar').src = avatarUrl;
                    document.getElementById('settingsSidebarAvatar').src = avatarUrl;
                    const userData = JSON.parse(localStorage.getItem('user_data') || '{}');
                    userData.profile_picture = avatarUrl;
                    userData.profile_picture_url = avatarUrl;
                    localStorage.setItem('user_data', JSON.stringify(userData));
                    await window.appAlert('Profile photo updated.', {
                        title: 'Profile updated',
                        icon: 'image-up',
                        iconTone: 'success'
                    });
                } catch (e) {
                    await window.appAlert(e.message || 'Failed to update photo', {
                        title: 'Photo update failed',
                        icon: 'triangle-alert',
                        iconTone: 'danger'
                    });
                } finally {
                    avatarInput.value = '';
                }
            });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', async function() {
                const fallback = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' fill='%23dbeafe'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='18' fill='%233b82f6'%3EUser%3C/text%3E%3C/svg%3E";
                try {
                    await makeApiCall('update_profile.php', 'POST', { profile_picture_url: '' });
                    document.getElementById('settingsProfileAvatar').src = fallback;
                    document.getElementById('settingsSidebarAvatar').src = fallback;
                    const userData = JSON.parse(localStorage.getItem('user_data') || '{}');
                    userData.profile_picture = '';
                    userData.profile_picture_url = '';
                    localStorage.setItem('user_data', JSON.stringify(userData));
                    await window.appAlert('Profile photo removed.', {
                        title: 'Profile updated',
                        icon: 'image-minus',
                        iconTone: 'success'
                    });
                } catch (err) {
                    await window.appAlert('Failed to remove profile photo.', {
                        title: 'Photo removal failed',
                        icon: 'triangle-alert',
                        iconTone: 'danger'
                    });
                }
            });
        }

        if (saveForm) {
            saveForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const first = document.getElementById('settingsFirstName').value.trim();
                const last = document.getElementById('settingsLastName').value.trim();
                const bio = document.getElementById('settingsBioInput').value.trim();
                const full_name = `${first} ${last}`.trim();
                const btn = document.getElementById('settingsSaveBtn');
                const original = btn.textContent;
                try {
                    btn.disabled = true;
                    btn.textContent = 'Saving...';
                    const res = await makeApiCall('update_profile.php', 'POST', {
                        full_name,
                        bio,
                        skills: document.getElementById('settingsSkillsInput').value.trim(),
                        tech_stack: document.getElementById('settingsTechStackInput').value.trim(),
                        current_company: document.getElementById('settingsCurrentCompanyInput').value.trim(),
                        job_role: document.getElementById('settingsJobRoleInput').value.trim(),
                        department: document.getElementById('settingsDepartmentInput').value.trim(),
                        branch: document.getElementById('settingsDepartmentInput').value.trim(),
                        designation: document.getElementById('settingsDesignationInput').value.trim(),
                        contact_number: document.getElementById('settingsContactNumberInput').value.trim(),
                        joining_year: document.getElementById('settingsJoiningYearInput').value.trim(),
                        graduation_year: document.getElementById('settingsGraduationYearInput').value.trim(),
                        location_city: document.getElementById('settingsLocationCityInput').value.trim(),
                        location_country: document.getElementById('settingsLocationCountryInput').value.trim(),
                        personal_website: document.getElementById('settingsWebsiteInput').value.trim(),
                        linkedin_url: document.getElementById('settingsLinkedinInput').value.trim(),
                        github_url: document.getElementById('settingsGithubInput').value.trim(),
                        twitter_url: document.getElementById('settingsTwitterInput').value.trim(),
                        help_alumni_mates: document.getElementById('settingsHelpAlumniInput').value.trim()
                    });
                    if (!res || res.message === 'Failed to update profile.') {
                        throw new Error((res && res.message) || 'Failed to save settings');
                    }
                    const userData = JSON.parse(localStorage.getItem('user_data') || '{}');
                    userData.full_name = full_name;
                    userData.name = full_name;
                    localStorage.setItem('user_data', JSON.stringify(userData));
                    document.getElementById('settingsSidebarName').textContent = full_name || userData.email || 'Member';
                    await window.appAlert('Profile settings updated successfully.', {
                        title: 'Settings saved',
                        icon: 'circle-check',
                        iconTone: 'success'
                    });
                } catch (err) {
                    await window.appAlert(err.message || 'Error saving settings', {
                        title: 'Save failed',
                        icon: 'triangle-alert',
                        iconTone: 'danger'
                    });
                } finally {
                    btn.disabled = false;
                    btn.textContent = original;
                }
            });
        }

        const passwordForm = document.getElementById('settingsPasswordForm');
        if (passwordForm) {
            passwordForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const currentPassword = document.getElementById('settingsCurrentPassword').value.trim();
                const newPassword = document.getElementById('settingsNewPassword').value.trim();
                const confirmPassword = document.getElementById('settingsConfirmPassword').value.trim();
                const btn = document.getElementById('settingsPasswordSaveBtn');
                const original = btn.textContent;

                if (!currentPassword || !newPassword || !confirmPassword) {
                    await window.appAlert('Please fill all password fields.', {
                        title: 'Missing details',
                        icon: 'triangle-alert',
                        iconTone: 'danger'
                    });
                    return;
                }
                if (newPassword !== confirmPassword) {
                    await window.appAlert('New password and confirm password do not match.', {
                        title: 'Password mismatch',
                        icon: 'triangle-alert',
                        iconTone: 'danger'
                    });
                    return;
                }
                if (newPassword.length < 8) {
                    await window.appAlert('New password must be at least 8 characters.', {
                        title: 'Password too short',
                        icon: 'triangle-alert',
                        iconTone: 'danger'
                    });
                    return;
                }

                try {
                    btn.disabled = true;
                    btn.textContent = 'Updating...';
                    const res = await makeApiCall('change_password.php', 'POST', {
                        current_password: currentPassword,
                        new_password: newPassword
                    });
                    if (!res || !(res.success || res.status === 'success')) {
                        throw new Error((res && res.message) || 'Failed to change password');
                    }
                    await window.appAlert('Password updated successfully.', {
                        title: 'Password updated',
                        icon: 'shield-check',
                        iconTone: 'success'
                    });
                    passwordForm.reset();
                } catch (err) {
                    await window.appAlert(err.message || 'Failed to change password', {
                        title: 'Password update failed',
                        icon: 'triangle-alert',
                        iconTone: 'danger'
                    });
                } finally {
                    btn.disabled = false;
                    btn.textContent = original;
                }
            });
        }
    });

    // Tab navigation
    document.querySelectorAll('#settingsLocalNav a[href^="#"]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all links
            document.querySelectorAll('#settingsLocalNav a[href^="#"]').forEach(l => {
                l.classList.remove('text-blue-600', 'bg-blue-50');
                l.classList.add('text-gray-700', 'hover:bg-gray-50');
            });
            
            // Add active class to clicked link
            this.classList.remove('text-gray-700', 'hover:bg-gray-50');
            this.classList.add('text-blue-600', 'bg-blue-50');
            
            // Scroll to section
            const targetId = this.getAttribute('href').substring(1);
            const targetEl = document.getElementById(targetId);
            if (targetEl) {
                if (targetId === 'profile') openSettingsSection('basic');
                if (targetId === 'privacy') openSettingsSection('privacy');
                targetEl.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    function focusScopedEditorFromQuery() {
        const params = new URLSearchParams(window.location.search);
        const scope = (params.get('scope') || '').toLowerCase();
        if (!scope) return;
        const map = {
            basic: 'settingsFirstName',
            work: 'settingsWorkSection',
            skills: 'settingsSkillsSection',
            contact: 'settingsContactSection',
            privacy: 'settingsPrivacySection'
        };
        const targetId = map[scope];
        if (!targetId) return;
        const target = document.getElementById(targetId);
        if (!target) return;
        openSettingsSection(scope);
        if (scope === 'privacy') {
            const privacyBlock = document.getElementById('privacy');
            if (privacyBlock) privacyBlock.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        target.classList.add('ring-2', 'ring-blue-200', 'rounded-lg');
        if (target.focus && target.tagName !== 'DIV') target.focus();
        setTimeout(() => target.classList.remove('ring-2', 'ring-blue-200', 'rounded-lg'), 1600);
    }

    function setPrivacyToggleState(isPrivate) {
        const btn = document.getElementById('settingsTogglePrivacy');
        const knob = document.getElementById('settingsPrivacyToggleKnob');
        const badge = document.getElementById('settingsPrivacyStateBadge');
        const help = document.getElementById('settingsPrivacyHelpText');
        if (!btn || !knob) return;
        btn.classList.toggle('bg-blue-600', !!isPrivate);
        btn.classList.toggle('bg-gray-200', !isPrivate);
        knob.classList.toggle('translate-x-6', !!isPrivate);
        btn.dataset.private = isPrivate ? '1' : '0';
        btn.setAttribute('aria-pressed', isPrivate ? 'true' : 'false');
        if (badge) {
            badge.textContent = isPrivate ? 'Private' : 'Public';
            badge.className = isPrivate
                ? 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700'
                : 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700';
        }
        if (help) {
            help.textContent = isPrivate
                ? 'Private: only approved/connected users can view your profile'
                : 'Public: everyone can view your profile';
        }
    }

    async function initSettingsPrivacy() {
        try {
            const user = JSON.parse(localStorage.getItem('user_data') || '{}');
            const role = String(user.role || window.settingsCurrentUserRole || '').toLowerCase();
            const uid = user.user_id || user.id;
            if (!uid) return;
            const res = await makeApiCall(`get_user_profile.php?user_id=${uid}`);
            let isPrivate = !!(res && (res.success || res.status === 'success') && res.data && res.data.is_private);
            if (role === 'student') {
                isPrivate = false;
            }
            setPrivacyToggleState(isPrivate);

            if (role === 'student') {
                const btn = document.getElementById('settingsTogglePrivacy');
                const saveBtn = document.getElementById('settingsSavePrivacyBtn');
                const help = document.getElementById('settingsPrivacyHelpText');
                const badge = document.getElementById('settingsPrivacyStateBadge');
                if (btn) {
                    btn.disabled = true;
                    btn.classList.add('hidden');
                    btn.setAttribute('aria-label', 'Students are always public');
                }
                if (saveBtn) {
                    saveBtn.disabled = true;
                    saveBtn.classList.add('hidden');
                }
                if (badge) {
                    badge.textContent = 'Public (Required)';
                    badge.className = 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700';
                }
                if (help) {
                    help.textContent = 'Students are always public. This setting is locked by portal policy.';
                }
            }
        } catch (e) {
            console.error('Privacy init failed', e);
        }
        const btn = document.getElementById('settingsTogglePrivacy');
        const saveBtn = document.getElementById('settingsSavePrivacyBtn');
        if (btn) {
            btn.addEventListener('click', () => {
                const current = btn.dataset.private === '1';
                setPrivacyToggleState(!current);
            });
        }
        if (saveBtn) {
            saveBtn.addEventListener('click', async () => {
                const user = JSON.parse(localStorage.getItem('user_data') || '{}');
                const role = String(user.role || window.settingsCurrentUserRole || '').toLowerCase();
                if (role === 'student') {
                    await window.appAlert('Students are always public on this portal.', {
                        title: 'Privacy locked',
                        icon: 'info',
                        iconTone: 'info'
                    });
                    setPrivacyToggleState(false);
                    return;
                }
                const isPrivate = (document.getElementById('settingsTogglePrivacy')?.dataset.private === '1');
                const res = await makeApiCall('update_privacy.php', 'POST', { is_private: isPrivate ? 1 : 0 });
                if (res && (res.success || res.status === 'success' || res.message)) {
                    await window.appAlert(`Privacy settings saved: ${isPrivate ? 'Private' : 'Public'}.`, {
                        title: 'Privacy updated',
                        icon: 'shield-check',
                        iconTone: 'success'
                    });
                } else {
                    await window.appAlert((res && res.message) || 'Failed to save privacy settings.', {
                        title: 'Privacy update failed',
                        icon: 'triangle-alert',
                        iconTone: 'danger'
                    });
                }
            });
        }
    }

    function buildSettingsCollapsibles() {
        const sections = [
            { scope: 'basic', id: 'settingsBasicSection', label: 'Basic Profile' },
            { scope: 'skills', id: 'settingsSkillsSection', label: 'Skills' },
            { scope: 'work', id: 'settingsWorkSection', label: 'Education & Work' },
            { scope: 'contact', id: 'settingsContactSection', label: 'Contact & Social' },
            { scope: 'privacy', id: 'settingsPrivacySection', label: 'Privacy' }
        ];

        sections.forEach((cfg) => {
            const section = document.getElementById(cfg.id);
            if (!section || section.dataset.collapsibleReady === '1') return;
            section.dataset.collapsibleReady = '1';
            const content = document.createElement('div');
            content.className = 'settings-collapsible-content hidden';
            while (section.firstChild) content.appendChild(section.firstChild);
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'settings-collapsible-btn';
            btn.innerHTML = `<span>${cfg.label}</span><i data-lucide="chevron-down" class="h-4 w-4"></i>`;
            btn.addEventListener('click', () => {
                const isHidden = content.classList.contains('hidden');
                content.classList.toggle('hidden', !isHidden);
                const icon = btn.querySelector('i');
                if (icon) icon.setAttribute('data-lucide', isHidden ? 'chevron-up' : 'chevron-down');
                lucide.createIcons();
            });
            section.appendChild(btn);
            section.appendChild(content);
        });
        lucide.createIcons();
    }

    function openSettingsSection(scope) {
        const map = {
            basic: 'settingsBasicSection',
            skills: 'settingsSkillsSection',
            work: 'settingsWorkSection',
            contact: 'settingsContactSection',
            privacy: 'settingsPrivacySection'
        };
        const section = document.getElementById(map[scope] || '');
        if (!section) return;
        const content = section.querySelector('.settings-collapsible-content');
        const btn = section.querySelector('.settings-collapsible-btn');
        if (content && content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            const icon = btn ? btn.querySelector('i') : null;
            if (icon) icon.setAttribute('data-lucide', 'chevron-up');
            lucide.createIcons();
        }
    }

    document.addEventListener('DOMContentLoaded', async () => {
        buildSettingsCollapsibles();
        await initSettingsPrivacy();
        setTimeout(focusScopedEditorFromQuery, 300);
    });
</script>

<?php include 'includes/footer.php'; ?>

