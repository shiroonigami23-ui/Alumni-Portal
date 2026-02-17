<?php
// Policy page with enhanced interactive features
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - RJIT Alumni Portal</title>

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
                        <a href="#introduction" class="toc-link block py-2 px-3 text-sm text-gray-600 border-l-2 border-gray-200 hover:text-blue-600 hover:border-blue-600">Introduction</a>
                        <a href="#information-collect" class="toc-link block py-2 px-3 text-sm text-gray-600 border-l-2 border-gray-200 hover:text-blue-600 hover:border-blue-600">Information We Collect</a>
                        <a href="#how-we-use" class="toc-link block py-2 px-3 text-sm text-gray-600 border-l-2 border-gray-200 hover:text-blue-600 hover:border-blue-600">How We Use Information</a>
                        <a href="#information-sharing" class="toc-link block py-2 px-3 text-sm text-gray-600 border-l-2 border-gray-200 hover:text-blue-600 hover:border-blue-600">Information Sharing</a>
                        <a href="#data-security" class="toc-link block py-2 px-3 text-sm text-gray-600 border-l-2 border-gray-200 hover:text-blue-600 hover:border-blue-600">Data Security</a>
                        <a href="#your-rights" class="toc-link block py-2 px-3 text-sm text-gray-600 border-l-2 border-gray-200 hover:text-blue-600 hover:border-blue-600">Your Rights</a>
                        <a href="#cookies" class="toc-link block py-2 px-3 text-sm text-gray-600 border-l-2 border-gray-200 hover:text-blue-600 hover:border-blue-600">Cookies & Tracking</a>
                        <a href="#third-party" class="toc-link block py-2 px-3 text-sm text-gray-600 border-l-2 border-gray-200 hover:text-blue-600 hover:border-blue-600">Third-Party Links</a>
                        <a href="#children" class="toc-link block py-2 px-3 text-sm text-gray-600 border-l-2 border-gray-200 hover:text-blue-600 hover:border-blue-600">Children's Privacy</a>
                        <a href="#changes" class="toc-link block py-2 px-3 text-sm text-gray-600 border-l-2 border-gray-200 hover:text-blue-600 hover:border-blue-600">Changes to Policy</a>
                        <a href="#contact" class="toc-link block py-2 px-3 text-sm text-gray-600 border-l-2 border-gray-200 hover:text-blue-600 hover:border-blue-600">Contact Us</a>
                    </nav>

                    <div class="mt-8 p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <div class="flex items-start">
                            <i data-lucide="download" class="h-5 w-5 text-blue-600 mr-2 mt-0.5"></i>
                            <div>
                                <h4 class="text-sm font-semibold text-blue-900 mb-1">Download PDF</h4>
                                <p class="text-xs text-blue-700 mb-2">Get a printable version</p>
                                <button onclick="window.print()" class="text-xs bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">Print Policy</button>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="lg:col-span-3">
                <!-- Header -->
                <div class="text-center mb-12 fade-in">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full mb-6 shadow-lg">
                        <i data-lucide="shield" class="h-8 w-8 text-white"></i>
                    </div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-4">Privacy Policy</h1>
                    <p class="text-gray-600">Last updated: <?php echo date('F j, Y'); ?></p>
                </div>

                <!-- Content -->
                <div class="bg-white rounded-xl shadow-sm p-8 lg:p-12">
                    <div class="prose prose-blue max-w-none">
                        <!-- Introduction -->
                        <section id="introduction" class="mb-12 fade-in">
                            <h2 class="text-3xl font-bold text-gray-900 mb-4">1. Introduction</h2>
                            <p class="text-gray-700 mb-4 leading-relaxed">
                                Welcome to the RJIT Alumni Portal. We are committed to protecting your privacy and ensuring the security of your personal information.
                            </p>
                            <p class="text-gray-700 leading-relaxed">
                                This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our platform.
                            </p>
                        </section>

                        <!-- Information We Collect -->
                        <section id="information-collect" class="mb-12 fade-in">
                            <h2 class="text-3xl font-bold text-gray-900 mb-6">2. Information We Collect</h2>

                            <h3 class="text-xl font-semibold text-gray-900 mb-3">Personal Information</h3>
                            <p class="text-gray-700 mb-4">When you register, we collect:</p>
                            <ul class="list-disc pl-6 text-gray-700 space-y-2 mb-6">
                                <li>Full name and contact information</li>
                                <li>Email address and phone number</li>
                                <li>Academic information (course, branch, graduation year)</li>
                                <li>Professional information (company, position)</li>
                                <li>Profile picture and biographical information</li>
                            </ul>

                            <h3 class="text-xl font-semibold text-gray-900 mb-3">Automatically Collected Information</h3>
                            <p class="text-gray-700 mb-4">We automatically collect:</p>
                            <ul class="list-disc pl-6 text-gray-700 space-y-2">
                                <li>IP address and device information</li>
                                <li>Browser type and operating system</li>
                                <li>Usage data and activity logs</li>
                                <li>Cookies and similar technologies</li>
                            </ul>
                        </section>

                        <!-- How We Use Information -->
                        <section id="how-we-use" class="mb-12 fade-in">
                            <h2 class="text-3xl font-bold text-gray-900 mb-4">3. How We Use Your Information</h2>
                            <p class="text-gray-700 mb-4">We use your information to:</p>
                            <ul class="list-disc pl-6 text-gray-700 space-y-2">
                                <li>Create and manage your account</li>
                                <li>Facilitate networking and connections</li>
                                <li>Send important updates and announcements</li>
                                <li>Improve our services and user experience</li>
                                <li>Monitor and analyze platform usage</li>
                                <li>Prevent fraud and ensure security</li>
                                <li>Comply with legal obligations</li>
                            </ul>
                        </section>

                        <!-- Information Sharing -->
                        <section id="information-sharing" class="mb-12 fade-in">
                            <h2 class="text-3xl font-bold text-gray-900 mb-4">4. Information Sharing</h2>
                            <p class="text-gray-700 mb-4">We may share your information with:</p>
                            <ul class="list-disc pl-6 text-gray-700 space-y-2 mb-6">
                                <li><strong>Other Users:</strong> Your profile information is visible to other registered users as per your privacy settings</li>
                                <li><strong>RJIT Administration:</strong> For official purposes and alumni relations</li>
                                <li><strong>Service Providers:</strong> Third parties who help us operate the platform</li>
                                <li><strong>Legal Authorities:</strong> When required by law or to protect rights</li>
                            </ul>

                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                                <div class="flex">
                                    <i data-lucide="alert-triangle" class="h-5 w-5 text-yellow-600 mr-3 mt-0.5"></i>
                                    <div>
                                        <h4 class="font-semibold text-yellow-800 mb-1">Important Note</h4>
                                        <p class="text-yellow-700 text-sm">
                                            Your profile visibility can be controlled through your privacy settings. Alumni and faculty can set their profiles to private.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Data Security -->
                        <section id="data-security" class="mb-12 fade-in">
                            <h2 class="text-3xl font-bold text-gray-900 mb-4">5. Data Security</h2>
                            <p class="text-gray-700 mb-4">
                                We implement appropriate technical and organizational measures to protect your personal information, including:
                            </p>
                            <ul class="list-disc pl-6 text-gray-700 space-y-2">
                                <li>Encryption of sensitive data</li>
                                <li>Regular security assessments</li>
                                <li>Access controls and authentication</li>
                                <li>Secure data storage and transmission</li>
                            </ul>
                            <p class="text-gray-700 mt-4">
                                However, no method of transmission over the Internet or electronic storage is 100% secure.
                            </p>
                        </section>

                        <!-- Your Rights -->
                        <section id="your-rights" class="mb-12 fade-in">
                            <h2 class="text-3xl font-bold text-gray-900 mb-4">6. Your Rights</h2>
                            <p class="text-gray-700 mb-4">You have the right to:</p>
                            <ul class="list-disc pl-6 text-gray-700 space-y-2">
                                <li>Access your personal information</li>
                                <li>Correct inaccurate information</li>
                                <li>Request deletion of your information</li>
                                <li>Export your data</li>
                                <li>Control your privacy settings</li>
                                <li>Opt-out of communications</li>
                            </ul>
                            <p class="text-gray-700 mt-4">
                                To exercise these rights, contact us at alumni@rjit.ac.in
                            </p>
                        </section>

                        <!-- Cookies -->
                        <section id="cookies" class="mb-12 fade-in">
                            <h2 class="text-3xl font-bold text-gray-900 mb-4">7. Cookies and Tracking</h2>
                            <p class="text-gray-700 mb-4">
                                We use cookies and similar tracking technologies to:
                            </p>
                            <ul class="list-disc pl-6 text-gray-700 space-y-2 mb-6">
                                <li>Remember your preferences</li>
                                <li>Maintain your login session</li>
                                <li>Analyze platform usage</li>
                                <li>Improve functionality</li>
                            </ul>
                            <p class="text-gray-700">
                                You can control cookies through your browser settings, but this may affect platform functionality.
                            </p>
                        </section>

                        <!-- Third-Party Links -->
                        <section id="third-party" class="mb-12 fade-in">
                            <h2 class="text-3xl font-bold text-gray-900 mb-4">8. Third-Party Links</h2>
                            <p class="text-gray-700">
                                Our platform may contain links to third-party websites. We are not responsible for the privacy practices or content of these sites.
                            </p>
                        </section>

                        <!-- Children's Privacy -->
                        <section id="children" class="mb-12 fade-in">
                            <h2 class="text-3xl font-bold text-gray-900 mb-4">9. Children's Privacy</h2>
                            <p class="text-gray-700">
                                Our platform is not intended for individuals under 16 years of age. We do not knowingly collect information from children.
                            </p>
                        </section>

                        <!-- Changes to Policy -->
                        <section id="changes" class="mb-12 fade-in">
                            <h2 class="text-3xl font-bold text-gray-900 mb-4">10. Changes to This Policy</h2>
                            <p class="text-gray-700 mb-4">
                                We may update this Privacy Policy from time to time. We will notify you of any material changes by email or through the platform.
                            </p>
                            <p class="text-gray-700">
                                Your continued use of the platform after changes constitutes acceptance of the updated policy.
                            </p>
                        </section>

                        <!-- Contact -->
                        <section id="contact" class="fade-in">
                            <h2 class="text-3xl font-bold text-gray-900 mb-4">11. Contact Us</h2>
                            <p class="text-gray-700 mb-4">
                                If you have questions about this Privacy Policy, please contact:
                            </p>
                            <div class="mt-4 p-6 bg-gray-50 rounded-lg border border-gray-200">
                                <p class="text-gray-700">
                                    <strong class="text-gray-900">Data Protection Officer</strong><br>
                                    RJIT Alumni Portal<br>
                                    Email: alumni@rjit.ac.in<br>
                                    Phone: +91 XXX XXX XXXX<br>
                                    Address: RJIT Campus, Bhopal, Madhya Pradesh
                                </p>
                            </div>
                        </section>
                    </div>

                    <!-- Data Protection Notice -->
                    <div class="mt-12 p-6 bg-gradient-to-r from-blue-50 to-purple-50 rounded-lg border border-blue-200">
                        <div class="flex items-start">
                            <i data-lucide="shield-check" class="h-6 w-6 text-blue-600 mr-3 mt-1"></i>
                            <div>
                                <h3 class="font-semibold text-blue-900 mb-2">Data Protection Commitment</h3>
                                <p class="text-blue-800">
                                    We are committed to protecting your personal information and complying with applicable data protection laws. Your trust is important to us.
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
                        <li><a href="terms.php" class="text-gray-400 hover:text-white transition-colors">Terms of Service</a></li>
                        <li><a href="policy.php" class="text-white font-medium">Privacy Policy</a></li>
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