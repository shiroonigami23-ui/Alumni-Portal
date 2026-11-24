<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - RJIT Alumni Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Roboto+Slab:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card { background: rgba(255,255,255,.96); backdrop-filter: blur(12px); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="card rounded-2xl shadow-2xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                <i data-lucide="mail-search" class="h-8 w-8 text-blue-600"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Forgot Password?</h1>
            <p class="text-gray-600 mt-2">Enter your email and we’ll send you a secure reset link.</p>
        </div>
        <form id="forgotPasswordForm" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                <input type="email" id="email" name="email" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="you@example.com">
            </div>
            <button type="submit" id="submitBtn"
                    class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 font-semibold transition duration-300 disabled:opacity-60">
                <span id="btnText">Send Reset Link</span>
            </button>
            <div id="feedback" class="hidden px-4 py-3 rounded-lg text-sm"></div>
        </form>
        <div class="mt-8 text-center">
            <a href="login.php" class="inline-flex items-center text-gray-600 hover:text-gray-900">
                <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>
                Back to login
            </a>
        </div>
    </div>
    <script>
        lucide.createIcons();
        const form = document.getElementById('forgotPasswordForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const feedback = document.getElementById('feedback');
        function showFeedback(message, ok = true) {
            feedback.textContent = message;
            feedback.className = `px-4 py-3 rounded-lg text-sm ${ok ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'}`;
            feedback.classList.remove('hidden');
        }
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            feedback.classList.add('hidden');
            submitBtn.disabled = true;
            btnText.textContent = 'Sending...';
            try {
                const response = await fetch('api/request_reset.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: document.getElementById('email').value.trim() })
                });
                const data = await response.json();
                showFeedback(data.message || 'If the email exists, a reset link has been sent.', true);
            } catch (error) {
                showFeedback('We could not process your request right now. Please try again.', false);
            } finally {
                submitBtn.disabled = false;
                btnText.textContent = 'Send Reset Link';
            }
        });
    </script>
</body>
</html>
