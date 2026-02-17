<?php
// If user is already logged in, redirect to dashboard
if (isset($_COOKIE['jwt_token']) || (isset($_SESSION) && isset($_SESSION['jwt_token']))) {
    header('Location: dashboard.php');
    exit();
}

// Get registration type from query parameter
$type = isset($_GET['type']) ? $_GET['type'] : 'student';
$validTypes = ['student', 'alumni', 'faculty'];
if (!in_array($type, $validTypes)) {
    $type = 'student';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - RJIT Alumni Portal</title>
    
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
        
        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        
        .tab-active {
            background-color: white;
            color: #2563eb;
            font-weight: 600;
            border-bottom: 3px solid #2563eb;
        }
        
        .profile-preview {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border: 3px solid #e5e7eb;
        }
    </style>
</head>
<body class="min-h-screen p-4">
    <div class="max-w-4xl mx-auto">
        <!-- Back to Home -->
        <div class="mb-6">
            <a href="index.php" class="inline-flex items-center text-white hover:text-gray-200">
                <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>
                Back to Home
            </a>
        </div>
        
        <div class="register-card rounded-2xl shadow-2xl p-8">
            <!-- Logo & Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                    <i data-lucide="user-plus" class="h-8 w-8 text-blue-600"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Join RJIT Alumni Portal</h1>
                <p class="text-gray-600">Create your account to connect with the community</p>
            </div>
            
            <!-- Registration Type Tabs -->
            <div class="flex border-b border-gray-200 mb-8">
                <button type="button" 
                        data-type="student"
                        class="flex-1 py-3 px-4 text-center font-medium text-gray-500 hover:text-gray-700 tab <?php echo $type === 'student' ? 'tab-active' : ''; ?>">
                    <i data-lucide="user" class="h-5 w-5 inline-block mr-2"></i>
                    Student
                </button>
                <button type="button" 
                        data-type="alumni"
                        class="flex-1 py-3 px-4 text-center font-medium text-gray-500 hover:text-gray-700 tab <?php echo $type === 'alumni' ? 'tab-active' : ''; ?>">
                    <i data-lucide="graduation-cap" class="h-5 w-5 inline-block mr-2"></i>
                    Alumni
                </button>
                <button type="button" 
                        data-type="faculty"
                        class="flex-1 py-3 px-4 text-center font-medium text-gray-500 hover:text-gray-700 tab <?php echo $type === 'faculty' ? 'tab-active' : ''; ?>">
                    <i data-lucide="briefcase" class="h-5 w-5 inline-block mr-2"></i>
                    Faculty
                </button>
            </div>
            
            <!-- Registration Forms -->
            <div id="registrationForms">
                <!-- Student Registration Form -->
                <form id="studentForm" class="space-y-6 <?php echo $type !== 'student' ? 'hidden' : ''; ?>" enctype="multipart/form-data" data-endpoint="register_student.php">
                    <!-- Profile Picture Upload -->
                    <div class="flex flex-col items-center mb-6">
                        <div class="relative mb-4">
                            <img id="studentProfilePreview" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'%3E%3Crect width='120' height='120' fill='%23e5e7eb'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='14' fill='%239ca3af'%3EProfile%3C/text%3E%3C/svg%3E" 
                                 alt="Profile Preview" 
                                 class="profile-preview rounded-full">
                            <label for="student_profile_pic" class="absolute bottom-0 right-0 bg-blue-600 text-white p-2 rounded-full cursor-pointer hover:bg-blue-700">
                                <i data-lucide="camera" class="h-4 w-4"></i>
                            </label>
                        </div>
                        <input type="file" 
                               id="student_profile_pic" 
                               name="profile_pic"
                               accept="image/*"
                               class="hidden"
                               onchange="previewImage(this, 'studentProfilePreview')">
                        <p class="text-sm text-gray-500">Upload profile picture (Optional, max 2MB)</p>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="student_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                            <input type="text" 
                                   id="student_name" 
                                   name="name"
                                   required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Aryan Singh">
                        </div>
                        
                        <div>
                            <label for="student_email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                            <input type="email" 
                                   id="student_email" 
                                   name="email"
                                   required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="student@rjit.ac.in">
                        </div>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="student_password" class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                            <input type="password" 
                                   id="student_password" 
                                   name="password"
                                   required
                                   minlength="8"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="••••••••">
                            <p class="mt-1 text-sm text-gray-500">Minimum 8 characters</p>
                        </div>
                        
                        <div>
                            <label for="student_confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password *</label>
                            <input type="password" 
                                   id="student_confirm_password" 
                                   name="confirm_password"
                                   required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="••••••••">
                        </div>
                    </div>
                    
                    <div class="grid md:grid-cols-3 gap-6">
                        <div>
                            <label for="student_roll_number" class="block text-sm font-medium text-gray-700 mb-2">Roll Number *</label>
                            <input type="text" 
                                   id="student_roll_number" 
                                   name="roll_number"
                                   required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="0902CS231097">
                        </div>
                        
                        <div>
                            <label for="student_course" class="block text-sm font-medium text-gray-700 mb-2">Course *</label>
                            <select id="student_course" 
                                    name="course"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Course</option>
                                <option value="B.Tech">B.Tech</option>
                                <option value="M.Tech">M.Tech</option>
                                <option value="MBA">MBA</option>
                                <option value="MCA">MCA</option>
                                <option value="BCA">BCA</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="student_branch" class="block text-sm font-medium text-gray-700 mb-2">Branch *</label>
                            <select id="student_branch" 
                                    name="branch"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Branch</option>
                                <option value="CSE">Computer Science & Engineering</option>
                                <option value="IT">Information Technology</option>
                                <option value="ECE">Electronics & Communication</option>
                                <option value="EE">Electrical Engineering</option>
                                <option value="ME">Mechanical Engineering</option>
                                <option value="CE">Civil Engineering</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label for="student_graduation_year" class="block text-sm font-medium text-gray-700 mb-2">Expected Graduation Year *</label>
                        <select id="student_graduation_year" 
                                name="graduation_year"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Year</option>
                            <?php
                            $currentYear = date('Y');
                            for ($i = $currentYear; $i <= $currentYear + 4; $i++) {
                                echo "<option value=\"$i\">$i</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="student_terms" 
                               name="terms"
                               required
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="student_terms" class="ml-2 block text-sm text-gray-700">
                            I agree to the <a href="terms.php" class="text-blue-600 hover:text-blue-800">Terms of Service</a> and <a href="policy.php" class="text-blue-600 hover:text-blue-800">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-semibold transition duration-300">
                        Register as Student
                    </button>
                </form>
                
                <!-- Alumni Registration Form -->
                <form id="alumniForm" class="space-y-6 <?php echo $type !== 'alumni' ? 'hidden' : ''; ?>" enctype="multipart/form-data" data-endpoint="register_alumni.php">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex">
                            <i data-lucide="key" class="h-5 w-5 text-blue-600 mr-3 mt-0.5"></i>
                            <div>
                                <h4 class="font-semibold text-blue-800 mb-1">Alumni Invitation Required</h4>
                                <p class="text-blue-700 text-sm">You need an invitation token from an existing alumni or the administration to register.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Profile Picture Upload -->
                    <div class="flex flex-col items-center mb-6">
                        <div class="relative mb-4">
                            <img id="alumniProfilePreview" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'%3E%3Crect width='120' height='120' fill='%23e5e7eb'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='14' fill='%239ca3af'%3EProfile%3C/text%3E%3C/svg%3E" 
                                 alt="Profile Preview" 
                                 class="profile-preview rounded-full">
                            <label for="alumni_profile_pic" class="absolute bottom-0 right-0 bg-blue-600 text-white p-2 rounded-full cursor-pointer hover:bg-blue-700">
                                <i data-lucide="camera" class="h-4 w-4"></i>
                            </label>
                        </div>
                        <input type="file" 
                               id="alumni_profile_pic" 
                               name="profile_pic"
                               accept="image/*"
                               class="hidden"
                               onchange="previewImage(this, 'alumniProfilePreview')">
                        <p class="text-sm text-gray-500">Upload profile picture (Optional, max 2MB)</p>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="alumni_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                            <input type="text" 
                                   id="alumni_name" 
                                   name="name"
                                   required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Jane Smith">
                        </div>
                        
                        <div>
                            <label for="alumni_email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                            <input type="email" 
                                   id="alumni_email" 
                                   name="email"
                                   required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="alumni@example.com">
                        </div>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="alumni_password" class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                            <input type="password" 
                                   id="alumni_password" 
                                   name="password"
                                   required
                                   minlength="8"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="••••••••">
                            <p class="mt-1 text-sm text-gray-500">Minimum 8 characters</p>
                        </div>
                        
                        <div>
                            <label for="alumni_confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password *</label>
                            <input type="password" 
                                   id="alumni_confirm_password" 
                                   name="confirm_password"
                                   required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="••••••••">
                        </div>
                    </div>
                    
                    <div>
                        <label for="alumni_invite_token" class="block text-sm font-medium text-gray-700 mb-2">Invitation Token *</label>
                        <input type="text" 
                               id="alumni_invite_token" 
                               name="invite_token"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter your invitation token">
                        <p class="mt-1 text-sm text-gray-500">Contact alumni@rjit.ac.in if you need a token</p>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="alumni_graduation_year" class="block text-sm font-medium text-gray-700 mb-2">Graduation Year *</label>
                            <select id="alumni_graduation_year" 
                                    name="graduation_year"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Year</option>
                                <?php
                                $currentYear = date('Y');
                                for ($i = 1990; $i <= $currentYear; $i++) {
                                    echo "<option value=\"$i\">$i</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="alumni_branch" class="block text-sm font-medium text-gray-700 mb-2">Branch *</label>
                            <select id="alumni_branch" 
                                    name="branch"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Branch</option>
                                <option value="CSE">Computer Science & Engineering</option>
                                <option value="IT">Information Technology</option>
                                <option value="ECE">Electronics & Communication</option>
                                <option value="EE">Electrical Engineering</option>
                                <option value="ME">Mechanical Engineering</option>
                                <option value="CE">Civil Engineering</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label for="alumni_current_company" class="block text-sm font-medium text-gray-700 mb-2">Current Company</label>
                        <input type="text" 
                               id="alumni_current_company" 
                               name="current_company"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Google, Microsoft, etc.">
                    </div>
                    
                    <div>
                        <label for="alumni_position" class="block text-sm font-medium text-gray-700 mb-2">Current Position</label>
                        <input type="text" 
                               id="alumni_position" 
                               name="position"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Software Engineer, Product Manager, etc.">
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="alumni_terms" 
                               name="terms"
                               required
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="alumni_terms" class="ml-2 block text-sm text-gray-700">
                            I agree to the <a href="terms.php" class="text-blue-600 hover:text-blue-800">Terms of Service</a> and <a href="policy.php" class="text-blue-600 hover:text-blue-800">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-semibold transition duration-300">
                        Register as Alumni
                    </button>
                </form>
                
                <!-- Faculty Registration Form -->
                <form id="facultyForm" class="space-y-6 <?php echo $type !== 'faculty' ? 'hidden' : ''; ?>" enctype="multipart/form-data" data-endpoint="register_faculty.php">
                    <!-- Profile Picture Upload -->
                    <div class="flex flex-col items-center mb-6">
                        <div class="relative mb-4">
                            <img id="facultyProfilePreview" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'%3E%3Crect width='120' height='120' fill='%23e5e7eb'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='14' fill='%239ca3af'%3EProfile%3C/text%3E%3C/svg%3E" 
                                 alt="Profile Preview" 
                                 class="profile-preview rounded-full">
                            <label for="faculty_profile_pic" class="absolute bottom-0 right-0 bg-blue-600 text-white p-2 rounded-full cursor-pointer hover:bg-blue-700">
                                <i data-lucide="camera" class="h-4 w-4"></i>
                            </label>
                        </div>
                        <input type="file" 
                               id="faculty_profile_pic" 
                               name="profile_pic"
                               accept="image/*"
                               class="hidden"
                               onchange="previewImage(this, 'facultyProfilePreview')">
                        <p class="text-sm text-gray-500">Upload profile picture (Optional, max 2MB)</p>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="faculty_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                            <input type="text" 
                                   id="faculty_name" 
                                   name="name"
                                   required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Prof. Vivek Gupta">
                        </div>
                        
                        <div>
                            <label for="faculty_email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                            <input type="email" 
                                   id="faculty_email" 
                                   name="email"
                                   required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="faculty@rjit.ac.in">
                        </div>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="faculty_password" class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                            <input type="password" 
                                   id="faculty_password" 
                                   name="password"
                                   required
                                   minlength="8"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="••••••••">
                            <p class="mt-1 text-sm text-gray-500">Minimum 8 characters</p>
                        </div>
                        
                        <div>
                            <label for="faculty_confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password *</label>
                            <input type="password" 
                                   id="faculty_confirm_password" 
                                   name="confirm_password"
                                   required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="••••••••">
                        </div>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="faculty_department" class="block text-sm font-medium text-gray-700 mb-2">Department *</label>
                            <select id="faculty_department" 
                                    name="department"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Department</option>
                                <option value="CSE">Computer Science & Engineering</option>
                                <option value="IT">Information Technology</option>
                                <option value="ECE">Electronics & Communication</option>
                                <option value="EE">Electrical Engineering</option>
                                <option value="ME">Mechanical Engineering</option>
                                <option value="CE">Civil Engineering</option>
                                <option value="Mathematics">Mathematics</option>
                                <option value="Physics">Physics</option>
                                <option value="Chemistry">Chemistry</option>
                                <option value="Management">Management</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="faculty_designation" class="block text-sm font-medium text-gray-700 mb-2">Designation *</label>
                            <select id="faculty_designation" 
                                    name="designation"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Designation</option>
                                <option value="Professor">Professor</option>
                                <option value="Associate Professor">Associate Professor</option>
                                <option value="Assistant Professor">Assistant Professor</option>
                                <option value="Lecturer">Lecturer</option>
                                <option value="Visiting Faculty">Visiting Faculty</option>
                                <option value="Lab Assistant">Lab Assistant</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label for="faculty_employee_id" class="block text-sm font-medium text-gray-700 mb-2">Employee ID *</label>
                        <input type="text" 
                               id="faculty_employee_id" 
                               name="employee_id"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="RJITFAC123">
                    </div>
                    
                    <div>
                        <label for="faculty_qualification" class="block text-sm font-medium text-gray-700 mb-2">Highest Qualification</label>
                        <input type="text" 
                               id="faculty_qualification" 
                               name="qualification"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Ph.D, M.Tech, etc.">
                    </div>
                    
                    <div>
                        <label for="faculty_specialization" class="block text-sm font-medium text-gray-700 mb-2">Specialization</label>
                        <input type="text" 
                               id="faculty_specialization" 
                               name="specialization"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Machine Learning, Networks, etc.">
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="faculty_terms" 
                               name="terms"
                               required
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="faculty_terms" class="ml-2 block text-sm text-gray-700">
                            I agree to the <a href="terms.php" class="text-blue-600 hover:text-blue-800">Terms of Service</a> and <a href="policy.php" class="text-blue-600 hover:text-blue-800">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-semibold transition duration-300">
                        Register as Faculty
                    </button>
                </form>
            </div>
            
            <!-- Messages -->
            <div id="errorMessage" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mt-6"></div>
            <div id="successMessage" class="hidden bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm mt-6"></div>
            
            <!-- Login Link -->
            <div class="mt-8 text-center">
                <p class="text-gray-600">Already have an account? <a href="login.php" class="text-blue-600 hover:text-blue-800 font-semibold">Sign in here</a></p>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Profile picture preview
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const file = input.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                }
                
                reader.readAsDataURL(file);
                
                // Validate file size (max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    document.getElementById('errorMessage').textContent = 'File size must be less than 2MB';
                    document.getElementById('errorMessage').classList.remove('hidden');
                    input.value = '';
                    preview.src = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'%3E%3Crect width='120' height='120' fill='%23e5e7eb'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='14' fill='%239ca3af'%3EProfile%3C/text%3E%3C/svg%3E";
                }
            }
        }
        
        // Tab switching functionality
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const type = this.dataset.type;
                
                // Update URL without reloading
                window.history.pushState({}, '', `register.php?type=${type}`);
                
                // Update active tab
                document.querySelectorAll('.tab').forEach(t => {
                    t.classList.remove('tab-active');
                    t.classList.add('text-gray-500', 'hover:text-gray-700');
                });
                this.classList.add('tab-active');
                this.classList.remove('text-gray-500', 'hover:text-gray-700');
                
                // Show corresponding form
                document.querySelectorAll('form').forEach(form => {
                    form.classList.add('hidden');
                });
                document.getElementById(`${type}Form`).classList.remove('hidden');
            });
        });
        
        // Handle form submissions
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Reset messages
        document.getElementById('errorMessage').classList.add('hidden');
        document.getElementById('successMessage').classList.add('hidden');
        
        // 1. Password Validation
        const password = this.querySelector('input[name="password"]').value;
        const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
        if (password !== confirmPassword) {
            showError('Passwords do not match!');
            return;
        }
        
        // 2. Terms Validation
        if (!this.querySelector('input[name="terms"]').checked) {
            showError('You must agree to the terms and conditions!');
            return;
        }

        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.innerHTML = '<i data-lucide="loader" class="h-5 w-5 animate-spin inline"></i> Processing...';
        submitBtn.disabled = true;

        try {
            // Prepare JSON data
            const formData = new FormData(this);
            const jsonData = {};
            formData.forEach((value, key) => {
                if (key !== 'confirm_password' && key !== 'terms' && key !== 'profile_pic') {
                    jsonData[key] = value;
                }
            });

            const endpoint = this.dataset.endpoint;
            
            // Make API call
            const response = await fetch(`api/${endpoint}`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(jsonData)
            });
            
            // Get response as text first
            const responseText = await response.text();
            
            // Try to parse as JSON
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('Response is not JSON:', responseText);
                throw new Error('Server returned an invalid response. Please try again.');
            }
            
            if (result.success) {
                document.getElementById('successMessage').textContent = result.message || 'Registration successful!';
                document.getElementById('successMessage').classList.remove('hidden');
                
                // Clear form
                this.reset();
                
                // Reset profile previews
                const previews = document.querySelectorAll('[id$="ProfilePreview"]');
                previews.forEach(preview => {
                    preview.src = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'%3E%3Crect width='120' height='120' fill='%23e5e7eb'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='14' fill='%239ca3af'%3EProfile%3C/text%3E%3C/svg%3E";
                });
                
                // Redirect based on user type
                if (result.token) {
                    // Auto-login if token provided
                    localStorage.setItem('jwt_token', result.token);
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 2000);
                } else {
                    // Otherwise go to login
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                }
            } else {
                throw new Error(result.message || 'Registration failed');
            }
        } catch (error) {
            showError(error.message);
        } finally {
            // Reset button
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            lucide.createIcons();
        }
    });
});

function showError(msg) {
    const el = document.getElementById('errorMessage');
    el.textContent = msg;
    el.classList.remove('hidden');
}
        
        // Password validation
        document.querySelectorAll('input[type="password"]').forEach(input => {
            input.addEventListener('input', function() {
                const form = this.closest('form');
                const password = form.querySelector('input[name="password"]');
                const confirmPassword = form.querySelector('input[name="confirm_password"]');
                
                if (password && confirmPassword && password.value !== confirmPassword.value) {
                    confirmPassword.classList.add('border-red-300');
                } else {
                    confirmPassword.classList.remove('border-red-300');
                }
            });
        });
        
        // Auto-focus first input in active form
        document.addEventListener('DOMContentLoaded', function() {
            const activeForm = document.querySelector('form:not(.hidden)');
            if (activeForm) {
                const firstInput = activeForm.querySelector('input:not([type="file"]), select, textarea');
                if (firstInput) {
                    firstInput.focus();
                }
            }
        });
    </script>
</body>
</html>