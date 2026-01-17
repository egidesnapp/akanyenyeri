<?php
/**
 * Advertisements Management - Akanyenyeri Admin Panel
 */

require_once '../database/config/database.php';
require_once 'php/auth_check.php';

// Require authentication and admin role
requireAuth();
requireRole('admin', 'You need admin privileges to manage advertisements');

// Get database connection
$pdo = getDB();

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'change_logo') {
            // Handle logo change
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $logo_dir = '../logo/';
                if (!is_dir($logo_dir)) {
                    mkdir($logo_dir, 0755, true);
                }

                // Delete existing logo files
                $existing_logos = glob($logo_dir . '*');
                foreach ($existing_logos as $existing_logo) {
                    if (is_file($existing_logo)) {
                        unlink($existing_logo);
                    }
                }

                // Upload new logo
                $filename = 'akanyenyeri logo.' . strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                $target_path = $logo_dir . $filename;

                if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_path)) {
                    $message = 'Logo updated successfully! The new logo will appear throughout the website.';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to upload logo.';
                    $messageType = 'error';
                }
            } else {
                $message = 'Please select a logo file to upload.';
                $messageType = 'error';
            }
        } elseif ($action === 'add') {
            // Handle advertisement addition
            $title = trim($_POST['title'] ?? '');
            $link_url = trim($_POST['link_url'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $display_order = (int)($_POST['display_order'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
            $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

            // Handle file upload
            $image_path = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/advertisements/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $filename = uniqid() . '_' . basename($_FILES['image']['name']);
                $target_path = $upload_dir . $filename;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    $image_path = 'uploads/advertisements/' . $filename;
                } else {
                    $message = 'Failed to upload image.';
                    $messageType = 'error';
                }
            }

            if (empty($image_path)) {
                $message = 'Please select an image to upload.';
                $messageType = 'error';
            } elseif (empty($title)) {
                $message = 'Please enter a title for the advertisement.';
                $messageType = 'error';
            } else {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO advertisements (title, image_path, link_url, category, display_order, is_active, start_date, end_date, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$title, $image_path, $link_url, $category, $display_order, $is_active, $start_date, $end_date, $_SESSION['user_id']]);

                    $message = 'Advertisement added successfully!';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Error adding advertisement: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        } elseif ($action === 'edit') {
            // Handle advertisement editing
            $id = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $link_url = trim($_POST['link_url'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $display_order = (int)($_POST['display_order'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
            $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

            $image_path = $_POST['existing_image'] ?? '';

            // Handle file upload for updates
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/advertisements/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $filename = uniqid() . '_' . basename($_FILES['image']['name']);
                $target_path = $upload_dir . $filename;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    $image_path = 'uploads/advertisements/' . $filename;
                }
            }

            if (empty($title)) {
                $message = 'Please enter a title for the advertisement.';
                $messageType = 'error';
            } else {
                try {
                    $stmt = $pdo->prepare("
                        UPDATE advertisements SET
                        title = ?, image_path = ?, link_url = ?, category = ?,
                        display_order = ?, is_active = ?, start_date = ?, end_date = ?,
                        updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?
                    ");
                    $stmt->execute([$title, $image_path, $link_url, $category, $display_order, $is_active, $start_date, $end_date, $id]);

                    $message = 'Advertisement updated successfully!';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Error updating advertisement: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        } elseif ($action === 'delete') {
            // Handle advertisement deletion
            $id = (int)($_POST['id'] ?? 0);

            try {
                $stmt = $pdo->prepare("DELETE FROM advertisements WHERE id = ?");
                $stmt->execute([$id]);

                $message = 'Advertisement deleted successfully!';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error deleting advertisement: ' . $e->getMessage();
                $messageType = 'error';
            }
        } elseif ($action === 'toggle') {
            // Handle status toggle
            $id = (int)($_POST['id'] ?? 0);

            try {
                $stmt = $pdo->prepare("UPDATE advertisements SET is_active = NOT is_active WHERE id = ?");
                $stmt->execute([$id]);

                $message = 'Advertisement status updated!';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error updating advertisement status: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Get all advertisements
try {
    $stmt = $pdo->query("SELECT * FROM advertisements ORDER BY display_order ASC, created_at DESC");
    $advertisements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $advertisements = [];
}

// Get editing advertisement if requested
$editing_ad = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    foreach ($advertisements as $ad) {
        if ($ad['id'] == $edit_id) {
            $editing_ad = $ad;
            break;
        }
    }
}

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Advertisements - Admin</title>
	<link rel="stylesheet" href="css/admin-layout.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
	<style>
		:root{--accent:#2b6cb0;--muted:#6b7280}
		.container{max-width:1200px;margin:0 auto;padding:24px}
		.media-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}
		.media-title h1{margin:0;font-size:24px}
		.page-actions .btn{padding:8px 12px;border-radius:6px;border:none;cursor:pointer}
		.btn-primary{background:var(--accent);color:#fff}
		.alert{padding:12px;border-radius:6px;margin-bottom:14px}
		.alert.success{background:#e6fffa;color:#055a4f}
		.alert.error{background:#ffe6e6;color:#7a1f1f}

		/* Add advertisement card */
		.upload-card{background:#fff;border-radius:10px;padding:18px;border:1px solid #eef2f6;box-shadow:0 6px 18px rgba(16,24,40,0.04)}
		.upload-zone{border:2px dashed #dbeafe;border-radius:8px;padding:28px;text-align:center;cursor:pointer;transition:all .18s}
		.upload-zone:hover{background:#f8fbff}
		.upload-meta{display:grid;grid-template-columns:1fr auto;gap:12px;align-items:center;margin-top:14px}
		.meta-input{padding:10px;border:1px solid #e6eef5;border-radius:8px}
		.upload-btn{background:linear-gradient(90deg,var(--accent),#553c9a);color:#fff;padding:10px 14px;border-radius:8px;border:none;cursor:pointer}

		/* Grid */
		.media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px}
		.media-item{background:#fff;border:1px solid #eef2f6;border-radius:10px;overflow:hidden;display:flex;flex-direction:column}
		.thumb{height:140px;background:#f3f6f9;display:flex;align-items:center;justify-content:center}
		.thumb img{width:100%;height:100%;object-fit:cover}
		.m-info{padding:12px;display:flex;flex-direction:column;flex:1}
		.m-name{font-size:14px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:8px}
		.m-actions{margin-top:auto;display:flex;gap:6px;flex-wrap:wrap}
		.action-btn{padding:6px 8px;border-radius:6px;border:none;cursor:pointer;font-size:12px;font-weight:500;transition:all 0.2s ease;display:flex;align-items:center;gap:4px;text-decoration:none;flex:1;justify-content:center}
		.action-edit{background:#3b82f6;color:#fff}.action-edit:hover{background:#2563eb;transform:translateY(-1px);box-shadow:0 2px 6px rgba(59,130,246,0.3)}
		.action-delete{background:#ef4444;color:#fff}.action-delete:hover{background:#dc2626;transform:translateY(-1px);box-shadow:0 2px 6px rgba(239,68,68,0.3)}

		/* Stats */
		.stats{display:flex;gap:12px;align-items:center;margin:16px 0}
		.stat{background:#fff;padding:12px;border-radius:8px;border:1px solid #eef2f6}

		/* Modal */
		.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:1000;align-items:center;justify-content:center}
		.modal.active{display:flex}
		.modal-content{position:relative;max-width:500px;width:100%;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.3);margin:1rem}
		.modal-header{padding:16px;background:#f3f4f6;border-bottom:1px solid #e5e7eb}
		.modal-title{margin:0;font-size:18px}
		.modal-body{padding:20px}
		.modal-footer{padding:16px;border-top:1px solid #e5e7eb;display:flex;gap:12px;justify-content:flex-end}

		.form-group{margin-bottom:16px}
		.form-label{display:block;margin-bottom:6px;font-weight:600}
		.form-input{width:100%;padding:10px;border:1px solid #e6eef5;border-radius:8px}
		.form-text{font-size:12px;color:var(--muted);margin-top:4px}

		@media(max-width:800px){.upload-meta{grid-template-columns:1fr}}
	</style>
</head>
<body>
	<div class="admin-wrapper">
		<?php include __DIR__ . '/includes/sidebar.php'; ?>
		<div class="main-content">
			<?php include __DIR__ . '/includes/header.php'; ?>
			<div class="content-area">
				<div class="container">

					<div class="media-header">
						<div class="media-title">
							<h1><i class="fas fa-ad"></i> Hero Advertisements</h1>
							<p style="margin:4px 0 0 0;font-size:14px;color:var(--muted)">Manage rotating advertisements for your website hero section</p>
						</div>
						<div class="page-actions">
							<button type="button" class="btn btn-primary" onclick="openAddModal()"><i class="fas fa-plus"></i> Add Advertisement</button>
						</div>
					</div>

					<?php if ($message): ?>
						<div class="alert <?php echo $messageType === 'success' ? 'success' : 'error'; ?>"><?php echo htmlspecialchars($message); ?></div>
					<?php endif; ?>

					<!-- Hero Section Guidelines -->
					<div class="hero-guidelines" style="background:linear-gradient(135deg,#f8fafc,#e2e8f0);border:1px solid #cbd5e0;border-radius:12px;padding:20px;margin-bottom:20px">
						<h4 style="margin:0 0 12px 0;color:#1a202c;display:flex;align-items:center;gap:8px">
							<i class="fas fa-lightbulb" style="color:#f59e0b"></i>
							Hero Section Guidelines
						</h4>
						<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px">
							<div style="background:#fff;padding:12px;border-radius:8px;border:1px solid #e2e8f0">
								<div style="font-weight:600;color:#2d3748;margin-bottom:4px">Recommended Dimensions</div>
								<div style="font-size:24px;font-weight:700;color:#3182ce">1920 x 600px</div>
								<div style="font-size:12px;color:#718096">16:6.25 aspect ratio</div>
							</div>
							<div style="background:#fff;padding:12px;border-radius:8px;border:1px solid #e2e8f0">
								<div style="font-weight:600;color:#2d3748;margin-bottom:4px">Alternative Sizes</div>
								<div style="font-size:14px;color:#4a5568">1600x500, 1440x450, 1200x375px</div>
								<div style="font-size:12px;color:#718096">Maintain 16:6.25 ratio</div>
							</div>
							<div style="background:#fff;padding:12px;border-radius:8px;border:1px solid #e2e8f0">
								<div style="font-weight:600;color:#2d3748;margin-bottom:4px">File Format</div>
								<div style="font-size:14px;color:#38a169">JPG, PNG, WebP</div>
								<div style="font-size:12px;color:#718096">Max 5MB per image</div>
							</div>
							<div style="background:#fff;padding:12px;border-radius:8px;border:1px solid #e2e8f0">
								<div style="font-weight:600;color:#2d3748;margin-bottom:4px">Best Practices</div>
								<div style="font-size:12px;color:#4a5568">High contrast text, clear focal point, brand colors</div>
							</div>
						</div>
					</div>

					<!-- Current Logo Display -->
					<div class="current-logo-section" style="background:linear-gradient(135deg,#667eea,#764ba2);border-radius:12px;padding:24px;margin-bottom:24px;color:white">
						<h4 style="margin:0 0 16px 0;display:flex;align-items:center;gap:8px">
							<i class="fas fa-image"></i>
							Current Website Logo
						</h4>
						<div style="display:flex;align-items:center;gap:20px">
							<div style="background:white;padding:12px;border-radius:8px">
								<?php
								$logo_files = glob('../logo/*');
								$current_logo = '';
								if (!empty($logo_files)) {
									$current_logo = basename($logo_files[0]);
								}
								?>
								<?php if ($current_logo): ?>
									<img src="../logo/<?php echo htmlspecialchars($current_logo); ?>" alt="Current Logo" style="max-width:120px;max-height:60px;object-fit:contain">
								<?php else: ?>
									<div style="width:120px;height:60px;background:#f3f4f6;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#6b7280;font-size:12px">No Logo</div>
								<?php endif; ?>
							</div>
							<div style="flex:1">
								<div style="font-weight:600;margin-bottom:4px">File: <?php echo $current_logo ?: 'No logo uploaded'; ?></div>
								<div style="font-size:14px;opacity:0.9;margin-bottom:12px">This logo appears on the website header, admin panel, and footer</div>
								<button type="button" class="btn btn-light" onclick="openLogoModal()" style="background:white;color:#667eea;border:none;padding:8px 16px;border-radius:6px;font-weight:500">
									<i class="fas fa-upload"></i> Change Logo
								</button>
							</div>
						</div>
					</div>

					<!-- Stats -->
					<div class="stats">
						<div class="stat">
							<div style="font-size:18px;font-weight:700;color:#1a202c"><?php echo count($advertisements); ?></div>
							<div style="font-size:12px;color:var(--muted)">Total Ads</div>
						</div>
						<div class="stat">
							<div style="font-size:18px;font-weight:700;color:#38a169"><?php echo count(array_filter($advertisements, function($ad) { return $ad['is_active']; })); ?></div>
							<div style="font-size:12px;color:var(--muted)">Active</div>
						</div>
						<div class="stat">
							<div style="font-size:18px;font-weight:700;color:#f59e0b"><?php echo count(array_filter($advertisements, function($ad) { return !$ad['is_active']; })); ?></div>
							<div style="font-size:12px;color:var(--muted)">Inactive</div>
						</div>
						<div class="stat">
							<div style="font-size:18px;font-weight:700;color:#805ad5"><?php echo count(array_unique(array_column($advertisements, 'category'))); ?></div>
							<div style="font-size:12px;color:var(--muted)">Categories</div>
						</div>
					</div>

					<!-- Add advertisement card -->
					<div class="upload-card" id="addAdCard" style="display:none;">
						<form id="addAdForm" method="post" enctype="multipart/form-data">
							<input type="hidden" name="action" value="add">
							<div class="upload-meta">
								<div style="display:grid;gap:12px;">
									<input class="meta-input" type="text" name="title" placeholder="Advertisement title" required>
									<input class="meta-input" type="url" name="link_url" placeholder="Link URL (optional)">
									<select class="meta-input" name="category">
										<option value="">General</option>
										<option value="Politics">Politics</option>
										<option value="Sports">Sports</option>
										<option value="Technology">Technology</option>
										<option value="Business">Business</option>
										<option value="Entertainment">Entertainment</option>
										<option value="Health">Health</option>
									</select>
									<input class="meta-input" type="number" name="display_order" placeholder="Display order" value="0">
								</div>
								<div>
									<input type="file" id="adImage" name="image" accept="image/*" required style="margin-bottom:12px;">
									<label style="display:block;margin-bottom:8px;"><input type="checkbox" name="is_active" checked> Active</label>
									<button class="upload-btn" type="submit">Add Advertisement</button>
								</div>
							</div>
						</form>
					</div>

					<?php if (empty($advertisements)): ?>
						<div style="margin-top:30px;padding:40px;text-align:center;background:#fff;border-radius:8px;border:1px solid #eef2f6">
							<i class="fas fa-ad" style="font-size:48px;color:#cbd5e0;margin-bottom:16px;"></i>
							<h3 style="margin:0 0 8px 0;color:#4a5568">No advertisements yet</h3>
							<p style="margin:0 0 16px 0;color:#718096">Add your first advertisement to get started</p>
							<button type="button" class="btn btn-primary" onclick="openAddModal()"><i class="fas fa-plus"></i> Add Advertisement</button>
						</div>
					<?php else: ?>
						<div class="media-grid" style="margin-top:18px">
							<?php foreach ($advertisements as $ad): ?>
							<div class="media-item">
								<div class="thumb">
									<?php if (file_exists(__DIR__ . '/../' . $ad['image_path'])): ?>
										<img src="../<?php echo htmlspecialchars($ad['image_path']); ?>" alt="<?php echo htmlspecialchars($ad['title']); ?>">
									<?php else: ?>
										<div style="font-size:36px">📄</div>
									<?php endif; ?>
									<div style="position:absolute;top:5px;right:5px;background:rgba(0,0,0,0.7);color:#fff;padding:2px 6px;border-radius:4px;font-size:10px;text-transform:capitalize;">
										<?php echo htmlspecialchars($ad['category'] ?: 'general'); ?>
									</div>
								</div>
								<div class="m-info">
									<div class="m-name"><?php echo htmlspecialchars($ad['title']); ?></div>
									<div style="font-size:12px;color:var(--muted);margin-bottom:8px">
										<?php echo $ad['is_active'] ? '<span style="color:#38a169">● Active</span>' : '<span style="color:#718096">● Inactive</span>'; ?> • Order: <?php echo $ad['display_order']; ?>
									</div>
									<div class="m-actions">
										<button class="action-btn action-edit" onclick="editAd(<?php echo $ad['id']; ?>)">Edit</button>
										<button class="action-btn action-delete" onclick="deleteAd(<?php echo $ad['id']; ?>, '<?php echo htmlspecialchars($ad['title']); ?>')">Delete</button>
									</div>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

				</div>
			</div>
		</div>
	</div>

	<!-- Edit Modal -->
	<div id="editModal" class="modal">
		<div class="modal-content">
			<div class="modal-header">
				<h3 class="modal-title">Edit Advertisement</h3>
				<button onclick="closeModal()" style="background:none;border:none;font-size:20px;cursor:pointer;">×</button>
			</div>
			<div class="modal-body">
				<form id="editForm" method="post" enctype="multipart/form-data">
					<input type="hidden" name="action" value="edit">
					<input type="hidden" name="id" id="editId">
					<input type="hidden" name="existing_image" id="existingImage">

					<div class="form-group">
						<label class="form-label">Title</label>
						<input class="form-input" type="text" name="title" id="editTitle" required>
					</div>

					<div class="form-group">
						<label class="form-label">Current Image</label>
						<img id="currentImage" style="max-width:100%;max-height:150px;border-radius:8px;margin-bottom:8px;">
					</div>

					<div class="form-group">
						<label class="form-label">New Image (optional)</label>
						<input class="form-input" type="file" name="image" accept="image/*">
						<div class="form-text">Leave empty to keep current image</div>
					</div>

					<div class="form-group">
						<label class="form-label">Link URL</label>
						<input class="form-input" type="url" name="link_url" id="editLink">
					</div>

					<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
						<div class="form-group">
							<label class="form-label">Category</label>
							<select class="form-input" name="category" id="editCategory">
								<option value="">General</option>
								<option value="Politics">Politics</option>
								<option value="Sports">Sports</option>
								<option value="Technology">Technology</option>
								<option value="Business">Business</option>
								<option value="Entertainment">Entertainment</option>
								<option value="Health">Health</option>
							</select>
						</div>

						<div class="form-group">
							<label class="form-label">Display Order</label>
							<input class="form-input" type="number" name="display_order" id="editOrder" value="0">
						</div>
					</div>

					<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
						<div class="form-group">
							<label class="form-label">Start Date</label>
							<input class="form-input" type="datetime-local" name="start_date" id="editStartDate">
						</div>

						<div class="form-group">
							<label class="form-label">End Date</label>
							<input class="form-input" type="datetime-local" name="end_date" id="editEndDate">
						</div>
					</div>

					<label style="display:block;margin-bottom:12px;"><input type="checkbox" name="is_active" id="editActive" checked> Active</label>
				</form>
			</div>
			<div class="modal-footer">
				<button onclick="closeModal()" style="padding:8px 16px;border:1px solid #ddd;border-radius:6px;background:#f3f4f6;">Cancel</button>
				<button onclick="document.getElementById('editForm').submit()" style="padding:8px 16px;border:none;border-radius:6px;background:var(--accent);color:#fff;">Update</button>
			</div>
		</div>
	</div>

	<!-- Logo Change Modal -->
	<div id="logoModal" class="modal">
		<div class="modal-content">
			<div class="modal-header">
				<h3 class="modal-title">Change Website Logo</h3>
				<button onclick="closeLogoModal()" style="background:none;border:none;font-size:20px;cursor:pointer;">×</button>
			</div>
			<div class="modal-body">
				<form id="logoForm" method="post" enctype="multipart/form-data">
					<input type="hidden" name="action" value="change_logo">

					<div class="form-group">
						<label class="form-label">Current Logo</label>
						<div style="background:#f8fafc;padding:12px;border-radius:8px;margin-bottom:12px;">
							<?php if ($current_logo): ?>
								<img src="../logo/<?php echo htmlspecialchars($current_logo); ?>" alt="Current Logo" style="max-width:200px;max-height:100px;object-fit:contain;border-radius:4px;">
							<?php else: ?>
								<div style="width:200px;height:60px;background:#e2e8f0;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#6b7280;font-size:14px;">No Logo</div>
							<?php endif; ?>
						</div>
					</div>

					<div class="form-group">
						<label class="form-label">New Logo File</label>
						<input class="form-input" type="file" name="logo" accept="image/*" required>
						<div class="form-text">Supported formats: JPG, PNG, SVG, WebP. Recommended size: 200x60px or smaller for crisp display.</div>
					</div>

					<div style="background:#fff3cd;border:1px solid #ffeaa7;border-radius:6px;padding:12px;margin-top:16px;">
						<strong>⚠️ Important:</strong> This will replace the logo everywhere on your website including the header, admin panel, and footer. Make sure you have a backup of your current logo.
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button onclick="closeLogoModal()" style="padding:8px 16px;border:1px solid #ddd;border-radius:6px;background:#f3f4f6;">Cancel</button>
				<button onclick="document.getElementById('logoForm').submit()" style="padding:8px 16px;border:none;border-radius:6px;background:#dc2626;color:#fff;">Replace Logo</button>
			</div>
		</div>
	</div>

	<script>
		let editingAd = null;

		function openAddModal(){
			document.getElementById('addAdCard').style.display = 'block';
			document.getElementById('addAdCard').scrollIntoView({behavior:'smooth'});
		}

		function openLogoModal(){
			document.getElementById('logoModal').classList.add('active');
		}

		function closeLogoModal(){
			document.getElementById('logoModal').classList.remove('active');
		}

		function editAd(id){
			// Find ad data and populate modal
			<?php foreach ($advertisements as $ad): ?>
			if(<?php echo $ad['id']; ?> === id){
				document.getElementById('editId').value = '<?php echo $ad['id']; ?>';
				document.getElementById('editTitle').value = '<?php echo htmlspecialchars($ad['title']); ?>';
				document.getElementById('editLink').value = '<?php echo htmlspecialchars($ad['link_url'] ?? ''); ?>';
				document.getElementById('editCategory').value = '<?php echo htmlspecialchars($ad['category'] ?? ''); ?>';
				document.getElementById('editOrder').value = '<?php echo $ad['display_order']; ?>';
				document.getElementById('editActive').checked = <?php echo $ad['is_active'] ? 'true' : 'false'; ?>;
				document.getElementById('existingImage').value = '<?php echo htmlspecialchars($ad['image_path']); ?>';
				document.getElementById('currentImage').src = '../<?php echo htmlspecialchars($ad['image_path']); ?>';
				document.getElementById('editStartDate').value = '<?php echo $ad['start_date'] ? date('Y-m-d\TH:i', strtotime($ad['start_date'])) : ''; ?>';
				document.getElementById('editEndDate').value = '<?php echo $ad['end_date'] ? date('Y-m-d\TH:i', strtotime($ad['end_date'])) : ''; ?>';
				document.getElementById('editModal').classList.add('active');
				break;
			}
			<?php endforeach; ?>
		}

		function deleteAd(id, title){
			if(confirm('Delete "' + title + '"?')){
				const form = document.createElement('form');
				form.method = 'post';
				form.innerHTML = '<input name="action" value="delete"><input name="id" value="' + id + '">';
				document.body.appendChild(form);
				form.submit();
			}
		}

		function closeModal(){
			document.getElementById('editModal').classList.remove('active');
		}

		// Close modal when clicking outside
		document.getElementById('editModal').addEventListener('click', function(e){
			if(e.target === this) closeModal();
		});
	</script>
</body>
</html>

<!-- Add Advertisement Modal -->
<div class="modal fade" id="addAdModal" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
        <div class="modal-content" style="margin: 1rem;">
            <div class="modal-header">
                <h5 class="modal-title">Add New Advertisement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body" style="padding: 1.5rem;">
                    <input type="hidden" name="action" value="add">

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="adTitle" class="form-label">Title *</label>
                            <input type="text" class="form-control" id="adTitle" name="title" required>
                        </div>

                        <div class="col-12">
                            <label for="adImage" class="form-label">Image *</label>
                            <input type="file" class="form-control" id="adImage" name="image" accept="image/*" required>
                            <div class="form-text small">Recommended: 1920x600px</div>
                        </div>

                        <div class="col-12">
                            <label for="adLink" class="form-label">Link URL</label>
                            <input type="url" class="form-control" id="adLink" name="link_url" placeholder="https://example.com">
                        </div>

                        <div class="col-md-6">
                            <label for="adCategory" class="form-label">Category</label>
                            <select class="form-select" id="adCategory" name="category">
                                <option value="">General</option>
                                <option value="Politics">Politics</option>
                                <option value="Sports">Sports</option>
                                <option value="Technology">Technology</option>
                                <option value="Business">Business</option>
                                <option value="Entertainment">Entertainment</option>
                                <option value="Health">Health</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="adOrder" class="form-label">Display Order</label>
                            <input type="number" class="form-control" id="adOrder" name="display_order" value="0" min="0">
                        </div>

                        <div class="col-md-6">
                            <label for="adStartDate" class="form-label">Start Date</label>
                            <input type="datetime-local" class="form-control" id="adStartDate" name="start_date">
                        </div>

                        <div class="col-md-6">
                            <label for="adEndDate" class="form-label">End Date</label>
                            <input type="datetime-local" class="form-control" id="adEndDate" name="end_date">
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="adActive" name="is_active" checked>
                                <label class="form-check-label" for="adActive">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 1rem 1.5rem;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Advertisement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Advertisement Modal -->
<?php if ($editing_ad): ?>
<div class="modal fade show" id="editAdModal" tabindex="-1" style="display: block;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Advertisement</h5>
                <a href="advertisements.php" class="btn-close"></a>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?php echo $editing_ad['id']; ?>">
                    <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($editing_ad['image_path']); ?>">

                    <div class="mb-3">
                        <label for="editAdTitle" class="form-label">Title *</label>
                        <input type="text" class="form-control" id="editAdTitle" name="title"
                               value="<?php echo htmlspecialchars($editing_ad['title']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Current Image</label>
                        <div>
                            <img src="../<?php echo htmlspecialchars($editing_ad['image_path']); ?>"
                                 alt="Current" style="max-width: 200px; max-height: 100px; object-fit: cover; border-radius: 5px;">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="editAdImage" class="form-label">New Image (optional)</label>
                        <input type="file" class="form-control" id="editAdImage" name="image" accept="image/*">
                        <div class="form-text">Leave empty to keep current image</div>
                    </div>

                    <div class="mb-3">
                        <label for="editAdLink" class="form-label">Link URL (optional)</label>
                        <input type="url" class="form-control" id="editAdLink" name="link_url"
                               value="<?php echo htmlspecialchars($editing_ad['link_url'] ?? ''); ?>" placeholder="https://example.com">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editAdCategory" class="form-label">Category</label>
                                <select class="form-control" id="editAdCategory" name="category">
                                    <option value="">General</option>
                                    <option value="Politics" <?php echo ($editing_ad['category'] === 'Politics') ? 'selected' : ''; ?>>Politics</option>
                                    <option value="Sports" <?php echo ($editing_ad['category'] === 'Sports') ? 'selected' : ''; ?>>Sports</option>
                                    <option value="Technology" <?php echo ($editing_ad['category'] === 'Technology') ? 'selected' : ''; ?>>Technology</option>
                                    <option value="Business" <?php echo ($editing_ad['category'] === 'Business') ? 'selected' : ''; ?>>Business</option>
                                    <option value="Entertainment" <?php echo ($editing_ad['category'] === 'Entertainment') ? 'selected' : ''; ?>>Entertainment</option>
                                    <option value="Health" <?php echo ($editing_ad['category'] === 'Health') ? 'selected' : ''; ?>>Health</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editAdOrder" class="form-label">Display Order</label>
                                <input type="number" class="form-control" id="editAdOrder" name="display_order"
                                       value="<?php echo $editing_ad['display_order']; ?>" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editAdStartDate" class="form-label">Start Date (optional)</label>
                                <input type="datetime-local" class="form-control" id="editAdStartDate" name="start_date"
                                       value="<?php echo $editing_ad['start_date'] ? date('Y-m-d\TH:i', strtotime($editing_ad['start_date'])) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editAdEndDate" class="form-label">End Date (optional)</label>
                                <input type="datetime-local" class="form-control" id="editAdEndDate" name="end_date"
                                       value="<?php echo $editing_ad['end_date'] ? date('Y-m-d\TH:i', strtotime($editing_ad['end_date'])) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="editAdActive" name="is_active"
                                   <?php echo $editing_ad['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="editAdActive">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="advertisements.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Advertisement</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal-backdrop fade show"></div>
<?php endif; ?>

<script>
// Delete advertisement confirmation
document.querySelectorAll('.delete-ad').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const title = this.getAttribute('data-title');

        if (confirm(`Are you sure you want to delete "${title}"?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
});

// Toggle advertisement status
document.querySelectorAll('.toggle-status').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.getAttribute('data-id');

        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    });
});
</script>
</body>
</html>
