<?php
// Terms of Service page with enhanced interactive features
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - RJIT Alumni Portal</title>

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

        .progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            width: 0%;
            z-index: 100;
            transition: width 0.1s ease;
        }

        .toc-link {
            transition: all 0.2s ease;
        }

        .toc-link.active {
            color: #3b82f6;
            font-weight: 600;
            border-left-color: #3b82f6;
        }

        section {
            scroll-margin-top: 100px;
        }

        .fade-in {
            animation: fadeIn 0.6s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .highlight-box {
            border-left: 4px solid #3b82f6;
            background: linear-gradient(90deg, #eff6ff 0%, #ffffff 100%);
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Progress Bar -->
    <div class="progress-bar" id="progressBar"></div>

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
                    <a href="login.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-medium">Login</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="lg:grid lg:grid-cols-4 lg:gap-8">
            <!-- Table of Contents (Sidebar) -->
            <aside class="hidden lg:block lg:col-span-1">
                <div class="sticky top-24">
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-4">On This Page</h3>
                    <nav class="space-y-2" id="tocNav">
                        <a href="#acceptance" class="toc-link block py-2 px-3 text-sm text-gray-600 border-l-2 border-gray-200 hover:text-blue-600 hover:border-blue-600">Acceptance of Terms</a>
                        <a href="#user-accounts" class="toc-link block py-2 px-3 text-sm text-gray-600 border-l-2 border-gray-200 hover:text-blue-600 hover:border-blue-600">User Accounts</a>
                        <a href="#user-conduct" class="toc-link block py-2 px-3 text-sm text-gray-600 border-l-2 border-gray-200 hover:text-blue-600 hover:border-blue-600">User Conduct</a>
                        <a href="#content-guidelines" class="toc-link block py-2 px-3 text-sm text-gray-600 border-l-2 border-gray-200 hover:text-blue-600 hover:border-blue-600">Content Guidelines</a>
                        <a href="#privacy" class="toc-link block py-2 px-3 text-sm text-gray-600 border-l-2 border-gray-200 hover:text-blue-600 hover:border-blue-600">Privacy</a>
                        <a href="#intellectual-property" class="toc-link block py-2 px-3 text-sm text-gray-600 border-l-2 border-gray-200 hover:text-blue-600 hover:border-blue-600">Intellectual Property</a>
                        <a href="#limitation-liability" class="toc-link block py-2 px-3 text-sm text-gray-600 border-l-2 border-gray-200 hover:text-blue-600 hover:border-blue-600">Limitation of Liability</a>
                        <a href="#changes-terms" class="toc-link block py-2 px-3 text-sm text-gray-600 border-l-2 border-gray-200 hover:text-blue-600 hover:border-blue-600">Changes to Terms</a>
                        <a href="#governing-law" class="toc-link block py-2 px-3 text-sm text-gray-600 border-l-2 border-gray-200 hover:text-blue-600 hover:border-blue-600">Governing Law</a>
                        <a href="#contact-info" class="toc-link block py-2 px-3 text-sm text-gray-600 border-l-2 border-gray-200 hover:text-blue-600 hover:border-blue-600">Contact Information</a>
                    </nav>

                    <div class="mt-8 p-4 bg-green-50 rounded-lg border border-green-200">
                        <div class="flex items-start">
                            <i data-lucide="file-text" class="h-5 w-5 text-green-600 mr-2 mt-0.5"></i>
                            <div>
                                <h4 class="text-sm font-semibold text-green-900 mb-1">Related Documents</h4>
                                <div class="space-y-1 mt-2">
                                    <a href="policy.php" class="block text-xs text-green-700 hover:text-green-900">Privacy Policy</a>
                                    <a href="conduct.php" class="block text-xs text-green-700 hover:text-green-900">Code of Conduct</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="lg:col-span-3">
                <!-- Header -->
                <div class="text-center mb-12 fade-in">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-green-500 to-blue-600 rounded-full mb-6 shadow-lg">
                        <i data-lucide="file-check" class="h-8 w-8 text-white"></i>
                    </div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-4">Terms of Service</h1>
                    <p class="text-gray-600">Last updated: <?php echo date('F j, Y'); ?></p>
                </div>

                <!-- Content -->
                <div class="bg-white rounded-xl shadow-sm p-8 lg:p-12">
                    <div class="prose prose-blue max-w-none">
                        <!-- Acceptance of Terms -->
                        <section id="acceptance" class="mb-12 fade-in">
                            <h2 class="text-3xl font-bold text-gray-900 mb-4">1. Acceptance of Terms</h2>
                            <p class="text-gray-700 mb-4 leading-relaxed">
                                Welcome to the RJIT Alumni Portal. By accessing or using our platform, you agree to be bound by these Terms of Service and all applicable laws and regulations.
                            </p>
                            <div class="highlight-box p-4 rounded-lg">
                                <p class="text-gray-700 font-medium">
                                    If you do not agree with any part of these terms, you may not use our services.
                                </p>
                            </div>
                        </section>

                        <!-- User Accounts -->
                        <section id="user-accounts" class="mb-12 fade-in">
                            <h2 class="text-3xl font-bold text-gray-900 mb-4">2. User Accounts</h2>
                            <ul class="list-disc pl-6 text-gray-700 space-y-2">
                                <li>You must be a current student, alumni, or faculty member of RJIT to register</li>
                                <li>You are responsible for maintaining the confidentiality of your account</li>
                                <li>You must provide accurate and complete information during registration</li>
                                <li>You are responsible for all activities that occur under your account</li>
                                <li>The portal reserves the right to suspend or terminate accounts that violate these terms</li>
                            </ul>
                        </section>

                        <!-- User Conduct -->
                        <section id="user-conduct" class="mb-12 fade-in">
                            <h2 class="text-3xl font-bold text-gray-900 mb-4">3. User Conduct</h2>
                            <p class="text-gray-700 mb-4">You agree not to:</p>
                            <ul class="list-disc pl-6 text-gray-700 space-y-2">
                                <li>Post offensive, abusive, or harassing content</li>
                                <li>Share false or misleading information</li>
                                <li>Impersonate another person or entity</li>
                                <li>Engage in spamming or unauthorized advertising</li>
                                <li>Violate intellectual property rights</li>
                                <li>Attempt to gain unauthorized access to the system</li>
                                <li>Use the platform for illegal activities</li>
                            </ul>
                        </section>

                        <!-- Content Guidelines -->
                        <section id="content-guidelines" class="mb-12 fade-in">
                            <h2 class="text-3xl font-bold text-gray-900 mb-4">4. Content Guidelines</h2>
                            <p class="text-gray-700 mb-4">All content posted on the portal must:</p>
                            <ul class="list-disc pl-6 text-gray-700 space-y-2">
                                <li>Be respectful and professional</li>
                                <li>Comply with RJIT's code of conduct</li>
                                <li>Not contain confidential or proprietary information</li>
                                <li>Be relevant to the RJIT community</li>
                                <li>Not violate any third-party rights</li>
                            </ul>
                            <div class="mt-4 p-4 bg-amber-50 border-l-4 border-amber-400 rounded">
                                <p class="text-amber-800 text-sm">
                                    <strong>Note:</strong> The portal reserves the right to remove any content that violates these guidelines.
                                </p>
                            </div>
                        </section>

                        <!-- Privacy -->
                        <section id="privacy" class="mb-12 fade-in">
                            <h2 class="text-3xl font-bold text-gray-900 mb-4">5. Privacy</h2>
                            <p class="text-gray-700 mb-4">
                                Your privacy is important to us. Please review our <a href="policy.php" class="text-blue-600 hover:text-blue-800 font-medium">Privacy Policy</a> to understand how we collect, use, and protect your information.
                            </p>
                        </section>

                        <!-- Intellectual Property -->
                        <section id="intellectual-property" class="mb-12 fade-in">
                            <h2 class="text-3xl font-bold text-gray-900 mb-4">6. Intellectual Property</h2>
                            <p class="text-gray-700 mb-4">
                                The RJIT Alumni Portal and its original content, features, and functionality are owned by RJIT and are protected by international copyright, trademark, and other intellectual property laws.
                            </p>
                            <div class="highlight-box p-4 rounded-lg">
                                <p class="text-gray-700">
                                    You retain ownership of the content you post, but grant RJIT a non-exclusive license to display and distribute that content on the platform.
                                </p>
                            </div>
                        </section>

                        <!-- Limitation of Liability -->
                        <section id="limitation-liability" class="mb-12 fade-in">
                            <h2 class="text-3xl font-bold text-gray-900 mb-4">7. Limitation of Liability</h2>
                            <p class="text-gray-700">
                                RJIT shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of or inability to use the portal.
                            </p>
                        </section>

                        <!-- Changes to Terms -->
                        <section id="changes-terms" class="mb-12 fade-in">
                            <h2 class="text-3xl font-bold text-gray-900 mb-4">8. Changes to Terms</h2>
                            <p class="text-gray-700">
                                We reserve the right to modify these terms at any time. We will notify users of any material changes. Your continued use of the portal constitutes acceptance of the modified terms.
                            </p>
                        </section>

                        <!-- Governing Law -->
                        <section id="governing-law" class="mb-12 fade-in">
                            <h2 class="text-3xl font-bold text-gray-900 mb-4">9. Governing Law</h2>
                            <p class="text-gray-700">
                                These terms shall be governed by and construed in accordance with the laws of India, without regard to its conflict of law provisions.
                            </p>
                        </section>

                        <!-- Contact Information -->
                        <section id="contact-info" class="fade-in">
                            <h2 class="text-3xl font-bold text-gray-900 mb-4">10. Contact Information</h2>
                            <p class="text-gray-700 mb-4">
                                If you have any questions about these Terms of Service, please contact us at:
                            </p>
                            <div class="mt-4 p-6 bg-gray-50 rounded-lg border border-gray-200">
                                <p class="text-gray-700">
                                    <strong class="text-gray-900">RJIT Alumni Relations Office</strong><br>
                                    Email: alumni@rjit.ac.in<br>
                                    Phone: +91 XXX XXX XXXX<br>
                                    Address: RJIT Campus, Bhopal, Madhya Pradesh
                                </p>
                            </div>
                        </section>
                    </div>

                    <!-- Acceptance Notice -->
                    <div class="mt-12 p-6 bg-gradient-to-r from-green-50 to-blue-50 rounded-lg border-2 border-green-200">
                        <div class="flex items-start">
                            <i data-lucide="check-circle-2" class="h-6 w-6 text-green-600 mr-3 mt-1 flex-shrink-0"></i>
                            <div>
                                <h3 class="font-semibold text-green-900 mb-2">Acceptance of Terms</h3>
                                <p class="text-green-800">
                                    By registering for and using the RJIT Alumni Portal, you acknowledge that you have read, understood, and agree to be bound by these Terms of Service.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Back Button -->
                <div class="mt-8 text-center">
                    <a href="index.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium transition-colors">
                        <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>
                        Back to Home
                    </a>
                </div>
            </div>
        </div>
    </main>

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
                        <li><a href="help.php" class="text-gray-400 hover:text-white transition-colors">Help Center</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-semibold text-lg mb-4">Legal</h3>
                    <ul class="space-y-2">
                        <li><a href="terms.php" class="text-white font-medium">Terms of Service</a></li>
                        <li><a href="policy.php" class="text-gray-400 hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="conduct.php" class="text-gray-400 hover:text-white transition-colors">Code of Conduct</a></li>
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

        // Reading progress bar
        function updateProgressBar() {
            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = (winScroll / height) * 100;
            document.getElementById('progressBar').style.width = scrolled + '%';
        }

        window.addEventListener('scroll', updateProgressBar);

        // Smooth scroll and active section highlighting
        const sections = document.querySelectorAll('section[id]');
        const tocLinks = document.querySelectorAll('.toc-link');

        function highlightActiveSection() {
            let current = '';

            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (pageYOffset >= (sectionTop - 150)) {
                    current = section.getAttribute('id');
                }
            });

            tocLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active');
                }
            });
        }

        window.addEventListener('scroll', highlightActiveSection);

        // TOC smooth scroll
        tocLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = link.getAttribute('href');
                const targetSection = document.querySelector(targetId);
                targetSection.scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Initial highlight
        highlightActiveSection();
    </script>
</body>

</html>