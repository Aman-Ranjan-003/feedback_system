<?php
include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$error = '';
$success = '';
$password_error = '';
$password_success = '';

// Fetch user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle profile update submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
  $username = trim($_POST['username']);
  $name = trim($_POST['fullName']);
  $address = trim($_POST['address']);
  $phone = trim($_POST['phone']);
  $gender = $_POST['gender'];

  // Validate phone number (for non-Google users)
  if ($user['google_id'] === null && $phone && !preg_match('/^(\+91|0)?[ -]?[6-9]\d{9}$/', $phone)) {
    $error = "Invalid Indian phone number!";
  } else {
    // Handle profile picture upload
    $profilePicture = $user['profile_picture'];
    if ($_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
      $uploadDir = 'uploads/';
      if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
      }
      $fileName = uniqid() . '_' . basename($_FILES['profile_picture']['name']);
      $targetFile = $uploadDir . $fileName;
      if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
        $profilePicture = $targetFile;
      } else {
        $error = "Failed to upload profile picture.";
      }
    }

    // Update user details
    $stmt = $conn->prepare("UPDATE users SET username = ?, name = ?, address = ?, phone = ?, gender = ?, profile_picture = ? WHERE id = ?");
    $stmt->bind_param("ssssssi", $username, $name, $address, $phone, $gender, $profilePicture, $_SESSION['user_id']);

    if ($stmt->execute()) {
      $success = "Profile updated successfully!";
      // Refresh user data
      $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
      $stmt->bind_param("i", $_SESSION['user_id']);
      $stmt->execute();
      $user = $stmt->get_result()->fetch_assoc();
    } else {
      $error = "Failed to update profile. Please try again.";
    }
  }
}

// Handle password change submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
  $old_password = $_POST['currentPassword'];
  $new_password = $_POST['newPassword'];
  $confirm_password = $_POST['confirmPassword'];

  // Verify old password
  if (password_verify($old_password, $user['password'])) {
    // Check if new password matches confirmation
    if ($new_password === $confirm_password) {
      // Update password
      $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
      $stmt->bind_param("si", $new_password_hash, $_SESSION['user_id']);

      if ($stmt->execute()) {
        $success = 'Password updated successfully!';
        // Clear POST data to show profile form
        $_POST = array();
      } else {
        $password_error = 'Failed to update password. Please try again.';
      }
    } else {
      $password_error = 'New passwords do not match!';
    }
  } else {
    $password_error = 'Current password is incorrect!';
  }
}

