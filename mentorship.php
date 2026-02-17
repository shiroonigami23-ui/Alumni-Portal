<?php
// mentorship.php
session_start();
#require_once 'includes/auth_check.php';

$pageTitle = "Mentorship - RJIT Alumni Portal";
include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-8 text-white mb-8">
        <div class="max-w-3xl">
            <h1 class="text-3xl font-bold mb-4">Mentorship Program</h1>
            <p class="text-lg mb-6">Connect with experienced alumni mentors to guide your career journey. Whether you're a student seeking guidance or an alumnus willing to help, this is the place for meaningful connections.</p>
            <div class="flex flex-wrap gap-4">
                <button class="bg-white text-blue-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100">
                    Find a Mentor
                </button>
                <button class="bg-transparent border-2 border-white text-white px-6 py-3 rounded-lg font-semibold hover:bg-white/10">
                    Become a Mentor
                </button>
            </div>
        </div>
    </div>
    
    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 text-center">
            <div class="text-3xl font-bold text-blue-600 mb-2">245+</div>
            <p class="text-gray-700">Active Mentors</p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 text-center">
            <div class="text-3xl font-bold text-green-600 mb-2">1,200+</div>
            <p class="text-gray-700">Mentees Connected</p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 text-center">
            <div class="text-3xl font-bold text-purple-600 mb-2">98%</div>
            <p class="text-gray-700">Satisfaction Rate</p>
        </div>
    </div>
    
    <!-- Mentor Categories -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Browse by Expertise</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 text-center hover:shadow-md cursor-pointer">
                <div class="p-3 bg-blue-100 rounded-lg inline-block mb-3">
                    <i data-lucide="code" class="h-6 w-6 text-blue-600"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1">Technology</h3>
                <p class="text-sm text-gray-600">86 Mentors</p>
            </div>
            
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 text-center hover:shadow-md cursor-pointer">
                <div class="p-3 bg-green-100 rounded-lg inline-block mb-3">
                    <i data-lucide="briefcase" class="h-6 w-6 text-green-600"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1">Business</h3>
                <p class="text-sm text-gray-600">42 Mentors</p>
            </div>
            
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 text-center hover:shadow-md cursor-pointer">
                <div class="p-3 bg-purple-100 rounded-lg inline-block mb-3">
                    <i data-lucide="heart" class="h-6 w-6 text-purple-600"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1">Healthcare</h3>
                <p class="text-sm text-gray-600">31 Mentors</p>
            </div>
            
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 text-center hover:shadow-md cursor-pointer">
                <div class="p-3 bg-yellow-100 rounded-lg inline-block mb-3">
                    <i data-lucide="graduation-cap" class="h-6 w-6 text-yellow-600"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1">Academics</h3>
                <p class="text-sm text-gray-600">28 Mentors</p>
            </div>
        </div>
    </div>
    
    <!-- Featured Mentors -->
    <div class="mb-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Featured Mentors</h2>
            <a href="#" class="text-blue-600 hover:text-blue-800 font-semibold">View all →</a>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Mentor Card 1 -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <img src="https://via.placeholder.com/60" alt="Mentor" class="h-16 w-16 rounded-full">
                        <div class="ml-4">
                            <h3 class="font-bold text-gray-900">Dr. Rajesh Kumar</h3>
                            <p class="text-gray-600">VP Engineering @ Google</p>
                        </div>
                    </div>
                    
                    <p class="text-gray-700 mb-4">20+ years in software engineering. Specialized in cloud architecture and leadership.</p>
                    
                    <div class="flex flex-wrap gap-2 mb-4">
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full">Cloud</span>
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full">Leadership</span>
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full">AI/ML</span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center text-yellow-500">
                            <i data-lucide="star" class="h-4 w-4 fill-current"></i>
                            <i data-lucide="star" class="h-4 w-4 fill-current"></i>
                            <i data-lucide="star" class="h-4 w-4 fill-current"></i>
                            <i data-lucide="star" class="h-4 w-4 fill-current"></i>
                            <i data-lucide="star" class="h-4 w-4 fill-current"></i>
                            <span class="ml-2 text-gray-600">(42)</span>
                        </div>
                        <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            Connect
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Mentor Card 2 -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <img src="https://via.placeholder.com/60" alt="Mentor" class="h-16 w-16 rounded-full">
                        <div class="ml-4">
                            <h3 class="font-bold text-gray-900">Priya Sharma</h3>
                            <p class="text-gray-600">Product Manager @ Microsoft</p>
                        </div>
                    </div>
                    
                    <p class="text-gray-700 mb-4">Expert in product management, UX design, and agile methodologies.</p>
                    
                    <div class="flex flex-wrap gap-2 mb-4">
                        <span class="px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full">Product</span>
                        <span class="px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full">UX</span>
                        <span class="px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full">Agile</span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center text-yellow-500">
                            <i data-lucide="star" class="h-4 w-4 fill-current"></i>
                            <i data-lucide="star" class="h-4 w-4 fill-current"></i>
                            <i data-lucide="star" class="h-4 w-4 fill-current"></i>
                            <i data-lucide="star" class="h-4 w-4 fill-current"></i>
                            <i data-lucide="star" class="h-4 w-4 fill-current"></i>
                            <span class="ml-2 text-gray-600">(38)</span>
                        </div>
                        <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            Connect
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Mentor Card 3 -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <img src="https://via.placeholder.com/60" alt="Mentor" class="h-16 w-16 rounded-full">
                        <div class="ml-4">
                            <h3 class="font-bold text-gray-900">Amit Patel</h3>
                            <p class="text-gray-600">Startup Founder @ TechVentures</p>
                        </div>
                    </div>
                    
                    <p class="text-gray-700 mb-4">Entrepreneur with 3 successful exits. Mentoring in startups and fundraising.</p>
                    
                    <div class="flex flex-wrap gap-2 mb-4">
                        <span class="px-3 py-1 bg-purple-100 text-purple-800 text-sm rounded-full">Startups</span>
                        <span class="px-3 py-1 bg-purple-100 text-purple-800 text-sm rounded-full">VC</span>
                        <span class="px-3 py-1 bg-purple-100 text-purple-800 text-sm rounded-full">Strategy</span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center text-yellow-500">
                            <i data-lucide="star" class="h-4 w-4 fill-current"></i>
                            <i data-lucide="star" class="h-4 w-4 fill-current"></i>
                            <i data-lucide="star" class="h-4 w-4 fill-current"></i>
                            <i data-lucide="star" class="h-4 w-4 fill-current"></i>
                            <i data-lucide="star" class="h-4 w-4 fill-current"></i>
                            <span class="ml-2 text-gray-600">(56)</span>
                        </div>
                        <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            Connect
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- How It Works -->
    <div class="bg-gray-50 rounded-2xl p-8 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">How Mentorship Works</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="bg-white rounded-full h-16 w-16 flex items-center justify-center mx-auto mb-4 shadow-sm">
                    <i data-lucide="search" class="h-8 w-8 text-blue-600"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Find a Mentor</h3>
                <p class="text-gray-600">Browse profiles based on industry, expertise, and availability.</p>
            </div>
            <div class="text-center">
                <div class="bg-white rounded-full h-16 w-16 flex items-center justify-center mx-auto mb-4 shadow-sm">
                    <i data-lucide="message-square" class="h-8 w-8 text-green-600"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Connect & Schedule</h3>
                <p class="text-gray-600">Send connection requests and schedule sessions via the platform.</p>
            </div>
            <div class="text-center">
                <div class="bg-white rounded-full h-16 w-16 flex items-center justify-center mx-auto mb-4 shadow-sm">
                    <i data-lucide="trending-up" class="h-8 w-8 text-purple-600"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Grow Together</h3>
                <p class="text-gray-600">Regular sessions, feedback, and career guidance.</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>