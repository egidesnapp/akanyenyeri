<?php
/**
 * User Profile - Akanyenyeri Magazine Admin
 * Allows users to update their profile information and picture
 */

session_start();
require_once 'php/auth_check.php';
require_once '../config/database.php';

// Require authentication
requireAuth();

// Get database connection
$pdo = getDB();

// Get current user
$current_user = getCurrentUser();
$user_id = $current_user['id'];

// Handle form submissions
$success_message = '';
$error_message = '';

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        // Validate CSRF token
        if (!validateCSRF($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token');
        }

        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($full_name)) {
            throw new Exception('Full name is required');
        }

        if (empty($email)) {
            throw new Exception('Email is required');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address');
        }

        // Check if email already exists (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            throw new Exception('Email already in use');
        }

        // Verify current password if trying to change password
        if (!empty($new_password)) {
            if (empty($current_password)) {
                throw new Exception('Current password required to change password');
            }

            if (!password_verify($current_password, $current_user['password'])) {
                throw new Exception('Current password is incorrect');
            }

            if (strlen($new_password) < 6) {
                throw new Exception('New password must be at least 6 characters');
            }

            if ($new_password !== $confirm_password) {
                throw new Exception('Passwords do not match');
            }
        }

        // Update profile
        $update_query = "UPDATE users SET full_name = ?, email = ?, updated_at = NOW()";
        $params = [$full_name, $email];

        // Handle profile picture upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['size'] > 0) {
            $file = $_FILES['profile_picture'];
            if ($file['size'] > 2 * 1024 * 1024) { // 2MB limit
                throw new Exception('Profile picture must be less than 2MB');
            }

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception('Invalid image type. Allowed: JPG, PNG, GIF, WebP');
            }

            // Create uploads directory if it doesn't exist
            $uploads_dir = __DIR__ . '/../uploads/profiles/';
            if (!is_dir($uploads_dir)) {
                mkdir($uploads_dir, 0755, true);
            }

            // Delete old profile picture if exists
            if (!empty($current_user['profile_picture'])) {
                $old_pic = __DIR__ . '/../uploads/' . $current_user['profile_picture'];
                if (file_exists($old_pic)) {
                    @unlink($old_pic);
                }
            }

            // Generate unique filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
            $filepath = $uploads_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $profile_picture = 'profiles/' . $filename;
                $update_query .= ", profile_picture = ?";
                $params[] = $profile_picture;
            }
        }

        // Update password if provided
        if (!empty($new_password)) {
            $update_query .= ", password = ?";
            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
        }

        $update_query .= " WHERE id = ?";
        $params[] = $user_id;

        $stmt = $pdo->prepare($update_query);
        $stmt->execute($params);

        $success_message = 'Profile updated successfully!';

        // Refresh current user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_user = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get user profile picture
$profile_pic_url = '';
if (!empty($current_user['profile_picture'])) {
    $profile_pic_url = '../uploads/' . htmlspecialchars($current_user['profile_picture']);
} else {
    // Fallback to placeholder
    $profile_pic_url = 'https://via.placeholder.com/150?text=' . urlencode(substr($current_user['full_name'] ?? $current_user['username'], 0, 1));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Akanyenyeri Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .profile-container { max-width: 800px; margin: 0 auto; }
        .profile-header { display: flex; gap: 30px; align-items: flex-start; margin-bottom: 40px; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .profile-pic { position: relative; }
        .profile-pic-img { width: 150px; height: 150px; border-radius: 10px; object-fit: cover; border: 3px solid #2b6cb0; }
        .profile-pic-label { position: absolute; bottom: 10px; right: 10px; background: #2b6cb0; color: white; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 12px; }
        .profile-pic-label:hover { background: #1e4a7a; }
        .profile-info { flex: 1; }
        .profile-info h2 { margin: 0 0 10px 0; font-size: 24px; }
        .profile-info-item { margin: 10px 0; }
        .profile-info-label { font-size: 12px; color: #666; text-transform: uppercase; }
        .profile-info-value { font-size: 16px; font-weight: 500; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #2b6cb0; box-shadow: 0 0 0 3px rgba(43, 108, 176, 0.1); }
        .form-section { background: #fff; padding: 30px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .form-section h3 { margin: 0 0 20px 0; font-size: 18px; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; transition: all 0.2s; }
        .btn-primary { background: #2b6cb0; color: white; }
        .btn-primary:hover { background: #1e4a7a; }
        .btn-group { display: flex; gap: 10px; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .hidden { display: none; }
        #file-input { display: none; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        <div class="main-content">
            <?php include __DIR__ . '/includes/header.php'; ?>
            <div class="content-area">
                <div class="container profile-container">
                    <h1><i class="fas fa-user-circle"></i> My Profile</h1>

                    <?php if ($success_message): ?>
                        <div class="alert success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>

                    <!-- Profile Header -->
                    <div class="profile-header">
                        <div class="profile-pic">
                            <img id="profile-img" src="<?php echo $profile_pic_url; ?>" alt="Profile Picture" class="profile-pic-img">
                            <label for="file-input" class="profile-pic-label">
                                <i class="fas fa-camera"></i> Change
                            </label>
                        </div>
                        <div class="profile-info">
                            <h2><?php echo htmlspecialchars($current_user['full_name']); ?></h2>
                            <div class="profile-info-item">
                                <div class="profile-info-label">Username</div>
                                <div class="profile-info-value"><?php echo htmlspecialchars($current_user['username']); ?></div>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-label">Role</div>
                                <div class="profile-info-value"><?php echo ucfirst(htmlspecialchars($current_user['role'])); ?></div>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-label">Member Since</div>
                                <div class="profile-info-value"><?php echo date('M d, Y', strtotime($current_user['created_at'])); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Profile Form -->
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="update_profile" value="1">
                        <input type="file" id="file-input" name="profile_picture" accept="image/*" onchange="handleImageSelect(event)">

                        <!-- Personal Information -->
                        <div class="form-section">
                            <h3><i class="fas fa-user"></i> Personal Information</h3>

                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($current_user['full_name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($current_user['email']); ?>" required>
                            </div>
                        </div>

                        <!-- Change Password -->
                        <div class="form-section">
                            <h3><i class="fas fa-lock"></i> Change Password</h3>
                            <p style="color: #666; font-size: 14px; margin: 0 0 20px 0;">Leave blank to keep current password</p>

                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password">
                            </div>

                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password">
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password">
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="form-section">
                            <div class="btn-group">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                                <a href="dashboard.php" class="btn" style="background: #6b7280; color: white; text-decoration: none; display: inline-flex; align-items: center;"><i class="fas fa-arrow-left"></i> Back</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function handleImageSelect(event) {
            const file = event.target.files[0];
            if (file) {
                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB');
                    event.target.value = '';
                    return;
                }

                // Preview image
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profile-img').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>