// Determine which form to show
$show_password_form = isset($_POST['change_password']) && empty($success);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile Page</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="lucide.js"></script>
  <link rel="icon" href="./media/image/favicon.png"  type="image/png">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    body {
      font-family: 'Inter', sans-serif;
      background-color: #f8fafc;
    }

    .disabled-input {
      cursor: not-allowed;
    }

    .disabled-input:hover {
      cursor: not-allowed;
    }

    /* Custom animations */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .animate-fadeIn {
      animation: fadeIn 0.3s ease-out forwards;
    }

    /* Custom scrollbar */
    ::-webkit-scrollbar {
      width: 8px;
    }

    ::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb {
      background: #10b981;
      border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: #0d9488;
    }

    /* Form focus styles */
    input:focus, textarea:focus {
      box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
      outline: none;
    }

    /* Card hover effect */
    .profile-card {
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .profile-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
  </style>
</head>

<body class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
  <div id="app" class="pb-12">
    <!-- Display Error/Success Messages -->
    <?php if ($error): ?>
      <div
        class="fixed top-4 right-4 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-r shadow-lg z-50 flex items-center justify-between w-80 animate-fadeIn"
        id="errorAlert">
        <div class="flex items-center">
          <i data-lucide="alert-circle" class="w-5 h-5 mr-2 text-red-500"></i>
          <span class="text-sm font-medium"><?= htmlspecialchars($error) ?></span>
        </div>
        <button onclick="document.getElementById('errorAlert').style.display = 'none'" class="text-red-500 hover:text-red-700">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div
        class="fixed top-4 right-4 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-r shadow-lg z-50 flex items-center justify-between w-80 animate-fadeIn"
        id="successAlert">
        <div class="flex items-center">
          <i data-lucide="check-circle" class="w-5 h-5 mr-2 text-green-500"></i>
          <span class="text-sm font-medium"><?= htmlspecialchars($success) ?></span>
        </div>
        <button onclick="document.getElementById('successAlert').style.display = 'none'" class="text-green-500 hover:text-green-700">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>
    <?php endif; ?>

    <?php if ($password_error && $show_password_form): ?>
      <div
        class="fixed top-4 right-4 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-r shadow-lg z-50 flex items-center justify-between w-80 animate-fadeIn"
        id="passwordErrorAlert">
        <div class="flex items-center">
          <i data-lucide="alert-circle" class="w-5 h-5 mr-2 text-red-500"></i>
          <span class="text-sm font-medium"><?= htmlspecialchars($password_error) ?></span>
        </div>
        <button onclick="document.getElementById('passwordErrorAlert').style.display = 'none'" class="text-red-500 hover:text-red-700">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>
    <?php endif; ?>

    <!-- Top Profile Section -->
    <div class="bg-gradient-to-r from-teal-600 to-emerald-500 text-white">
      <div class="max-w-6xl mx-auto px-4 py-10 md:py-16 relative">
        <!-- Back to Dashboard Button -->
        <a href="user_dashboard.php"
          class="absolute left-4 top-4 flex items-center gap-2 text-white hover:text-emerald-100 transition-colors bg-white/10 px-3 py-1.5 rounded-full backdrop-blur-sm">
          <i data-lucide="arrow-left" class="w-4 h-4"></i>
          <span class="text-sm font-medium">Dashboard</span>
        </a>

        <div class="flex flex-col md:flex-row items-center gap-8 pt-6">
          <!-- Profile Image -->
          <div class="relative group">
            <div id="profileImageContainer"
              class="w-32 h-32 md:w-40 md:h-40 rounded-full overflow-hidden border-4 border-white shadow-lg transition-all duration-300 <?= empty($user['profile_picture']) ? 'bg-emerald-700 flex items-center justify-center' : '' ?>">
              <?php if (!empty($user['profile_picture'])): ?>
                <img src="<?= $user['profile_picture'] ?>" alt="Profile" class="w-full h-full object-cover" />
              <?php else: ?>
                <div class="w-full h-full flex items-center justify-center">
                  <i data-lucide="user" class="w-16 h-16 text-emerald-200"></i>
                </div>
              <?php endif; ?>
            </div>
            <label for="fileInput"
              class="absolute bottom-2 right-2 bg-white text-emerald-600 p-2 rounded-full cursor-pointer shadow-lg hover:bg-emerald-50 transition-all duration-200 group-hover:scale-110">
              <i data-lucide="camera" class="w-4 h-4"></i>
            </label>
          </div>

          <!-- Profile Info -->
          <div class="text-center md:text-left">
            <h1 id="profileName" class="text-3xl font-bold"><?= htmlspecialchars($user['name'] ?: 'Your Name') ?></h1>
            <p id="profileUsername" class="text-emerald-100 mt-1 flex items-center justify-center md:justify-start gap-1">
              <i data-lucide="at-sign" class="w-4 h-4"></i>
              <span><?= htmlspecialchars($user['username'] ?: 'username') ?></span>
            </p>
            <div class="mt-3 flex items-center justify-center md:justify-start gap-2 text-sm">
              <span class="bg-white/20 px-3 py-1 rounded-full backdrop-blur-sm flex items-center gap-1">
                <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                <?= htmlspecialchars($user['email']) ?>
              </span>
              <?php if (!empty($user['phone'])): ?>
              <span class="bg-white/20 px-3 py-1 rounded-full backdrop-blur-sm flex items-center gap-1">
                <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                <?= htmlspecialchars($user['phone']) ?>
              </span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-4 py-8">
      <div class="bg-white shadow-lg rounded-xl p-6 md:p-8 profile-card">

        <!-- Profile Form -->
        <form id="profileForm" method="POST" enctype="multipart/form-data"
          class="<?= $show_password_form ? 'hidden' : '' ?>">
          <div class="flex items-center justify-between mb-6">
            <h2 id="formTitle" class="text-2xl font-bold text-gray-800 flex items-center gap-2">
              <i data-lucide="user" class="w-6 h-6 text-emerald-500"></i>
              My Profile
            </h2>
            <div class="text-sm text-gray-500 flex items-center gap-1">
              <i data-lucide="info" class="w-4 h-4"></i>
              <span>Last updated: <?= date('M d, Y', strtotime($user['updated_at'] ?? 'now')) ?></span>
            </div>
          </div>
          <input type="hidden" name="update_profile" value="1">
          <input type="file" id="fileInput" name="profile_picture" class="hidden" accept="image/*" />

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <label for="username" class="text-sm font-medium text-gray-700 flex items-center gap-1">
                <i data-lucide="at-sign" class="w-4 h-4 text-emerald-500"></i>
                Username
              </label>
              <input id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200"
                placeholder="Enter your username" required />
            </div>

            <div class="space-y-2">
              <label for="fullName" class="text-sm font-medium text-gray-700 flex items-center gap-1">
                <i data-lucide="user" class="w-4 h-4 text-emerald-500"></i>
                Full Name
              </label>
              <input id="fullName" name="fullName" value="<?= htmlspecialchars($user['name']) ?>"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200"
                placeholder="Enter your full name" required />
            </div>

            <div class="space-y-2 md:col-span-2">
              <label for="address" class="text-sm font-medium text-gray-700 flex items-center gap-1">
                <i data-lucide="map-pin" class="w-4 h-4 text-emerald-500"></i>
                Address
              </label>
              <input id="address" name="address" value="<?= htmlspecialchars($user['address']) ?>"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200"
                placeholder="Enter your address" />
            </div>

            <div class="space-y-2">
              <label for="phone" class="text-sm font-medium text-gray-700 flex items-center gap-1">
                <i data-lucide="phone" class="w-4 h-4 text-emerald-500"></i>
                Phone Number
              </label>
              <input id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200"
                placeholder="Enter your phone number" />
            </div>

            <div class="space-y-2">
              <label for="email" class="text-sm font-medium text-gray-700 flex items-center gap-1">
                <i data-lucide="mail" class="w-4 h-4 text-emerald-500"></i>
                Email
              </label>
              <div class="relative">
                <input id="email" name="email" type="email" value="<?= htmlspecialchars($user['email']) ?>"
                  class="w-full rounded-lg border border-gray-300 px-4 py-2.5 bg-gray-50 cursor-not-allowed pl-10"
                  placeholder="Your email address" readonly disabled />
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                  <i data-lucide="lock" class="w-4 h-4 text-gray-400"></i>
                </div>
              </div>
              <p class="text-xs text-gray-500 mt-1">Email cannot be changed</p>
            </div>

            <div class="space-y-3 md:col-span-2">
              <label class="text-sm font-medium text-gray-700 flex items-center gap-1">
                <i data-lucide="user" class="w-4 h-4 text-emerald-500"></i>
                Gender
              </label>
              <div class="flex flex-wrap gap-6">
                <div class="flex items-center space-x-2">
                  <input type="radio" id="male" name="gender" value="male" <?= $user['gender'] === 'male' ? 'checked' : '' ?> 
                    class="w-4 h-4 text-emerald-500 focus:ring-emerald-500 border-gray-300">
                  <label for="male" class="text-gray-700">Male</label>
                </div>
                <div class="flex items-center space-x-2">
                  <input type="radio" id="female" name="gender" value="female" <?= $user['gender'] === 'female' ? 'checked' : '' ?> 
                    class="w-4 h-4 text-emerald-500 focus:ring-emerald-500 border-gray-300">
                  <label for="female" class="text-gray-700">Female</label>
                </div>
                <div class="flex items-center space-x-2">
                  <input type="radio" id="other" name="gender" value="other" <?= $user['gender'] === 'other' ? 'checked' : '' ?> 
                    class="w-4 h-4 text-emerald-500 focus:ring-emerald-500 border-gray-300">
                  <label for="other" class="text-gray-700">Other</label>
                </div>
              </div>
            </div>
          </div>

          <div class="border-t border-gray-200 my-8"></div>

          <div class="flex flex-col sm:flex-row sm:justify-between gap-4">
            <button type="submit"
              class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-6 py-2.5 flex items-center justify-center gap-2 transition-all duration-200 shadow-md hover:shadow-lg">
              <i data-lucide="save" class="w-5 h-5"></i>
              Update Profile
            </button>

            <button type="button" id="changePasswordBtn"
              class="border border-emerald-600 text-emerald-600 hover:bg-emerald-50 rounded-lg px-6 py-2.5 flex items-center justify-center gap-2 transition-all duration-200">
              <i data-lucide="lock" class="w-5 h-5"></i>
              Change Password
            </button>
          </div>
        </form>

        <!-- Password Form -->
        <form id="passwordForm" method="POST"
          class="<?= !$show_password_form ? 'hidden' : '' ?> flex flex-col md:flex-row gap-8">
          <input type="hidden" name="change_password" value="1">

          <div class="flex-1">
            <div class="flex items-center mb-6">
              <button type="button" id="backToProfileBtn" class="p-1 mr-4 hover:bg-gray-100 rounded-full transition-colors">
                <i data-lucide="arrow-left" class="w-5 h-5 text-gray-600"></i>
              </button>
              <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i data-lucide="lock" class="w-6 h-6 text-emerald-500"></i>
                Change Password
              </h2>
            </div>

            <div class="space-y-6">
              <div class="space-y-2">
                <label for="currentPassword" class="text-sm font-medium text-gray-700 flex items-center gap-1">
                  <i data-lucide="key" class="w-4 h-4 text-emerald-500"></i>
                  Current Password
                </label>
                <div class="relative">
                  <input id="currentPassword" name="currentPassword" type="password"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 pl-10"
                    placeholder="Enter your current password" required />
                  <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i data-lucide="lock" class="w-4 h-4 text-gray-400"></i>
                  </div>
                  <button type="button" class="toggle-password absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600" data-target="currentPassword">
                    <i data-lucide="eye" class="w-4 h-4 password-icon-show"></i>
                    <i data-lucide="eye-off" class="w-4 h-4 password-icon-hide hidden"></i>
                  </button>
                </div>
              </div>

              <div class="space-y-2">
                <label for="newPassword" class="text-sm font-medium text-gray-700 flex items-center gap-1">
                  <i data-lucide="key" class="w-4 h-4 text-emerald-500"></i>
                  New Password
                </label>
                <div class="relative">
                  <input id="newPassword" name="newPassword" type="password"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 pl-10"
                    placeholder="Enter your new password" required />
                  <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i data-lucide="lock" class="w-4 h-4 text-gray-400"></i>
                  </div>
                  <button type="button" class="toggle-password absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600" data-target="newPassword">
                    <i data-lucide="eye" class="w-4 h-4 password-icon-show"></i>
                    <i data-lucide="eye-off" class="w-4 h-4 password-icon-hide hidden"></i>
                  </button>
                </div>
              </div>

              <div class="space-y-2">
                <label for="confirmPassword" class="text-sm font-medium text-gray-700 flex items-center gap-1">
                  <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500"></i>
                  Confirm New Password
                </label>
                <div class="relative">
                  <input id="confirmPassword" name="confirmPassword" type="password"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 pl-10"
                    placeholder="Confirm your new password" required />
                  <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i data-lucide="lock" class="w-4 h-4 text-gray-400"></i>
                  </div>
                  <button type="button" class="toggle-password absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600" data-target="confirmPassword">
                    <i data-lucide="eye" class="w-4 h-4 password-icon-show"></i>
                    <i data-lucide="eye-off" class="w-4 h-4 password-icon-hide hidden"></i>
                  </button>
                </div>
              </div>

              <div class="mt-2 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded-md">
                <div class="flex items-start">
                  <i data-lucide="alert-triangle" class="w-5 h-5 text-yellow-500 mt-0.5 mr-2"></i>
                  <div>
                    <h3 class="text-sm font-medium text-yellow-800">Password requirements:</h3>
                    <ul class="mt-1 text-xs text-yellow-700 list-disc list-inside">
                      <li>At least 8 characters long</li>
                      <li>Include at least one uppercase letter</li>
                      <li>Include at least one number</li>
                      <li>Include at least one special character</li>
                    </ul>
                  </div>
                </div>
              </div>

              <button type="submit"
                class="mt-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-6 py-2.5 flex items-center justify-center gap-2 transition-all duration-200 shadow-md hover:shadow-lg w-full md:w-auto">
                <i data-lucide="save" class="w-5 h-5"></i>
                Update Password
              </button>
            </div>
          </div>

          <div id="passwordChangeStatus" class="flex-1 hidden md:flex flex-col items-center justify-center">
            <div id="passwordChanging" class="hidden text-center">
              <div class="relative w-24 h-24 mb-6 mx-auto">
                <div class="absolute top-0 left-0 w-full h-full border-4 border-emerald-200 rounded-full"></div>
                <div class="absolute top-0 left-0 w-full h-full border-t-4 border-emerald-500 rounded-full animate-spin"></div>
              </div>
              <p class="text-2xl font-semibold text-gray-700">Changing Password...</p>
              <p class="text-gray-500 mt-2">Please wait while we update your credentials</p>
            </div>
            
            <div id="passwordInfo" class="text-center p-6 bg-gray-50 rounded-xl border border-gray-200">
              <i data-lucide="shield" class="w-16 h-16 text-emerald-500 mx-auto mb-4"></i>
              <h3 class="text-xl font-semibold text-gray-800 mb-2">Keep Your Account Secure</h3>
              <p class="text-gray-600 mb-4">Regularly updating your password helps protect your account from unauthorized access.</p>
              <div class="space-y-3 text-sm text-left">
                <div class="flex items-start">
                  <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 mr-2 mt-0.5"></i>
                  <p>Use a unique password for each of your important accounts</p>
                </div>
                <div class="flex items-start">
                  <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 mr-2 mt-0.5"></i>
                  <p>Avoid using personal information that others might know</p>
                </div>
                <div class="flex items-start">
                  <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 mr-2 mt-0.5"></i>
                  <p>Consider using a password manager to generate and store strong passwords</p>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>

    document.addEventListener('DOMContentLoaded', function () {
      // DOM Elements
      const profileForm = document.getElementById('profileForm');
      const passwordForm = document.getElementById('passwordForm');
      const formTitle = document.getElementById('formTitle');
      const changePasswordBtn = document.getElementById('changePasswordBtn');
      const backToProfileBtn = document.getElementById('backToProfileBtn');
      const fileInput = document.getElementById('fileInput');
      const profileImageContainer = document.getElementById('profileImageContainer');
      const profileName = document.getElementById('profileName');
      const profileUsername = document.getElementById('profileUsername');
      const passwordInfo = document.getElementById('passwordInfo');
      const passwordChanging = document.getElementById('passwordChanging');

      // Form toggle functionality
      changePasswordBtn.addEventListener('click', () => {
        profileForm.classList.add('hidden');
        passwordForm.classList.remove('hidden');
        // Clear any previous password errors when switching to password form
        document.getElementById('currentPassword').value = '';
        document.getElementById('newPassword').value = '';
        document.getElementById('confirmPassword').value = '';
      });

      backToProfileBtn.addEventListener('click', () => {
        passwordForm.classList.add('hidden');
        profileForm.classList.remove('hidden');
        // Clear password fields when going back
        document.getElementById('currentPassword').value = '';
        document.getElementById('newPassword').value = '';
        document.getElementById('confirmPassword').value = '';
      });

      // Profile image upload preview
      fileInput.addEventListener('change', function (e) {
        if (e.target.files && e.target.files[0]) {
          const reader = new FileReader();
          reader.onload = function (e) {
            if (e.target?.result) {
              profileImageContainer.innerHTML = `
                <img src="${e.target.result}" alt="Profile" class="w-full h-full object-cover" />
              `;
              profileImageContainer.classList.remove('bg-emerald-700');
            }
          };
          reader.readAsDataURL(e.target.files[0]);
        }
      });

      // Update profile name and username in real-time
      document.getElementById('username').addEventListener('input', function (e) {
        const usernameElement = profileUsername.querySelector('span');
        if (usernameElement) {
          usernameElement.textContent = e.target.value || 'username';
        }
      });

      document.getElementById('fullName').addEventListener('input', function (e) {
        profileName.textContent = e.target.value || 'Your Name';
      });

      // Password form submission handler
      if (passwordForm) {
        passwordForm.addEventListener('submit', function (e) {
          e.preventDefault();

          // Show loading animation
          passwordInfo.classList.add('hidden');
          passwordChanging.classList.remove('hidden');
          document.getElementById('passwordChangeStatus').classList.remove('hidden');

          // Submit the form after a short delay to show loading animation
          setTimeout(() => {
            this.submit();
          }, 1000);
        });
      }

      // Toggle password visibility
      const toggleButtons = document.querySelectorAll('.toggle-password');
      toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
          const targetId = this.getAttribute('data-target');
          const passwordInput = document.getElementById(targetId);
          const showIcon = this.querySelector('.password-icon-show');
          const hideIcon = this.querySelector('.password-icon-hide');
          
          if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            showIcon.classList.add('hidden');
            hideIcon.classList.remove('hidden');
          } else {
            passwordInput.type = 'password';
            showIcon.classList.remove('hidden');
            hideIcon.classList.add('hidden');
          }
        });
      });

      // Auto-hide alerts after 5 seconds
      setTimeout(() => {
        const alerts = document.querySelectorAll('[id$="Alert"]');
        alerts.forEach(alert => {
          if (alert) alert.style.display = 'none';
        });
      }, 5000);

      // Enhance email field to show not-allowed cursor
      const emailField = document.getElementById('email');
      if (emailField) {
        emailField.addEventListener('mouseover', function () {
          this.style.cursor = 'not-allowed';
        });
        emailField.addEventListener('mouseenter', function () {
          this.style.cursor = 'not-allowed';
        });
      }


      // Get the full name input element
  const fullNameInput = document.getElementById('fullName');
  
  // Create error message element
  const errorElement = document.createElement('p');
  errorElement.className = 'text-sm text-red-500 mt-1 flex items-center';
  errorElement.innerHTML = `
    <i data-lucide="alert-circle" class="w-4 h-4 mr-1"></i>
    <span>Integers are not allowed in the full name</span>
  `;
  errorElement.style.display = 'none';
  
  // Insert error element after the input field
  fullNameInput.parentNode.insertBefore(errorElement, fullNameInput.nextSibling);
  
  // Add input event listener for real-time validation
  fullNameInput.addEventListener('input', function(e) {
    const value = e.target.value;
    
    // Check if the input contains any digits
    if (/\d/.test(value)) {
      errorElement.style.display = 'flex';
      fullNameInput.classList.add('border-red-300');
      fullNameInput.classList.remove('border-gray-300');
    } else {
      errorElement.style.display = 'none';
      fullNameInput.classList.remove('border-red-300');
      fullNameInput.classList.add('border-gray-300');
    }
  });

  
    // Initialize Lucide icons
    lucide.createIcons();
    });
  </script>
</body>

</html>