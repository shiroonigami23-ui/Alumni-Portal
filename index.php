<?php
// If user is already logged in, redirect to dashboard
session_start();
if (isset($_COOKIE['jwt_token']) || isset($_SESSION['jwt_token'])) {
    header('Location: dashboard.php');
    exit();
}

$landingStats = [
    'alumni_network' => 0,
    'active_members' => 0,
    'total_posts' => 0,
    'companies' => 0,
    'linkedin_profiles' => 0,
    'branches' => 0,
];
$publicAlumniPhotos = [];

function normalize_public_image_url($value)
{
    $url = trim((string)$value);
    if ($url === '') return '';
    $url = str_replace('\\', '/', $url);
    if (strpos($url, 'data:') === 0) return $url;
    if (preg_match('#^(https?:)?//#i', $url)) return $url;
    return ltrim($url, './');
}

try {
    require_once __DIR__ . '/config/Database.php';
    require_once __DIR__ . '/config/DbCompat.php';
    $database = new Database();
    $conn = $database->getConnection();

    if ($conn instanceof PDO) {
        $stmt = $conn->query("
            SELECT
                (SELECT COUNT(*) FROM users WHERE role = 'alumni' AND status = 'active') AS alumni_network,
                (SELECT COUNT(*) FROM users WHERE status = 'active') AS active_members,
                (SELECT COUNT(*) FROM posts WHERE status = 'published') AS total_posts,
                (SELECT COUNT(DISTINCT p.current_company)
                 FROM profiles p
                 INNER JOIN users u ON u.user_id = p.user_id
                 WHERE u.role = 'alumni'
                   AND COALESCE(TRIM(p.current_company), '') <> '') AS companies,
                (SELECT COUNT(*)
                 FROM profiles p
                 INNER JOIN users u ON u.user_id = p.user_id
                 WHERE u.role = 'alumni'
                   AND COALESCE(TRIM(p.linkedin_url), '') <> '') AS linkedin_profiles,
                (SELECT COUNT(DISTINCT p.branch)
                 FROM profiles p
                 INNER JOIN users u ON u.user_id = p.user_id
                 WHERE u.role = 'alumni'
                   AND COALESCE(TRIM(p.branch), '') <> '') AS branches
        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $landingStats = [
                'alumni_network' => (int) ($row['alumni_network'] ?? 0),
                'active_members' => (int) ($row['active_members'] ?? 0),
                'total_posts' => (int) ($row['total_posts'] ?? 0),
                'companies' => (int) ($row['companies'] ?? 0),
                'linkedin_profiles' => (int) ($row['linkedin_profiles'] ?? 0),
                'branches' => (int) ($row['branches'] ?? 0),
            ];
        }

        $photoOrderExpr = db_is_mysql($conn)
            ? "ORDER BY (p.updated_at IS NULL), p.updated_at DESC, p.profile_id DESC"
            : "ORDER BY p.updated_at DESC NULLS LAST, p.profile_id DESC";
        $photoStmt = $conn->query("
            SELECT
                p.full_name,
                p.branch,
                p.graduation_year,
                p.profile_picture_url
            FROM profiles p
            INNER JOIN users u ON u.user_id = p.user_id
            WHERE u.role = 'alumni'
              AND u.status = 'active'
              AND COALESCE(p.is_private, FALSE) = FALSE
              AND COALESCE(TRIM(p.profile_picture_url), '') <> ''
            {$photoOrderExpr}
            LIMIT 24
        ");
        $publicAlumniPhotos = $photoStmt ? ($photoStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }
} catch (Throwable $e) {
    // Keep graceful fallbacks if DB is unavailable.
}

if (($landingStats['alumni_network'] ?? 0) <= 0) {
    $featuredPath = __DIR__ . '/storage/featured_alumni/featured_alumni.json';
    if (is_file($featuredPath)) {
        $featured = json_decode((string)file_get_contents($featuredPath), true);
        if (is_array($featured)) {
            $landingStats['alumni_network'] = max($landingStats['alumni_network'], count($featured));
            $companies = [];
            $branches = [];
            foreach ($featured as $row) {
                $company = trim((string)($row['company'] ?? ''));
                $branch = trim((string)($row['branch'] ?? ''));
                if ($company !== '') $companies[$company] = true;
                if ($branch !== '') $branches[$branch] = true;
            }
            $landingStats['companies'] = max($landingStats['companies'], count($companies));
            $landingStats['branches'] = max($landingStats['branches'], count($branches));
        }
    }
}

if (($landingStats['total_posts'] ?? 0) <= 0) {
    $fallbackPostsPath = __DIR__ . '/fallback_content/posts.json';
    if (is_file($fallbackPostsPath)) {
        $fallbackPosts = json_decode((string)file_get_contents($fallbackPostsPath), true);
        if (is_array($fallbackPosts)) {
            $landingStats['total_posts'] = max($landingStats['total_posts'], count($fallbackPosts));
        }
    }
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
    <link rel="stylesheet" href="assets/css/variety-ui.css">
    <script src="assets/js/variety-ui.js" defer></script>

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
            <div class="flex justify-between items-center h-16 gap-3">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center">
                        <i data-lucide="graduation-cap" class="h-8 w-8 text-blue-600"></i>
                        <span class="ml-2 text-base sm:text-xl font-bold text-gray-900">RJIT Alumni Portal</span>
                    </a>
                </div>
                <div class="flex items-center gap-2 sm:gap-4">
                    <button id="themeModeBtn" class="vu-theme-toggle inline-flex" type="button">
                        <i data-lucide="palette" class="h-4 w-4"></i>
                    </button>
                    <a href="login.php" class="text-sm sm:text-base text-gray-700 hover:text-blue-600 font-medium px-2 py-1">Login</a>
                    <a href="register.php" class="text-sm sm:text-base bg-blue-600 text-white px-3 sm:px-4 py-2 rounded-lg hover:bg-blue-700 font-medium">Register</a>
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
                <div class="mb-8 inline-flex flex-wrap items-center justify-center gap-3 text-sm md:text-base">
                    <span class="px-3 py-1 rounded-full bg-white/15 border border-white/30">
                        <?php echo number_format($landingStats['alumni_network']); ?> Active Alumni
                    </span>
                    <span class="px-3 py-1 rounded-full bg-white/15 border border-white/30">
                        <?php echo number_format($landingStats['branches']); ?> Branches
                    </span>
                    <span class="px-3 py-1 rounded-full bg-white/15 border border-white/30">
                        <?php echo number_format($landingStats['linkedin_profiles']); ?> LinkedIn Profiles
                    </span>
                </div>
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
                    <div class="text-3xl font-bold text-blue-600 mb-2 stats-counter" id="totalUsers"><?php echo number_format($landingStats['alumni_network']); ?></div>
                    <div class="text-gray-600">Alumni Network</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-blue-600 mb-2 stats-counter" id="activeUsers"><?php echo number_format($landingStats['active_members']); ?></div>
                    <div class="text-gray-600">Active Members</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-blue-600 mb-2 stats-counter" id="totalPosts"><?php echo number_format($landingStats['total_posts']); ?></div>
                    <div class="text-gray-600">Community Posts</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-blue-600 mb-2 stats-counter" id="companies"><?php echo number_format($landingStats['companies']); ?></div>
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
            <h2 class="text-3xl font-bold text-center mb-10">Our Alumni Community</h2>

            <div id="alumniGrid" class="alumni-grid grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
                <?php if (!empty($publicAlumniPhotos)): ?>
                    <?php foreach ($publicAlumniPhotos as $person): ?>
                        <?php
                        $img = normalize_public_image_url($person['profile_picture_url'] ?? '');
                        if ($img === '') continue;
                        $name = trim((string)($person['full_name'] ?? 'RJIT Alumni'));
                        $branch = trim((string)($person['branch'] ?? ''));
                        $year = trim((string)($person['graduation_year'] ?? ''));
                        ?>
                        <div class="group relative aspect-square rounded-xl overflow-hidden bg-white shadow-sm border border-gray-100">
                            <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>"
                                 alt="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>"
                                 class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                 loading="lazy"
                                 referrerpolicy="no-referrer">
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-2 sm:p-3">
                                <p class="text-white text-xs sm:text-sm font-semibold leading-tight truncate"><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="text-blue-100 text-[11px] sm:text-xs leading-tight truncate">
                                    <?php echo htmlspecialchars($branch, ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if ($branch !== '' && $year !== ''): ?> • <?php endif; ?>
                                    <?php echo $year !== '' ? ('Class of ' . htmlspecialchars($year, ENT_QUOTES, 'UTF-8')) : ''; ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-2 sm:col-span-3 lg:col-span-6 text-center py-10">
                        <i data-lucide="users" class="h-10 w-10 text-blue-500 mx-auto mb-3"></i>
                        <p class="text-gray-600">Public alumni photos will appear here as members upload them.</p>
                    </div>
                <?php endif; ?>
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
        window.LANDING_STATS = <?php echo json_encode($landingStats, JSON_UNESCAPED_SLASHES); ?>;

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

                element.textContent = current.toLocaleString();

                if (frame === totalFrames) {
                    clearInterval(counter);
                }
            }, frameDuration);
        }

        // Load dynamic content
        document.addEventListener('DOMContentLoaded', async function() {
            // Animate stats
            setTimeout(() => {
                animateCounter(document.getElementById('totalUsers'), window.LANDING_STATS.alumni_network || 0);
                animateCounter(document.getElementById('activeUsers'), window.LANDING_STATS.active_members || 0);
                animateCounter(document.getElementById('totalPosts'), window.LANDING_STATS.total_posts || 0);
                animateCounter(document.getElementById('companies'), window.LANDING_STATS.companies || 0);
            }, 500);

            // Load featured alumni
            await loadFeaturedAlumni();

            // Load public feed
            await loadPublicFeed();
        });

        async function loadFeaturedAlumni() {
            const container = document.getElementById('featuredAlumni');
            try {
                const response = await fetch('api/featured_alumni.php?limit=8');
                const data = await response.json();

                if (data && data.success && data.data && data.data.length > 0) {
                    container.innerHTML = '';

                    data.data.slice(0, 4).forEach(alumni => {
                        const alumniCard = document.createElement('div');
                        alumniCard.className = 'bg-white rounded-xl shadow-sm overflow-hidden card-hover';

                        const primaryImage = (alumni.images && alumni.images.length > 0) ? alumni.images[0] : null;
                        const photosCount = Array.isArray(alumni.images) ? alumni.images.length : 0;

                        alumniCard.innerHTML = `
                            <div class="aspect-[4/3] bg-gray-100">
                                ${primaryImage ?
                                    `<img src="${primaryImage}" alt="${alumni.name}" class="w-full h-full object-cover">` :
                                    `<div class="w-full h-full flex items-center justify-center">
                                        <i data-lucide="image-off" class="h-10 w-10 text-gray-400"></i>
                                    </div>`}
                            </div>
                            <div class="p-5 text-left">
                                <h3 class="font-semibold text-gray-900 mb-1">${alumni.name}</h3>
                                <p class="text-gray-600 text-sm mb-2">${alumni.position || 'Alumni'}</p>
                                <p class="text-gray-500 text-xs">
                                    ${alumni.branch ? `${alumni.branch} &bull; ` : ''}Class of ${alumni.graduation_year || 'N/A'}
                                </p>
                                ${alumni.summary ? `<p class="text-sm text-gray-600 mt-3 line-clamp-2">${alumni.summary}</p>` : ''}
                                <div class="mt-4 flex items-center justify-between gap-2">
                                    <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                        ${alumni.company || 'RJIT Alumni'}
                                    </span>
                                    ${photosCount > 1 ? `<span class="text-xs text-gray-500">${photosCount} photos</span>` : ''}
                                </div>
                            </div>
                        `;

                        container.appendChild(alumniCard);
                    });

                    lucide.createIcons();
                } else {
                    container.innerHTML = `
                        <div class="col-span-4 text-center py-10">
                            <i data-lucide="users" class="h-8 w-8 text-blue-500 mx-auto mb-3"></i>
                            <p class="text-gray-600">Featured alumni will appear here once records are available.</p>
                        </div>
                    `;
                    lucide.createIcons();
                }
            } catch (error) {
                console.error('Error loading featured alumni:', error);
                container.innerHTML = `
                    <div class="col-span-4 text-center py-10">
                        <i data-lucide="alert-circle" class="h-8 w-8 text-red-400 mx-auto mb-3"></i>
                        <p class="text-gray-600">Could not load featured alumni right now.</p>
                    </div>
                `;
                lucide.createIcons();
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
                                        ${post.branch || 'RJIT'} â€¢ Class of ${post.graduation_year || '20XX'}
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
                                <p class="text-sm text-gray-500">${post.author_role}${post.author_email ? ` | ${post.author_email}` : ``}</p>
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
            return window.portalTime ? window.portalTime.format(dateString, 'relative') : window.formatDate(dateString, 'relative');
        }
    </script>
</body>

</html>
