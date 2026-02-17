<?php
// If user is already logged in, redirect to dashboard
if (isset($_COOKIE['jwt_token']) || (isset($_SESSION) && isset($_SESSION['jwt_token']))) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RJIT Alumni Portal</title>
    
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="login-card rounded-2xl shadow-2xl w-full max-w-md p-8">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                <i data-lucide="graduation-cap" class="h-8 w-8 text-blue-600"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Welcome Back</h1>
            <p class="text-gray-600">Sign in to your RJIT Alumni account</p>
        </div>
        
        <!-- Login Form -->
        <form id="loginForm" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                <input type="email" 
                       id="email" 
                       name="email"
                       required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="you@example.com">
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                <input type="password" 
                       id="password" 
                       name="password"
                       required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="••••••••">
                <div class="mt-2 flex justify-end">
                    <a href="forgot-password.php" class="text-sm text-blue-600 hover:text-blue-800">Forgot password?</a>
                </div>
            </div>
            
            <div class="flex items-center">
                <input type="checkbox" 
                       id="remember" 
                       name="remember"
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="remember" class="ml-2 block text-sm text-gray-700">Remember me</label>
            </div>
            
            <button type="submit" 
                    id="loginBtn"
                    class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-semibold transition duration-300">
                <span id="btnText">Sign In</span>
                <i data-lucide="loader" id="loadingIcon" class="h-5 w-5 animate-spin hidden ml-2 inline"></i>
            </button>
            
            <div id="errorMessage" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm"></div>
            
            <div id="successMessage" class="hidden bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm"></div>
        </form>
        
        <!-- Divider -->
        <div class="my-6 flex items-center">
            <div class="flex-grow border-t border-gray-300"></div>
            <span class="flex-shrink mx-4 text-gray-500 text-sm">OR</span>
            <div class="flex-grow border-t border-gray-300"></div>
        </div>
        
        <!-- Register Links -->
        <div class="text-center space-y-3">
            <p class="text-gray-600">Don't have an account?</p>
            <div class="grid grid-cols-3 gap-3">
                <a href="register.php?type=student" class="block bg-gray-100 hover:bg-gray-200 text-gray-800 py-2 px-4 rounded-lg text-sm font-medium transition duration-300">
                    Student
                </a>
                <a href="register.php?type=alumni" class="block bg-blue-50 hover:bg-blue-100 text-blue-700 py-2 px-4 rounded-lg text-sm font-medium transition duration-300">
                    Alumni
                </a>
                <a href="register.php?type=faculty" class="block bg-blue-100 hover:bg-blue-200 text-blue-800 py-2 px-4 rounded-lg text-sm font-medium transition duration-300">
                    Faculty
                </a>
            </div>
        </div>
        
        <!-- Back to Home -->
        <div class="mt-8 text-center">
            <a href="index.php" class="inline-flex items-center text-gray-600 hover:text-gray-900">
                <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>
                Back to Home
            </a>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Handle login form submission
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            // Reset messages
            document.getElementById('errorMessage').classList.add('hidden');
            document.getElementById('successMessage').classList.add('hidden');
            
            // Show loading state
            const loginBtn = document.getElementById('loginBtn');
            const btnText = document.getElementById('btnText');
            const loadingIcon = document.getElementById('loadingIcon');
            
            btnText.textContent = 'Signing in...';
            loadingIcon.classList.remove('hidden');
            loginBtn.disabled = true;
            
            try {
                console.log('1. Attempting login...');
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        email: email,
                        password: password
                    })
                });
                
                console.log('2. Login response status:', response.status);
                const data = await response.json();
                console.log('3. Login response data:', data);
                
                if (data.success) {
                    // Store token in localStorage
                    localStorage.setItem('jwt_token', data.token);
                    console.log('4. Token stored:', data.token.substring(0, 20) + '...');
                    
                    // Get user info
                    console.log('5. Calling api/me.php...');
                    const userResponse = await fetch('api/me.php', {
                        method: 'GET',
                        headers: {
                            'Authorization': 'Bearer ' + data.token,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    });
                    
                    console.log('6. api/me.php response status:', userResponse.status);
                    console.log('7. api/me.php response headers:', Object.fromEntries(userResponse.headers.entries()));
                    
                    const responseText = await userResponse.text();
                    console.log('8. api/me.php raw response:', responseText);
                    
                    let userData;
                    try {
                        userData = JSON.parse(responseText);
                        console.log('9. api/me.php parsed JSON:', userData);
                    } catch (jsonError) {
                        console.error('10. JSON parse error:', jsonError);
                        throw new Error('Invalid JSON response from server: ' + responseText.substring(0, 100));
                    }
                    
                    if (userData.success) {
                        localStorage.setItem('user_data', JSON.stringify(userData.data));
                        console.log('11. User data stored successfully');
                        
                        // Show success message
                        document.getElementById('successMessage').textContent = 'Login successful! Redirecting...';
                        document.getElementById('successMessage').classList.remove('hidden');
                        
                        // Redirect to dashboard
                        setTimeout(() => {
                            window.location.href = 'dashboard.php';
                        }, 1000);
                    } else {
                        console.error('12. api/me.php returned success=false:', userData);
                        throw new Error('Failed to load user data: ' + (userData.message || 'Unknown error from server'));
                    }
                } else {
                    throw new Error(data.message || 'Login failed');
                }
            } catch (error) {
                console.error('Login error:', error);
                document.getElementById('errorMessage').textContent = error.message;
                document.getElementById('errorMessage').classList.remove('hidden');
                
                // Reset button
                btnText.textContent = 'Sign In';
                loadingIcon.classList.add('hidden');
                loginBtn.disabled = false;
            }
        });
        
        // Auto-focus email field
        document.getElementById('email').focus();
    </script>
</body>
</html>