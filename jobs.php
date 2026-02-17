<?php
// jobs.php
session_start();
#require_once 'includes/auth_check.php';

$pageTitle = "Jobs & Opportunities - RJIT Alumni Portal";
include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Jobs & Opportunities</h1>
        <p class="text-gray-600">Find job postings from alumni and companies</p>
    </div>
    
    <!-- Stats & Filters -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i data-lucide="briefcase" class="h-6 w-6 text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Active Jobs</p>
                    <p class="text-2xl font-bold text-gray-900">127</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i data-lucide="users" class="h-6 w-6 text-green-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Hiring Companies</p>
                    <p class="text-2xl font-bold text-gray-900">48</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i data-lucide="map-pin" class="h-6 w-6 text-purple-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Remote Jobs</p>
                    <p class="text-2xl font-bold text-gray-900">63</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <i data-lucide="clock" class="h-6 w-6 text-yellow-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Internships</p>
                    <p class="text-2xl font-bold text-gray-900">34</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Search and Filter Bar -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-3 h-5 w-5 text-gray-400"></i>
                    <input type="text" 
                           placeholder="Search jobs by title, company, or skills..." 
                           class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div>
                <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Job Type</option>
                    <option value="fulltime">Full Time</option>
                    <option value="parttime">Part Time</option>
                    <option value="internship">Internship</option>
                    <option value="contract">Contract</option>
                </select>
            </div>
            <div>
                <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Experience Level</option>
                    <option value="entry">Entry Level</option>
                    <option value="mid">Mid Level</option>
                    <option value="senior">Senior Level</option>
                </select>
            </div>
        </div>
        
        <div class="flex flex-wrap gap-2 mt-4">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800">
                Remote <button class="ml-1"><i data-lucide="x" class="h-3 w-3"></i></button>
            </span>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-green-100 text-green-800">
                Tech <button class="ml-1"><i data-lucide="x" class="h-3 w-3"></i></button>
            </span>
            <button class="text-blue-600 text-sm hover:text-blue-800">Clear all filters</button>
        </div>
    </div>
    
    <!-- Job Listings -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Job Card 1 -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Full Time</span>
                        <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 ml-2">Remote</span>
                    </div>
                    <button class="text-gray-400 hover:text-red-500">
                        <i data-lucide="bookmark" class="h-5 w-5"></i>
                    </button>
                </div>
                
                <div class="flex items-center mb-4">
                    <img src="https://via.placeholder.com/48" alt="Google" class="h-12 w-12 rounded-lg">
                    <div class="ml-4">
                        <h3 class="font-bold text-gray-900">Senior Software Engineer</h3>
                        <p class="text-gray-600">Google · Mountain View, CA</p>
                    </div>
                </div>
                
                <p class="text-gray-700 mb-4">Looking for experienced engineers to work on Google Cloud Platform. Must have 5+ years of experience.</p>
                
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="px-3 py-1 bg-gray-100 text-gray-800 text-sm rounded-full">Java</span>
                    <span class="px-3 py-1 bg-gray-100 text-gray-800 text-sm rounded-full">Python</span>
                    <span class="px-3 py-1 bg-gray-100 text-gray-800 text-sm rounded-full">AWS</span>
                </div>
                
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Posted 2 days ago</p>
                    </div>
                    <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        Apply Now
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Job Card 2 -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Internship</span>
                    </div>
                    <button class="text-gray-400 hover:text-red-500">
                        <i data-lucide="bookmark" class="h-5 w-5"></i>
                    </button>
                </div>
                
                <div class="flex items-center mb-4">
                    <img src="https://via.placeholder.com/48" alt="Microsoft" class="h-12 w-12 rounded-lg">
                    <div class="ml-4">
                        <h3 class="font-bold text-gray-900">Software Development Intern</h3>
                        <p class="text-gray-600">Microsoft · Redmond, WA</p>
                    </div>
                </div>
                
                <p class="text-gray-700 mb-4">Summer internship for computer science students. Work on cutting-edge projects with mentorship.</p>
                
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="px-3 py-1 bg-gray-100 text-gray-800 text-sm rounded-full">C#</span>
                    <span class="px-3 py-1 bg-gray-100 text-gray-800 text-sm rounded-full">.NET</span>
                    <span class="px-3 py-1 bg-gray-100 text-gray-800 text-sm rounded-full">Azure</span>
                </div>
                
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Posted 1 week ago</p>
                    </div>
                    <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        Apply Now
                    </button>
                </div>
            </div>
        </div>
        
        <!-- More job cards would go here -->
    </div>
    
    <!-- Post Job Button -->
    <div class="mt-8 text-center">
        <button class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 font-semibold flex items-center mx-auto">
            <i data-lucide="plus" class="h-5 w-5 mr-2"></i>
            Post a Job Opportunity
        </button>
    </div>
</div>

<?php include 'includes/footer.php'; ?>