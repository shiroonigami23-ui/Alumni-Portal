<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center - RJIT Alumni Portal</title>

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
            scroll-behavior: smooth;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Roboto Slab', serif;
        }

        .faq-item {
            transition: all 0.3s ease;
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease, padding 0.4s ease;
        }

        .faq-answer.open {
            max-height: 500px;
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

        .faq-icon {
            transition: transform 0.3s ease;
        }

        .faq-icon.rotate {
            transform: rotate(180deg);
        }

        .category-pill {
            transition: all 0.2s ease;
        }

        .category-pill:hover {
            transform: translateY(-2px);
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .floating-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 50;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .floating-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php
    // If user is logged in, show different navigation
    session_start();
    $isLoggedIn = isset($_COOKIE['jwt_token']) || isset($_SESSION['jwt_token']);
    ?>

    <!-- Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center">
                        <i data-lucide="graduation-cap" class="h-8 w-8 text-blue-600"></i>
                        <span class="ml-2 text-xl font-bold text-gray-900">RJIT Alumni Portal</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-700 hover:text-blue-600 font-medium">Home</a>
                    <?php if ($isLoggedIn): ?>
                        <a href="dashboard.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-medium">Dashboard</a>
                    <?php else: ?>
                        <a href="login.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-medium">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Header -->
        <div class="text-center mb-12 fade-in">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full mb-6 shadow-lg">
                <i data-lucide="help-circle" class="h-10 w-10 text-white"></i>
            </div>
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Help Center</h1>
            <p class="text-xl text-gray-600">Find answers to your questions</p>
        </div>

        <!-- Search Bar -->
        <div class="mb-12 fade-in">
            <div class="relative max-w-2xl mx-auto">
                <i data-lucide="search" class="absolute left-4 top-4 h-5 w-5 text-gray-400"></i>
                <input type="text"
                    id="searchInput"
                    placeholder="Search for help articles..."
                    class="w-full pl-12 pr-4 py-4 text-lg border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm">
            </div>
        </div>

        <!-- Category Pills -->
        <div class="flex flex-wrap justify-center gap-3 mb-12 fade-in">
            <button class="category-pill px-6 py-2 bg-blue-100 text-blue-700 rounded-full font-medium hover:bg-blue-200 active" data-category="all">
                All Topics
            </button>
            <button class="category-pill px-6 py-2 bg-gray-100 text-gray-700 rounded-full font-medium hover:bg-gray-200" data-category="account">
                Account
            </button>
            <button class="category-pill px-6 py-2 bg-gray-100 text-gray-700 rounded-full font-medium hover:bg-gray-200" data-category="networking">
                Networking
            </button>
            <button class="category-pill px-6 py-2 bg-gray-100 text-gray-700 rounded-full font-medium hover:bg-gray-200" data-category="posts">
                Posts
            </button>
            <button class="category-pill px-6 py-2 bg-gray-100 text-gray-700 rounded-full font-medium hover:bg-gray-200" data-category="technical">
                Technical
            </button>
        </div>

        <!-- FAQ Accordion -->
        <div id="faqContainer" class="max-w-4xl mx-auto space-y-4">
            <!-- Account FAQs -->
            <div class="faq-item bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden fade-in" data-category="account">
                <button class="faq-question w-full px-6 py-5 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                    <span class="font-semibold text-gray-900 text-lg">How do I register as an alumni?</span>
                    <i data-lucide="chevron-down" class="faq-icon h-5 w-5 text-gray-500"></i>
                </button>
                <div class="faq-answer px-6 text-gray-700">
                    <p class="mb-3">To register as an alumni, follow these steps:</p>
                    <ol class="list-decimal pl-5 space-y-2">
                        <li>Click on the "Register" button in the top navigation</li>
                        <li>Select "Alumni" as your role</li>
                        <li>Fill in your personal information including graduation year and branch</li>
                        <li>Verify your email address through the link sent to your inbox</li>
                        <li>Complete your profile with professional information</li>
                    </ol>
                    <p class="mt-3 text-sm text-gray-600">Note: You'll need a valid RJIT email or graduation certificate for verification.</p>
                </div>
            </div>

            <div class="faq-item bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden fade-in" data-category="account">
                <button class="faq-question w-full px-6 py-5 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                    <span class="font-semibold text-gray-900 text-lg">How do I reset my password?</span>
                    <i data-lucide="chevron-down" class="faq-icon h-5 w-5 text-gray-500"></i>
                </button>
                <div class="faq-answer px-6 text-gray-700">
                    <p class="mb-3">If you've forgotten your password:</p>
                    <ol class="list-decimal pl-5 space-y-2">
                        <li>Go to the login page and click "Forgot Password?"</li>
                        <li>Enter your registered email address</li>
                        <li>Check your email for a password reset link</li>
                        <li>Click the link and create a new password</li>
                        <li>Log in with your new password</li>
                    </ol>
                    <p class="mt-3 text-sm text-gray-600">The reset link expires after 24 hours for security.</p>
                </div>
            </div>

            <div class="faq-item bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden fade-in" data-category="account">
                <button class="faq-question w-full px-6 py-5 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                    <span class="font-semibold text-gray-900 text-lg">How do I update my profile information?</span>
                    <i data-lucide="chevron-down" class="faq-icon h-5 w-5 text-gray-500"></i>
                </button>
                <div class="faq-answer px-6 text-gray-700">
                    <p class="mb-3">To update your profile:</p>
                    <ol class="list-decimal pl-5 space-y-2">
                        <li>Log in to your account</li>
                        <li>Click on your profile picture in the top right</li>
                        <li>Select "Settings" from the dropdown menu</li>
                        <li>Edit the fields you want to update</li>
                        <li>Click "Save Changes" at the bottom</li>
                    </ol>
                </div>
            </div>

            <!-- Networking FAQs -->
            <div class="faq-item bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden fade-in" data-category="networking">
                <button class="faq-question w-full px-6 py-5 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                    <span class="font-semibold text-gray-900 text-lg">How do I find and connect with other alumni?</span>
                    <i data-lucide="chevron-down" class="faq-icon h-5 w-5 text-gray-500"></i>
                </button>
                <div class="faq-answer px-6 text-gray-700">
                    <p class="mb-3">Use the Discovery feature to find alumni:</p>
                    <ol class="list-decimal pl-5 space-y-2">
                        <li>Navigate to the "Discovery" page from the sidebar</li>
                        <li>Use filters to search by graduation year, branch, company, or location</li>
                        <li>Click on a profile to view their information</li>
                        <li>Send a connection request or message</li>
                    </ol>
                    <p class="mt-3 text-sm text-gray-600">Tip: Complete your profile to appear in search results!</p>
                </div>
            </div>

            <div class="faq-item bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden fade-in" data-category="networking">
                <button class="faq-question w-full px-6 py-5 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                    <span class="font-semibold text-gray-900 text-lg">How do I send messages to other users?</span>
                    <i data-lucide="chevron-down" class="faq-icon h-5 w-5 text-gray-500"></i>
                </button>
                <div class="faq-answer px-6 text-gray-700">
                    <p class="mb-3">To send a message:</p>
                    <ol class="list-decimal pl-5 space-y-2">
                        <li>Go to the Messages page from the sidebar</li>
                        <li>Click "New Message" button</li>
                        <li>Search for the user you want to message</li>
                        <li>Select them and click "Start Chat"</li>
                        <li>Type your message and press Enter or click Send</li>
                    </ol>
                </div>
            </div>

            <!-- Posts FAQs -->
            <div class="faq-item bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden fade-in" data-category="posts">
                <button class="faq-question w-full px-6 py-5 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                    <span class="font-semibold text-gray-900 text-lg">How do I create and share posts?</span>
                    <i data-lucide="chevron-down" class="faq-icon h-5 w-5 text-gray-500"></i>
                </button>
                <div class="faq-answer px-6 text-gray-700">
                    <p class="mb-3">Creating posts is easy:</p>
                    <ol class="list-decimal pl-5 space-y-2">
                        <li>Go to the Feed page</li>
                        <li>Click on the "What's on your mind?" box</li>
                        <li>Type your message or update</li>
                        <li>Optionally add images or files</li>
                        <li>Click "Post" to share with the community</li>
                    </ol>
                    <p class="mt-3 text-sm text-gray-600">Remember to follow community guidelines when posting!</p>
                </div>
            </div>

            <div class="faq-item bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden fade-in" data-category="posts">
                <button class="faq-question w-full px-6 py-5 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                    <span class="font-semibold text-gray-900 text-lg">What file types can I upload?</span>
                    <i data-lucide="chevron-down" class="faq-icon h-5 w-5 text-gray-500"></i>
                </button>
                <div class="faq-answer px-6 text-gray-700">
                    <p class="mb-3">Supported file types:</p>
                    <ul class="list-disc pl-5 space-y-2">
                        <li><strong>Images:</strong> JPG, PNG, GIF, WebP (max 5MB each)</li>
                        <li><strong>Documents:</strong> PDF, DOC, DOCX, TXT (max 10MB each)</li>
                        <li><strong>Presentations:</strong> PPT, PPTX (max 10MB each)</li>
                    </ul>
                    <p class="mt-3 text-sm text-gray-600">Maximum 5 files per post.</p>
                </div>
            </div>

            <!-- Technical FAQs -->
            <div class="faq-item bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden fade-in" data-category="technical">
                <button class="faq-question w-full px-6 py-5 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                    <span class="font-semibold text-gray-900 text-lg">Which browsers are supported?</span>
                    <i data-lucide="chevron-down" class="faq-icon h-5 w-5 text-gray-500"></i>
                </button>
                <div class="faq-answer px-6 text-gray-700">
                    <p class="mb-3">The portal works best on modern browsers:</p>
                    <ul class="list-disc pl-5 space-y-2">
                        <li>Google Chrome (latest version)</li>
                        <li>Mozilla Firefox (latest version)</li>
                        <li>Safari (latest version)</li>
                        <li>Microsoft Edge (latest version)</li>
                    </ul>
                    <p class="mt-3 text-sm text-gray-600">For best experience, keep your browser updated.</p>
                </div>
            </div>

            <div class="faq-item bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden fade-in" data-category="technical">
                <button class="faq-question w-full px-6 py-5 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                    <span class="font-semibold text-gray-900 text-lg">How do I report a technical issue?</span>
                    <i data-lucide="chevron-down" class="faq-icon h-5 w-5 text-gray-500"></i>
                </button>
                <div class="faq-answer px-6 text-gray-700">
                    <p class="mb-3">To report technical issues:</p>
                    <ol class="list-decimal pl-5 space-y-2">
                        <li>Click the floating help button (bottom right)</li>
                        <li>Or email support@rjit.ac.in with details</li>
                        <li>Include screenshots if possible</li>
                        <li>Describe what you were trying to do</li>
                        <li>Mention your browser and device</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- No Results Message -->
        <div id="noResults" class="hidden text-center py-12">
            <i data-lucide="search-x" class="h-16 w-16 text-gray-300 mx-auto mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No results found</h3>
            <p class="text-gray-500">Try different keywords or browse all categories</p>
        </div>

        <!-- Contact Support Section -->
        <div class="mt-16 bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl p-8 text-white text-center shadow-xl fade-in">
            <i data-lucide="message-circle" class="h-12 w-12 mx-auto mb-4"></i>
            <h2 class="text-2xl font-bold mb-4">Still Need Help?</h2>
            <p class="text-blue-100 mb-6 max-w-2xl mx-auto">
                Can't find what you're looking for? Our support team is here to help you.
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="mailto:support@rjit.ac.in" class="bg-white text-blue-600 px-6 py-3 rounded-lg hover:bg-blue-50 font-semibold inline-flex items-center justify-center">
                    <i data-lucide="mail" class="h-4 w-4 mr-2"></i>
                    Email Support
                </a>
                <a href="tel:+91XXXXXXXXXX" class="bg-blue-700 text-white px-6 py-3 rounded-lg hover:bg-blue-800 font-semibold inline-flex items-center justify-center">
                    <i data-lucide="phone" class="h-4 w-4 mr-2"></i>
                    Call Support
                </a>
            </div>
            <p class="text-blue-200 text-sm mt-4">
                Response time: Usually within 24 hours
            </p>
        </div>

        <!-- Quick Links -->
        <div class="mt-8 text-center">
            <div class="inline-flex items-center space-x-6">
                <a href="terms.php" class="text-gray-600 hover:text-blue-600 transition-colors">Terms of Service</a>
                <span class="text-gray-300">•</span>
                <a href="policy.php" class="text-gray-600 hover:text-blue-600 transition-colors">Privacy Policy</a>
                <span class="text-gray-300">•</span>
                <a href="conduct.php" class="text-gray-600 hover:text-blue-600 transition-colors">Code of Conduct</a>
            </div>
        </div>
    </main>

    <!-- Floating Help Button -->
    <a href="mailto:support@rjit.ac.in" class="floating-btn bg-blue-600 text-white p-4 rounded-full">
        <i data-lucide="headphones" class="h-6 w-6"></i>
    </a>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12 mt-16">
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
                        <li><a href="index.php" class="text-gray-400 hover:text-white transition-colors">Home</a></li>
                        <li><a href="login.php" class="text-gray-400 hover:text-white transition-colors">Login</a></li>
                        <li><a href="register.php" class="text-gray-400 hover:text-white transition-colors">Register</a></li>
                        <li><a href="help.php" class="text-white font-medium">Help Center</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-semibold text-lg mb-4">Legal</h3>
                    <ul class="space-y-2">
                        <li><a href="terms.php" class="text-gray-400 hover:text-white transition-colors">Terms of Service</a></li>
                        <li><a href="policy.php" class="text-gray-400 hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="conduct.php" class="text-gray-400 hover:text-white transition-colors">Code of Conduct</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-semibold text-lg mb-4">Contact</h3>
                    <ul class="space-y-2">
                        <li class="text-gray-400">alumni@rjit.ac.in</li>
                        <li class="text-gray-400">support@rjit.ac.in</li>
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

        // FAQ Accordion functionality
        const faqQuestions = document.querySelectorAll('.faq-question');

        faqQuestions.forEach(question => {
            question.addEventListener('click', () => {
                const answer = question.nextElementSibling;
                const icon = question.querySelector('.faq-icon');
                const isOpen = answer.classList.contains('open');

                // Close all other FAQs
                document.querySelectorAll('.faq-answer').forEach(a => {
                    if (a !== answer) {
                        a.classList.remove('open');
                    }
                });
                document.querySelectorAll('.faq-icon').forEach(i => {
                    if (i !== icon) {
                        i.classList.remove('rotate');
                    }
                });

                // Toggle current FAQ
                answer.classList.toggle('open');
                icon.classList.toggle('rotate');

                // Reinitialize icons
                lucide.createIcons();
            });
        });

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const faqItems = document.querySelectorAll('.faq-item');
        const noResults = document.getElementById('noResults');

        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            let visibleCount = 0;

            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question span').textContent.toLowerCase();
                const answer = item.querySelector('.faq-answer').textContent.toLowerCase();

                if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                    item.style.display = 'block';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            // Show/hide no results message
            if (visibleCount === 0 && searchTerm !== '') {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }
        });

        // Category filter functionality
        const categoryPills = document.querySelectorAll('.category-pill');

        categoryPills.forEach(pill => {
            pill.addEventListener('click', () => {
                const category = pill.dataset.category;

                // Update active pill
                categoryPills.forEach(p => {
                    p.classList.remove('bg-blue-100', 'text-blue-700', 'active');
                    p.classList.add('bg-gray-100', 'text-gray-700');
                });
                pill.classList.remove('bg-gray-100', 'text-gray-700');
                pill.classList.add('bg-blue-100', 'text-blue-700', 'active');

                // Filter FAQs
                let visibleCount = 0;
                faqItems.forEach(item => {
                    if (category === 'all' || item.dataset.category === category) {
                        item.style.display = 'block';
                        visibleCount++;
                    } else {
                        item.style.display = 'none';
                    }
                });

                // Clear search when filtering
                searchInput.value = '';
                noResults.classList.add('hidden');
            });
        });
    </script>
</body>

</html>
