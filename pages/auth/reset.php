<?php
$token = trim((string)($_GET['token'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - RJIT Alumni Portal</title>
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
                <i data-lucide="key-round" class="h-8 w-8 text-blue-600"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Set a New Password</h1>
            <p class="text-gray-600 mt-2">Choose a strong new password for your alumni portal login.</p>
        </div>
        <form id="resetPasswordForm" class="space-y-6">
            <input type="hidden" id="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                <input type="password" id="password" required minlength="8"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="At least 8 characters">
            </div>
            <div>
                <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                <input type="password" id="confirmPassword" required minlength="8"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Re-enter your new password">
            </div>
            <button type="submit" id="submitBtn"
                    class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 font-semibold transition duration-300 disabled:opacity-60">
                <span id="btnText">Update Password</span>
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
        const form = document.getElementById('resetPasswordForm');
        const token = document.getElementById('token').value.trim();
        const feedback = document.getElementById('feedback');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        function showFeedback(message, ok = true) {
            feedback.textContent = message;
            feedback.className = `px-4 py-3 rounded-lg text-sm ${ok ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'}`;
            feedback.classList.remove('hidden');
        }
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            feedback.classList.add('hidden');
            if (!token) {
                showFeedback('This reset link is incomplete. Please request a new one.', false);
                return;
            }
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirmPassword').value;
            if (password.length < 8) {
                showFeedback('Password must be at least 8 characters long.', false);
                return;
            }
            if (password !== confirm) {
                showFeedback('Passwords do not match.', false);
                return;
            }
            submitBtn.disabled = true;
            btnText.textContent = 'Updating...';
            try {
                const response = await fetch('api/reset_password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ token, new_password: password })
                });
                const data = await response.json();
                if (response.ok) {
                    showFeedback(data.message || 'Password updated successfully.', true);
                    setTimeout(() => { window.location.href = 'login.php'; }, 1500);
                } else {
                    showFeedback(data.message || 'Reset link is invalid or expired.', false);
                }
            } catch (error) {
                showFeedback('We could not update your password right now. Please try again.', false);
            } finally {
                submitBtn.disabled = false;
                btnText.textContent = 'Update Password';
            }
        });
    </script>
</body>
</html>
