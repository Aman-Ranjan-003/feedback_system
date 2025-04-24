<?php include 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $securityQuestion = $_POST['security_question'];
    $securityAnswer = trim($_POST['security_answer']);
    
    // Check if username or email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        $error = "Username or Email already exists!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, name, email, password, security_question, security_answer) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $username, $name, $email, $password, $securityQuestion, $securityAnswer);
        
        if ($stmt->execute()) {
            // Get the newly created user
            $user_id = $stmt->insert_id;
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            
            // Set session and redirect
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            header("Location: user_dashboard.php");
            exit();
        } else {
            $error = "Registration failed!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PowerFeedback - Sign Up</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="icon" href="./media/image/favicon.png"  type="image/png">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                            950: '#082f49',
                        },
                        secondary: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                            950: '#022c22',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    boxShadow: {
                        'custom': '0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1)',
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        .login-gradient {
    background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
}
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-blue-100 font-sans min-h-screen flex items-center justify-center p-4">
    <div class="max-w-4xl w-full">
        <!-- Back Button -->
        <div class="mb-4 absolute top-4 left-4">
            <a href="index.html" class="text-primary-600 hover:text-primary-700 font-medium flex items-center transition-colors duration-200">
                <i data-lucide="arrow-left" class="h-5 w-5 mr-1"></i>
                Back to Main
            </a>
        </div>

        <!-- Form Container -->
        <div class="bg-white rounded-xl shadow-custom overflow-hidden border border-gray-100">
            <div class="md:flex">
                <!-- Sign Up Form -->
                <div id="signup-form" class="md:w-1/2 p-8 md:p-10 <?php echo isset($_POST['username']) && !$error ? 'hidden' : ''; ?>">
                    <div class="text-center mb-8">
                        <h2 class="text-3xl font-bold text-gray-800">Create Account</h2>
                        <p class="text-gray-600 mt-2">Help improve campus electricity management</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4">
                            <p class="flex items-center">
                                <i data-lucide="alert-circle" class="h-5 w-5 mr-2"></i>
                                <?php echo $error; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <form id="registerForm" method="POST" class="space-y-5">
                        <div class="space-y-4">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i data-lucide="user" class="h-5 w-5 text-gray-400"></i>
                                </div>
                                <input type="text" name="name" placeholder="Full Name" required
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200">
                            </div>
                            
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i data-lucide="at-sign" class="h-5 w-5 text-gray-400"></i>
                                </div>
                                <input type="text" name="username" placeholder="Username" required
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200">
                            </div>
                            
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i data-lucide="mail" class="h-5 w-5 text-gray-400"></i>
                                </div>
                                <input type="email" name="email" placeholder="Email Address" required
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200">
                            </div>
                            
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i data-lucide="lock" class="h-5 w-5 text-gray-400"></i>
                                </div>
                                <input type="password" name="password" placeholder="Password" required minlength="6"
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Security Question</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="shield-question" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <select name="security_question" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent appearance-none bg-none transition-all duration-200" required>
                                        <option value="">Select a security question</option>
                                        <option value="What was your first pet's name?">What was your first pet's name?</option>
                                        <option value="What elementary school did you attend?">What elementary school did you attend?</option>
                                        <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                                        <option value="In what city were you born?">In what city were you born?</option>
                                        <option value="What is your favorite movie?">What is your favorite movie?</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <i data-lucide="chevron-down" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Answer</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="key" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <input type="text" name="security_answer" placeholder="Your answer" required
                                        class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200">
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" id="agree-terms" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" required>
                            <label for="agree-terms" class="ml-2 text-sm text-gray-600">
                                I agree to the <a href="#" class="text-primary-600 hover:underline">Terms of Service</a>
                            </label>
                        </div>
                        
                        <button type="submit" class="w-full py-3 px-4 bg-primary-500 hover:bg-primary-600 rounded-lg text-white font-medium transition duration-200 flex items-center justify-center">
                            <i data-lucide="user-plus" class="h-5 w-5 mr-2"></i>
                            Sign Up
                        </button>
                    </form>
                    
                    <div class="mt-6">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-white text-gray-500">Or sign up with</span>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-center">
                            <a href="google-login.php" class="w-full max-w-xs flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition duration-200">
                                <img src="./media/image/google.png" alt="Google" class="h-5 w-5 mr-2">
                                Google
                            </a>
                        </div>
                    </div>
                    
                    <p class="mt-6 text-center text-gray-600">
                        Already have an account? 
                        <a href="login.php" class="text-primary-600 font-medium hover:underline">Sign in</a>
                    </p>
                </div>
                
                <!-- Right Side - Image -->
                <div class="hidden md:block md:w-1/2 relative login-gradient">
                    <div class="absolute inset-0 bg-gradient-to-br from-[#3b82f6]/20 to-[#1e40af]/30 z-10"></div>

                    <!-- Content Overlay -->
                    <div class="absolute inset-0 flex flex-col items-center top-16 z-20 p-8 text-white">
                        <div class="max-w-md text-center animate-fade-in" style="animation-delay: 0.4s">
                            <h2 class="text-3xl font-bold mb-4">Power Supply Tracking</h2>
                            <p class="text-lg opacity-90 mb-8">
                                Share your experience with power supply in your area and help us improve reliability for everyone.
                            </p>
                            <div class="flex justify-center">
                                <div class="grid grid-cols-2 gap-4 max-w-xs">
                                    <div class="bg-white/10 backdrop-blur-sm p-4 rounded-lg">
                                        <div class="text-3xl font-bold">25k+</div>
                                        <div class="text-sm opacity-80">Feedback Reports</div>
                                    </div>
                                    <div class="bg-white/10 backdrop-blur-sm p-4 rounded-lg">
                                        <div class="text-3xl font-bold">120+</div>
                                        <div class="text-sm opacity-80">Cities Covered</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
      //  lucide.createIcons();
        
        // Form validation
        // document.getElementById('registerForm').addEventListener('submit', function(e) {
        //     if (!document.getElementById('agree-terms').checked) {
        //         e.preventDefault();
        //         alert('You must agree to the terms of service');
        //         return false;
        //     }
        //     return true;
        // });

        // Initialize Lucide icons
        lucide.createIcons();
        
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Clear previous errors
            const existingError = document.querySelector('.bg-red-100');
            if (existingError) existingError.remove();

            let errors = [];

            // Name validation (letters, spaces, and apostrophes only)
            const name = document.querySelector('[name="name"]').value.trim();
            if (!/^[A-Za-z\s']+$/.test(name)) {
                errors.push("Name should contain only letters, spaces, and apostrophes");
            }

            // Username validation (alphanumeric with both letters and numbers)
            const username = document.querySelector('[name="username"]').value.trim();
            if (!/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]+$/.test(username)) {
                errors.push("Username must contain both letters and numbers (no special characters)");
            }

            // Email validation
            const email = document.querySelector('[name="email"]').value.trim();
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)) {
                errors.push("Please enter a valid email address");
            }

            // Password validation
            const password = document.querySelector('[name="password"]').value;
            if (!/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{6,}$/.test(password)) {
                errors.push("Password must be: 6+ characters, 1 letter, 1 number, and 1 special character");
            }

            // Security answer validation
            const securityAnswer = document.querySelector('[name="security_answer"]').value.trim();
            if (securityAnswer === '') {
                errors.push("Security answer is required");
            }

            // Terms agreement check
            const agreeTerms = document.getElementById('agree-terms').checked;
            if (!agreeTerms) {
                errors.push("You must agree to the terms of service");
            }

            // Display errors or submit
            if (errors.length > 0) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'bg-red-100 text-red-700 p-3 rounded-lg mb-4';
                errorDiv.innerHTML = errors.map(error => `
                    <p class="flex items-center">
                        <i data-lucide="alert-circle" class="h-5 w-5 mr-2"></i>
                        ${error}
                    </p>
                `).join('');

                this.parentNode.insertBefore(errorDiv, this);
                lucide.createIcons();
            } else {
                this.submit();
            }
        });
 
        
    </script>
</body>
</html>
