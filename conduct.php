<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code of Conduct - RJIT Alumni Portal</title>
    
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
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Roboto Slab', serif;
        }
        
        .content-container {
            max-width: 800px;
        }
        
        .conduct-rule {
            border-left: 4px solid #3b82f6;
            background-color: #f8fafc;
        }
        
        .warning-box {
            background: linear-gradient(135deg, #fef3c715 0%, #f59e0b15 100%);
            border: 2px solid #f59e0b30;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
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
        <div class="content-container mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-100 rounded-full mb-6">
                    <i data-lucide="shield" class="h-10 w-10 text-blue-600"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-4">Community Code of Conduct</h1>
                <p class="text-gray-600">Building a respectful and professional alumni community</p>
            </div>

            <!-- Introduction -->
            <div class="bg-white rounded-xl shadow-sm p-8 mb-8">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Our Commitment</h2>
                    <p class="text-gray-700 text-lg">
                        The RJIT Alumni Portal is committed to providing a safe, respectful, and professional environment for all members of our community.
                    </p>
                </div>
                
                <div class="grid md:grid-cols-3 gap-6 mb-8">
                    <div class="text-center p-6 bg-blue-50 rounded-lg">
                        <i data-lucide="users" class="h-8 w-8 text-blue-600 mx-auto mb-4"></i>
                        <h3 class="font-semibold text-gray-900 mb-2">Respect</h3>
                        <p class="text-gray-600 text-sm">Treat all members with dignity and respect</p>
                    </div>
                    
                    <div class="text-center p-6 bg-blue-50 rounded-lg">
                        <i data-lucide="heart" class="h-8 w-8 text-blue-600 mx-auto mb-4"></i>
                        <h3 class="font-semibold text-gray-900 mb-2">Inclusion</h3>
                        <p class="text-gray-600 text-sm">Welcome diversity and different perspectives</p>
                    </div>
                    
                    <div class="text-center p-6 bg-blue-50 rounded-lg">
                        <i data-lucide="award" class="h-8 w-8 text-blue-600 mx-auto mb-4"></i>
                        <h3 class="font-semibold text-gray-900 mb-2">Integrity</h3>
                        <p class="text-gray-600 text-sm">Act with honesty and professionalism</p>
                    </div>
                </div>
            </div>

            <!-- Code of Conduct Rules -->
            <div class="bg-white rounded-xl shadow-sm p-8 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Expected Behavior</h2>
                
                <div class="space-y-6">
                    <!-- Rule 1 -->
                    <div class="conduct-rule p-5">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 h-8 w-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mr-4">1</div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-2">Professional Conduct</h3>
                                <p class="text-gray-700">
                                    Maintain professional decorum in all interactions. The portal is an extension of the RJIT community and should reflect the institution's values.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Rule 2 -->
                    <div class="conduct-rule p-5">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 h-8 w-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mr-4">2</div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-2">Respectful Communication</h3>
                                <p class="text-gray-700">
                                    Communicate respectfully and constructively. Avoid personal attacks, harassment, hate speech, or discriminatory language.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Rule 3 -->
                    <div class="conduct-rule p-5">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 h-8 w-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mr-4">3</div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-2">Authentic Identity</h3>
                                <p class="text-gray-700">
                                    Use your real identity and provide accurate information. Impersonation or false representation is strictly prohibited.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Rule 4 -->
                    <div class="conduct-rule p-5">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 h-8 w-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mr-4">4</div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-2">Confidentiality</h3>
                                <p class="text-gray-700">
                                    Respect the privacy of others. Do not share personal information about other members without their consent.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Rule 5 -->
                    <div class="conduct-rule p-5">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 h-8 w-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mr-4">5</div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-2">Appropriate Content</h3>
                                <p class="text-gray-700">
                                    Share content that is relevant, professional, and adds value to the community. Avoid spam, advertising, or inappropriate material.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Rule 6 -->
                    <div class="conduct-rule p-5">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 h-8 w-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mr-4">6</div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-2">Intellectual Property</h3>
                                <p class="text-gray-700">
                                    Respect intellectual property rights. Only share content that you have the right to distribute.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Prohibited Behavior -->
            <div class="warning-box rounded-xl p-8 mb-8">
                <div class="flex items-start mb-6">
                    <i data-lucide="alert-octagon" class="h-6 w-6 text-amber-600 mr-3 mt-1"></i>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Prohibited Behavior</h2>
                        <p class="text-gray-700">The following behaviors are strictly prohibited and may result in immediate account suspension:</p>
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="flex items-start p-4 bg-white bg-opacity-50 rounded-lg">
                        <i data-lucide="x" class="h-5 w-5 text-red-500 mr-3 mt-0.5"></i>
                        <span class="text-gray-700">Harassment or bullying</span>
                    </div>
                    
                    <div class="flex items-start p-4 bg-white bg-opacity-50 rounded-lg">
                        <i data-lucide="x" class="h-5 w-5 text-red-500 mr-3 mt-0.5"></i>
                        <span class="text-gray-700">Hate speech or discrimination</span>
                    </div>
                    
                    <div class="flex items-start p-4 bg-white bg-opacity-50 rounded-lg">
                        <i data-lucide="x" class="h-5 w-5 text-red-500 mr-3 mt-0.5"></i>
                        <span class="text-gray-700">Spamming or unauthorized advertising</span>
                    </div>
                    
                    <div class="flex items-start p-4 bg-white bg-opacity-50 rounded-lg">
                        <i data-lucide="x" class="h-5 w-5 text-red-500 mr-3 mt-0.5"></i>
                        <span class="text-gray-700">Impersonation or false identity</span>
                    </div>
                    
                    <div class="flex items-start p-4 bg-white bg-opacity-50 rounded-lg">
                        <i data-lucide="x" class="h-5 w-5 text-red-500 mr-3 mt-0.5"></i>
                        <span class="text-gray-700">Sharing confidential information</span>
                    </div>
                    
                    <div class="flex items-start p-4 bg-white bg-opacity-50 rounded-lg">
                        <i data-lucide="x" class="h-5 w-5 text-red-500 mr-3 mt-0.5"></i>
                        <span class="text-gray-700">Illegal activities or content</span>
                    </div>
                </div>
            </div>

            <!-- Reporting and Enforcement -->
            <div class="bg-white rounded-xl shadow-sm p-8 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Reporting and Enforcement</h2>
                
                <div class="space-y-6">
                    <div class="flex items-start">
                        <i data-lucide="flag" class="h-6 w-6 text-blue-600 mr-4 mt-1"></i>
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-2">Reporting Violations</h3>
                            <p class="text-gray-700 mb-3">
                                If you witness or experience behavior that violates this Code of Conduct, please report it immediately:
                            </p>
                            <ul class="list-disc pl-6 text-gray-700 space-y-1">
                                <li>Use the "Report" feature on posts or profiles</li>
                                <li>Email: conduct@rjit.ac.in</li>
                                <li>Contact the portal administrators</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <i data-lucide="scale" class="h-6 w-6 text-blue-600 mr-4 mt-1"></i>
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-2">Enforcement Process</h3>
                            <p class="text-gray-700 mb-3">
                                All reports will be reviewed confidentially and promptly. Enforcement actions may include:
                            </p>
                            <ul class="list-disc pl-6 text-gray-700 space-y-1">
                                <li>Warning and removal of violating content</li>
                                <li>Temporary suspension of account privileges</li>
                                <li>Permanent termination of account</li>
                                <li>Reporting to RJIT administration for further action</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Agreement -->
            <div class="bg-blue-50 rounded-xl border border-blue-200 p-8">
                <div class="text-center">
                    <i data-lucide="check-circle" class="h-12 w-12 text-blue-600 mx-auto mb-4"></i>
                    <h2 class="text-xl font-bold text-blue-800 mb-4">Your Commitment</h2>
                    <p class="text-blue-700 mb-6">
                        By using the RJIT Alumni Portal, you agree to abide by this Code of Conduct and contribute to building a positive, professional community.
                    </p>
                    <div class="flex flex-col sm:flex-row justify-center gap-4">
                        <a href="index.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-medium">
                            Return to Home
                        </a>
                        <a href="register.php" class="bg-white text-blue-600 border border-blue-600 px-6 py-3 rounded-lg hover:bg-blue-50 font-medium">
                            Join Our Community
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

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
                        <li><a href="index.php" class="text-gray-400 hover:text-white">Home</a></li>
                        <li><a href="login.php" class="text-gray-400 hover:text-white">Login</a></li>
                        <li><a href="register.php" class="text-gray-400 hover:text-white">Register</a></li>
                        <li><a href="terms.php" class="text-gray-400 hover:text-white">Terms of Service</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="font-semibold text-lg mb-4">Legal</h3>
                    <ul class="space-y-2">
                        <li><a href="terms.php" class="text-gray-400 hover:text-white">Terms of Service</a></li>
                        <li><a href="policy.php" class="text-gray-400 hover:text-white">Privacy Policy</a></li>
                        <li><a href="conduct.php" class="text-white font-medium">Code of Conduct</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="font-semibold text-lg mb-4">Contact</h3>
                    <ul class="space-y-2">
                        <li class="text-gray-400">alumni@rjit.ac.in</li>
                        <li class="text-gray-400">conduct@rjit.ac.in</li>
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
    </script>
</body>
</html>