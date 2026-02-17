<?php
// If user is already logged in, redirect to dashboard
session_start();
if (isset($_COOKIE['jwt_token']) || isset($_SESSION['jwt_token'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to RJIT Alumni Portal</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Roboto+Slab:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Roboto Slab', serif;
        }

        .hero-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-hover:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .alumni-grid img {
            transition: transform 0.3s ease;
        }

        .alumni-grid img:hover {
            transform: scale(1.05);
        }

        .stats-counter {
            font-feature-settings: "tnum";
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center">
                        <i data-lucide="graduation-cap" class="h-8 w-8 text-blue-600"></i>
                        <span class="ml-2 text-xl font-bold text-gray-900">RJIT Alumni Portal</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="login.php" class="text-gray-700 hover:text-blue-600 font-medium">Login</a>
                    <a href="register.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-medium">Register</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-gradient text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center">
                <h1 class="text-4xl md:text-6xl font-bold mb-6">Welcome to the RJIT Alumni Portal</h1>
                <p class="text-xl md:text-2xl mb-8 opacity-90">Connecting generations of RJITians. Share, Network, Grow.</p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <a href="register.php" class="bg-white text-blue-600 px-8 py-3 rounded-lg text-lg font-semibold hover:bg-gray-100 transition duration-300">Join Now</a>
                    <a href="#features" class="bg-transparent border-2 border-white px-8 py-3 rounded-lg text-lg font-semibold hover:bg-white hover:text-blue-600 transition duration-300">Learn More</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-3xl font-bold text-blue-600 mb-2 stats-counter" id="totalUsers">2,500+</div>
                    <div class="text-gray-600">Alumni Network</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-blue-600 mb-2 stats-counter" id="activeUsers">1,200+</div>
                    <div class="text-gray-600">Active Members</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-blue-600 mb-2 stats-counter" id="totalPosts">5,000+</div>
                    <div class="text-gray-600">Community Posts</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-blue-600 mb-2 stats-counter" id="companies">150+</div>
                    <div class="text-gray-600">Companies Represented</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-center mb-12">Why Join Our Community?</h2>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-xl shadow-sm text-center card-hover">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-6">
                        <i data-lucide="network" class="h-8 w-8 text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Network & Connect</h3>
                    <p class="text-gray-600">Connect with alumni across industries, locations, and generations. Build meaningful professional relationships.</p>
                </div>

                <div class="bg-white p-8 rounded-xl shadow-sm text-center card-hover">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-6">
                        <i data-lucide="briefcase" class="h-8 w-8 text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Career Opportunities</h3>
                    <p class="text-gray-600">Access exclusive job postings, internships, and mentorship programs from successful alumni.</p>
                </div>

                <div class="bg-white p-8 rounded-xl shadow-sm text-center card-hover">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-6">
                        <i data-lucide="users" class="h-8 w-8 text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Community Support</h3>
                    <p class="text-gray-600">Get guidance, share experiences, and contribute to the growth of current students and fellow alumni.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Alumni -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-12">
                <h2 class="text-3xl font-bold">Featured Alumni</h2>
                <a href="discovery.php" class="text-blue-600 hover:text-blue-800 font-semibold">View All Alumni</a>
            </div>

            <div id="featuredAlumni" class="grid md:grid-cols-4 gap-6">
                <!-- Featured alumni will be loaded here -->
                <div class="text-center py-8 col-span-4">
                    <i data-lucide="loader" class="h-8 w-8 animate-spin text-blue-600 mx-auto mb-4"></i>
                    <p class="text-gray-500">Loading featured alumni...</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Alumni Grid -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-center mb-12">Our Alumni Community</h2>

            <div id="alumniGrid" class="alumni-grid grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <!-- Alumni grid will be loaded here with fallback images -->
                <?php
                // Fallback images from storage
                $fallbackImages = [];

                // Check for existing profile images in storage
                $storagePath = 'storage/profiles/';
                if (is_dir($storagePath)) {
                    $files = scandir($storagePath);
                    foreach ($files as $file) {
                        if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $file)) {
                            $fallbackImages[] = $storagePath . $file;
                            if (count($fallbackImages) >= 12) break;
                        }
                    }
                }

                // Add default fallbacks if needed
                $defaultImages = [
                    'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="150" height="150" viewBox="0 0 150 150"%3E%3Crect width="150" height="150" fill="%23dbeafe"/%3E%3Ctext x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="20" fill="%233b82f6"%3EAlumni%3C/text%3E%3C/svg%3E',
                    'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="150" height="150" viewBox="0 0 150 150"%3E%3Crect width="150" height="150" fill="%23dbeafe"/%3E%3Ctext x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="20" fill="%233b82f6"%3ERJIT%3C/text%3E%3C/svg%3E'
                ];

                // Display 12 images total
                for ($i = 0; $i < 12; $i++):
                    if (isset($fallbackImages[$i])) {
                        $imageSrc = $fallbackImages[$i];
                        $altText = 'Alumni Profile';
                    } else {
                        $imageSrc = $defaultImages[$i % count($defaultImages)];
                        $altText = 'Alumni Member';
                    }
                ?>
                    <div class="aspect-square rounded-lg overflow-hidden bg-white shadow-sm">
                        <img src="<?php echo $imageSrc; ?>"
                            alt="<?php echo $altText; ?>"
                            class="w-full h-full object-cover"
                            loading="lazy">
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </section>

    <!-- Recent Posts -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-center mb-12">Recent Community Updates</h2>

            <div id="publicFeed" class="grid md:grid-cols-3 gap-6">
                <!-- Posts will be loaded here -->
                <div class="text-center py-12 col-span-3">
                    <i data-lucide="loader" class="h-8 w-8 animate-spin text-blue-600 mx-auto mb-4"></i>
                    <p class="text-gray-500">Loading community updates...</p>
                </div>
            </div>

            <div class="text-center mt-12">
                <a href="login.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-semibold">
                    <span>View full community feed</span>
                    <i data-lucide="arrow-right" class="h-4 w-4 ml-2"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-blue-600 text-white py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold mb-6">Ready to Join Our Community?</h2>
            <p class="text-xl mb-8 opacity-90">Thousands of RJIT alumni are already connecting, sharing, and growing together.</p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="register.php" class="bg-white text-blue-600 px-8 py-3 rounded-lg text-lg font-semibold hover:bg-gray-100 transition duration-300">Register Now</a>
                <a href="login.php" class="bg-transparent border-2 border-white px-8 py-3 rounded-lg text-lg font-semibold hover:bg-white hover:text-blue-600 transition duration-300">Login</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        <i data-lucide="graduation-cap" class="h-8 w-8 text-blue-400"></i>
                        <span class="ml-2 text-xl font-bold">RJIT Alumni</span>
                    </div>
                    <p class="text-gray-400">Connecting RJITians across generations and geographies.</p>
                </div>

                <div>
                    <h3 class="font-semibold text-lg mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="login.php" class="text-gray-400 hover:text-white">Login</a></li>
                        <li><a href="register.php" class="text-gray-400 hover:text-white">Register</a></li>
                        <li><a href="discovery.php" class="text-gray-400 hover:text-white">Find Alumni</a></li>
                        <li><a href="help.php" class="text-gray-400 hover:text-white">Help Center</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-semibold text-lg mb-4">Legal</h3>
                    <ul class="space-y-2">
                        <li><a href="policy.php" class="text-gray-400 hover:text-white">Privacy Policy</a></li>
                        <li><a href="terms.php" class="text-gray-400 hover:text-white">Terms of Service</a></li>
                        <li><a href="conduct.php" class="text-gray-400 hover:text-white">Code of Conduct</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-semibold text-lg mb-4">Contact</h3>
                    <ul class="space-y-2">
                        <li class="text-gray-400">alumni@rjit.ac.in</li>
                        <li class="text-gray-400">+91 XXX XXX XXXX</li>
                        <li class="text-gray-400">RJIT Campus, Bhopal</li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; <?php echo date('Y'); ?> RJIT Alumni Portal. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Animate counters
        function animateCounter(element, target) {
            const duration = 2000;
            const frameDuration = 1000 / 60;
            const totalFrames = Math.round(duration / frameDuration);
            let frame = 0;

            const counter = setInterval(() => {
                frame++;
                const progress = frame / totalFrames;
                const current = Math.round(target * progress);

                element.textContent = current.toLocaleString() + '+';

                if (frame === totalFrames) {
                    clearInterval(counter);
                }
            }, frameDuration);
        }

        // Load dynamic content
        document.addEventListener('DOMContentLoaded', async function() {
            // Animate stats
            setTimeout(() => {
                animateCounter(document.getElementById('totalUsers'), 2500);
                animateCounter(document.getElementById('activeUsers'), 1200);
                animateCounter(document.getElementById('totalPosts'), 5000);
                animateCounter(document.getElementById('companies'), 150);
            }, 500);

            // Load featured alumni
            await loadFeaturedAlumni();

            // Load public feed
            await loadPublicFeed();
        });

        async function loadFeaturedAlumni() {
            try {
                const response = await fetch('api/public_feed.php?type=featured_alumni');
                const data = await response.json();

                const container = document.getElementById('featuredAlumni');

                if (data && data.success && data.data && data.data.length > 0) {
                    container.innerHTML = '';

                    data.data.slice(0, 4).forEach(alumni => {
                        const alumniCard = document.createElement('div');
                        alumniCard.className = 'bg-white rounded-xl shadow-sm p-6 text-center card-hover';

                        alumniCard.innerHTML = `
                            <div class="mb-4">
                                ${alumni.avatar ? 
                                    `<img src="${alumni.avatar}" alt="${alumni.name}" class="h-20 w-20 rounded-full object-cover mx-auto">` : 
                                    `<div class="h-20 w-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto">
                                        <i data-lucide="user" class="h-10 w-10 text-blue-600"></i>
                                    </div>`}
                            </div>
                            <h3 class="font-semibold text-gray-900 mb-1">${alumni.name}</h3>
                            <p class="text-gray-600 text-sm mb-2">${alumni.position || 'Alumni'}</p>
                            <p class="text-gray-500 text-xs">
                                ${alumni.branch ? `${alumni.branch} • ` : ''}Class of ${alumni.graduation_year || '20XX'}
                            </p>
                            <div class="mt-4">
                                <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                    ${alumni.company || 'RJIT Alumni'}
                                </span>
                            </div>
                        `;

                        container.appendChild(alumniCard);
                    });

                    lucide.createIcons();
                } else {
                    // Show fallback featured alumni
                    container.innerHTML = `
                        <div class="bg-white rounded-xl shadow-sm p-6 text-center card-hover">
                            <div class="mb-4">
                                <div class="h-20 w-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto">
                                    <i data-lucide="user" class="h-10 w-10 text-blue-600"></i>
                                </div>
                            </div>
                            <h3 class="font-semibold text-gray-900 mb-1">Dr. Rajesh Kumar</h3>
                            <p class="text-gray-600 text-sm mb-2">Senior Software Engineer</p>
                            <p class="text-gray-500 text-xs">CSE • Class of 2010</p>
                            <div class="mt-4">
                                <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                    Google
                                </span>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm p-6 text-center card-hover">
                            <div class="mb-4">
                                <div class="h-20 w-20 bg-green-100 rounded-full flex items-center justify-center mx-auto">
                                    <i data-lucide="user" class="h-10 w-10 text-green-600"></i>
                                </div>
                            </div>
                            <h3 class="font-semibold text-gray-900 mb-1">Priya Sharma</h3>
                            <p class="text-gray-600 text-sm mb-2">Product Manager</p>
                            <p class="text-gray-500 text-xs">IT • Class of 2015</p>
                            <div class="mt-4">
                                <span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                    Microsoft
                                </span>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm p-6 text-center card-hover">
                            <div class="mb-4">
                                <div class="h-20 w-20 bg-purple-100 rounded-full flex items-center justify-center mx-auto">
                                    <i data-lucide="user" class="h-10 w-10 text-purple-600"></i>
                                </div>
                            </div>
                            <h3 class="font-semibold text-gray-900 mb-1">Amit Patel</h3>
                            <p class="text-gray-600 text-sm mb-2">Data Scientist</p>
                            <p class="text-gray-500 text-xs">ECE • Class of 2018</p>
                            <div class="mt-4">
                                <span class="inline-block px-3 py-1 bg-purple-100 text-purple-800 text-xs rounded-full">
                                    Amazon
                                </span>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm p-6 text-center card-hover">
                            <div class="mb-4">
                                <div class="h-20 w-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto">
                                    <i data-lucide="user" class="h-10 w-10 text-amber-600"></i>
                                </div>
                            </div>
                            <h3 class="font-semibold text-gray-900 mb-1">Prof. Sunita Verma</h3>
                            <p class="text-gray-600 text-sm mb-2">Faculty - CSE Department</p>
                            <p class="text-gray-500 text-xs">RJIT Faculty</p>
                            <div class="mt-4">
                                <span class="inline-block px-3 py-1 bg-amber-100 text-amber-800 text-xs rounded-full">
                                    RJIT Faculty
                                </span>
                            </div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading featured alumni:', error);
            }
        }

        async function loadPublicFeed() {
            try {
                const response = await fetch('api/public_feed.php');
                const data = await response.json();

                const container = document.getElementById('publicFeed');

                if (data && data.success && data.data && data.data.length > 0) {
                    container.innerHTML = '';

                    data.data.slice(0, 6).forEach(post => {
                        // Fetch content from file if available
                        let content = post.content_preview || 'Shared an update with the community...';
                        if (post.content_file_path) {
                            // In a real implementation, you would fetch this asynchronously
                            content = content.length > 150 ? content.substring(0, 150) + '...' : content;
                        }

                        const postCard = document.createElement('div');
                        postCard.className = 'bg-white rounded-xl shadow-sm p-6 card-hover';

                        postCard.innerHTML = `
                            <div class="flex items-start mb-4">
                                <div class="h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                    ${post.author_avatar ? 
                                        `<img src="${post.author_avatar}" alt="${post.author_name}" class="h-10 w-10 rounded-full object-cover">` : 
                                        `<i data-lucide="user" class="h-5 w-5 text-blue-600"></i>`}
                                </div>
                                <div>
                                    <h4 class="font-semibold">${post.author_name || 'Alumni'}</h4>
                                    <p class="text-sm text-gray-500">
                                        ${post.branch || 'RJIT'} • Class of ${post.graduation_year || '20XX'}
                                    </p>
                                </div>
                            </div>
                            <p class="text-gray-700 mb-4 line-clamp-3">${content}</p>
                            <div class="flex items-center text-sm text-gray-500">
                                <i data-lucide="heart" class="h-4 w-4 mr-1"></i>
                                <span class="mr-4">${post.likes_count || 0}</span>
                                <i data-lucide="message-square" class="h-4 w-4 mr-1"></i>
                                <span>${post.comments_count || 0}</span>
                                <span class="ml-auto text-xs">${formatDate(post.created_at)}</span>
                            </div>
                        `;

                        container.appendChild(postCard);
                    });

                    lucide.createIcons();
                } else {
                    // Load fallback posts from JSON
                    await loadFallbackPosts(container);
                }
            } catch (error) {
                console.error('Error loading public feed:', error);
                // Try loading fallback posts on error
                try {
                    await loadFallbackPosts(document.getElementById('publicFeed'));
                } catch (fallbackError) {
                    console.error('Error loading fallback posts:', fallbackError);
                    container.innerHTML = `
                        <div class="col-span-3 text-center py-12">
                            <i data-lucide="alert-circle" class="h-12 w-12 text-red-300 mx-auto mb-4"></i>
                            <p class="text-gray-500">Unable to load updates. Please try again later.</p>
                        </div>
                    `;
                }
            }
        }

        async function loadFallbackPosts(container) {
            try {
                const response = await fetch('fallback_content/posts.json');
                const fallbackPosts = await response.json();

                container.innerHTML = '';

                fallbackPosts.slice(0, 3).forEach(post => {
                    const postCard = document.createElement('div');
                    postCard.className = 'bg-white rounded-xl shadow-sm p-6 card-hover';

                    postCard.innerHTML = `
                        <div class="flex items-start mb-4">
                            <div class="h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                ${post.author_profile_picture_url ? 
                                    `<img src="${post.author_profile_picture_url}" alt="${post.author_name}" class="h-10 w-10 rounded-full object-cover">` : 
                                    `<i data-lucide="user" class="h-5 w-5 text-blue-600"></i>`}
                            </div>
                            <div>
                                <h4 class="font-semibold">${post.author_name}</h4>
                                <p class="text-sm text-gray-500">${post.author_role}</p>
                            </div>
                        </div>
                        ${post.image_url ? `<img src="${post.image_url}" alt="Post image" class="w-full rounded-lg mb-4 object-cover" style="max-height: 200px;">` : ''}
                        <p class="text-gray-700 mb-4 whitespace-pre-line">${post.content.length > 200 ? post.content.substring(0, 200) + '...' : post.content}</p>
                        <div class="flex items-center text-sm text-gray-500">
                            <i data-lucide="heart" class="h-4 w-4 mr-1"></i>
                            <span class="mr-4">${post.likes_count}</span>
                            <i data-lucide="message-square" class="h-4 w-4 mr-1"></i>
                            <span>${post.comments_count}</span>
                            <span class="ml-auto text-xs">${formatDate(post.created_at)}</span>
                        </div>
                    `;

                    container.appendChild(postCard);
                });

                lucide.createIcons();
            } catch (error) {
                console.error('Error loading fallback posts:', error);
                // Show basic fallback if JSON fails
                container.innerHTML = `
                    <div class="col-span-3 text-center py-12">
                        <i data-lucide="newspaper" class="h-12 w-12 text-gray-300 mx-auto mb-4"></i>
                        <p class="text-gray-500">No posts available yet. Join the community to start sharing!</p>
                    </div>
                `;
                lucide.createIcons();
            }
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 60) {
                return `${diffMins}m ago`;
            } else if (diffHours < 24) {
                return `${diffHours}h ago`;
            } else if (diffDays < 7) {
                return `${diffDays}d ago`;
            } else {
                return date.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric'
                });
            }
        }
    </script>
</body>

</html>