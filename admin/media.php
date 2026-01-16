<?php
session_start();
require_once __DIR__ . '/php/auth_check.php';
requireAuth();

$pdo = getDB();
$db = $pdo;

$message = '';
$messageType = '';

// Config
$MAX_FILE_SIZE = 15 * 1024 * 1024; // 15MB
$ALLOWED_MIME = []; // Accept all file types (basic security check in processSingleFile)

// Ensure CSRF token
if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['media_file'])) {
	if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
		$message = 'Security token mismatch.';
		$messageType = 'error';
	} else {
		$files = $_FILES['media_file'];
		$uploadedCount = 0;
		$failedCount = 0;
		$errors = [];

		// Handle both single file and multiple files
		if (is_array($files['name'])) {
			// Multiple files
			$fileCount = count($files['name']);
			for ($i = 0; $i < $fileCount; $i++) {
				$file = [
					'name' => $files['name'][$i],
					'type' => $files['type'][$i],
					'tmp_name' => $files['tmp_name'][$i],
					'error' => $files['error'][$i],
					'size' => $files['size'][$i]
				];

				$result = processSingleFile($file, $db);
				if ($result['success']) {
					$uploadedCount++;
				} else {
					$failedCount++;
					$errors[] = $result['message'];
				}
			}
		} else {
			// Single file
			$result = processSingleFile($files, $db);
			if ($result['success']) {
				$uploadedCount++;
			} else {
				$failedCount++;
				$errors[] = $result['message'];
			}
		}

		// Set overall message
		if ($uploadedCount > 0 && $failedCount === 0) {
			$message = $uploadedCount === 1 ? 'File uploaded successfully.' : "$uploadedCount files uploaded successfully.";
			$messageType = 'success';
		} elseif ($uploadedCount > 0 && $failedCount > 0) {
			$message = "$uploadedCount files uploaded, $failedCount failed. Errors: " . implode('; ', array_slice($errors, 0, 3));
			$messageType = 'warning';
		} else {
			$message = 'Upload failed: ' . implode('; ', array_slice($errors, 0, 3));
			$messageType = 'error';
		}
	}
}

function processSingleFile($f, $db) {
	try {
		global $MAX_FILE_SIZE, $ALLOWED_MIME;

		if ($f['error'] !== UPLOAD_ERR_OK) {
			return ['success' => false, 'message' => 'Upload error for ' . $f['name']];
		}

		if ($f['size'] > $MAX_FILE_SIZE) {
			return ['success' => false, 'message' => $f['name'] . ' too large (max 15MB)'];
		}

		// Get actual MIME type from file content
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$actual_mime = finfo_file($finfo, $f['tmp_name']);
		finfo_close($finfo);

		// Basic security: block potentially dangerous file types
		$dangerous_types = [
			'application/x-php',
			'application/php',
			'application/x-httpd-php',
			'application/x-httpd-php-source',
			'text/php',
			'application/javascript',
			'application/x-javascript',
			'text/javascript'
		];

		if (in_array($actual_mime, $dangerous_types) || in_array($f['type'], $dangerous_types)) {
			return ['success' => false, 'message' => $f['name'] . ' file type not allowed for security reasons'];
		}

		// Check for duplicate file (same name, size, and type)
		$duplicateCheck = $db->prepare('SELECT id FROM media WHERE original_name = ? AND file_size = ? AND mime_type = ? LIMIT 1');
		$duplicateCheck->execute([$f['name'], $f['size'], $f['type']]);
		if ($duplicateCheck->fetch()) {
			return ['success' => false, 'message' => $f['name'] . ' already exists'];
		}

		$ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
		$safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($f['name']));
		$filename = time() . '_' . bin2hex(random_bytes(6)) . '_' . $safe;

		// Determine directory based on file type
		$file_type = $f["type"];
		if (strpos($file_type, 'image/') === 0) {
			$sub_dir = 'images';
		} elseif (strpos($file_type, 'video/') === 0) {
			$sub_dir = 'videos';
		} elseif (strpos($file_type, 'audio/') === 0) {
			$sub_dir = 'audio';
		} elseif (in_array(strtolower($ext), ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf'])) {
			$sub_dir = 'documents';
		} else {
			$sub_dir = 'others';
		}

		$uploadDir = __DIR__ . '/../uploads/' . $sub_dir . '/';
		if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
		$target = $uploadDir . $filename;

		if (move_uploaded_file($f['tmp_name'], $target)) {
			$user_id = $_SESSION['admin_user_id'] ?? null;
			if (!$user_id) {
				@unlink($target);
				return ['success' => false, 'message' => 'User not authenticated'];
			}

			$stmt = $db->prepare('INSERT INTO media (filename, original_name, file_path, file_size, mime_type, alt_text, caption, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
			$result = $stmt->execute([
				$filename,
				$f['name'],
				$sub_dir . '/' . $filename,
				$f['size'],
				$f['type'],
				trim($_POST['alt_text'] ?? ''),
				trim($_POST['caption'] ?? ''),
				$user_id,
			]);

			if ($result) {
				return ['success' => true, 'message' => 'Uploaded ' . $f['name']];
			} else {
				@unlink($target);
				return ['success' => false, 'message' => 'Database error for ' . $f['name']];
			}
		} else {
			return ['success' => false, 'message' => 'Could not save ' . $f['name']];
		}
	} catch (Exception $e) {
		return ['success' => false, 'message' => 'Error with ' . $f['name'] . ': ' . $e->getMessage()];
	}
}

// Handle get images for post editor
if (isset($_GET['action']) && $_GET['action'] === 'get_images') {
	header('Content-Type: application/json');
	try {
		$stmt = $db->query('SELECT id, filename, original_name, alt_text, caption FROM media WHERE mime_type LIKE "image/%" ORDER BY created_at DESC LIMIT 50');
		$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$imageData = array_map(function($img) {
			return [
				'id' => $img['id'],
				'name' => $img['original_name'],
				'url' => '../uploads/images/' . $img['filename'],
				'alt' => $img['alt_text'] ?: $img['original_name'],
				'caption' => $img['caption'] ?: ''
			];
		}, $images);

		echo json_encode(['success' => true, 'images' => $imageData]);
	} catch (Exception $e) {
		echo json_encode(['success' => false, 'message' => $e->getMessage()]);
	}
	exit;
}

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['id'])) {
	$id = intval($_GET['id']);
	try {
		$stmt = $db->prepare('SELECT filename FROM media WHERE id = ? LIMIT 1');
		$stmt->execute([$id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if ($row) {
			$path = __DIR__ . '/../uploads/images/' . $row['filename'];
			if (file_exists($path)) @unlink($path);
			$del = $db->prepare('DELETE FROM media WHERE id = ?');
			$del->execute([$id]);
			$message = 'File deleted.';
			$messageType = 'success';
		}
	} catch (Exception $e) {
		$message = 'Error deleting file.';
		$messageType = 'error';
	}
}

// Handle edit metadata (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_meta') {
	header('Content-Type: application/json');
	if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
		echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
		exit;
	}
	$id = intval($_POST['id'] ?? 0);
	$alt = trim($_POST['alt_text'] ?? '');
	$caption = trim($_POST['caption'] ?? '');
	try {
		$up = $db->prepare('UPDATE media SET alt_text = ?, caption = ? WHERE id = ?');
		$up->execute([$alt, $caption, $id]);
		echo json_encode(['success' => true, 'message' => 'Updated']);
	} catch (Exception $e) {
		echo json_encode(['success' => false, 'message' => 'Update failed']);
	}
	exit;
}

// Search & filter
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'all';

$sql = 'SELECT * FROM media WHERE 1=1';
$params = [];
if ($search !== '') {
	$sql .= ' AND (original_name LIKE ? OR alt_text LIKE ? OR caption LIKE ?)';
	$params[] = "%{$search}%";
	$params[] = "%{$search}%";
	$params[] = "%{$search}%";
}
if ($filter === 'images') {
	$sql .= ' AND mime_type LIKE ?';
	$params[] = 'image/%';
} elseif ($filter === 'videos') {
	$sql .= ' AND mime_type LIKE ?';
	$params[] = 'video/%';
} elseif ($filter === 'audio') {
	$sql .= ' AND mime_type LIKE ?';
	$params[] = 'audio/%';
} elseif ($filter === 'documents') {
	$sql .= ' AND (mime_type LIKE ? OR mime_type LIKE ? OR mime_type LIKE ? OR mime_type = ? OR file_path LIKE ?)';
	$params[] = 'application/%';
	$params[] = 'text/%';
	$params[] = 'message/%';
	$params[] = 'multipart/%';
	$params[] = 'documents/%';
} elseif ($filter === 'pdf') {
	$sql .= ' AND mime_type = ?';
	$params[] = 'application/pdf';
}
$sql .= ' ORDER BY created_at DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$mediaFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$totalFiles = count($mediaFiles);
$totalSize = 0;
foreach ($mediaFiles as $m) $totalSize += intval($m['file_size']);
$totalSizeMB = round($totalSize / 1024 / 1024, 2);

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Media Library - Admin</title>
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

		/* Toolbar + upload */
		.toolbar{display:flex;gap:12px;align-items:center}
		.upload-card{background:#fff;border-radius:10px;padding:18px;border:1px solid #eef2f6;box-shadow:0 6px 18px rgba(16,24,40,0.04)}
		.upload-zone{border:2px dashed #dbeafe;border-radius:8px;padding:28px;text-align:center;cursor:pointer;transition:all .18s;position:relative}
		.upload-zone:hover{background:#f8fbff}
		.upload-zone.selected{border-color:#10b981;background:#f0fdf4}
		.upload-zone.dragover{border-color:#3b82f6;background:#eff6ff}
		.selected-files{display:none;margin-top:16px;}
		.selected-file{margin-bottom:8px;padding:12px;background:#f8fafc;border-radius:6px;border:1px solid #e2e8f0}
		.file-info{display:flex;align-items:center;gap:12px}
		.file-icon{font-size:24px;color:#6b7280}
		.file-details{flex:1;text-align:left}
		.file-name{font-weight:600;color:#1f2937;margin-bottom:2px}
		.file-size{color:#6b7280;font-size:14px}
		.remove-file{background:#ef4444;color:#fff;border:none;border-radius:4px;padding:4px 8px;cursor:pointer;font-size:12px}
		.remove-file:hover{background:#dc2626}
		.upload-meta{display:grid;grid-template-columns:1fr auto;gap:12px;align-items:center;margin-top:14px}
		.meta-input{padding:10px;border:1px solid #e6eef5;border-radius:8px}
		.upload-btn{background:linear-gradient(90deg,var(--accent),#553c9a);color:#fff;padding:10px 14px;border-radius:8px;border:none;cursor:pointer}
		.upload-btn:disabled{background:#9ca3af;cursor:not-allowed}

		/* Filters */
		.filters{display:flex;gap:12px;align-items:center;margin:16px 0}
		.filter-input{padding:8px;border:1px solid #e6eef5;border-radius:8px}

		/* Grid */
		.media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px}
		.media-item{background:#fff;border:1px solid #eef2f6;border-radius:10px;overflow:hidden;display:flex;flex-direction:column}
		.thumb{height:120px;background:#f3f6f9;display:flex;align-items:center;justify-content:center}
		.thumb img{width:100%;height:100%;object-fit:cover}
		.m-info{padding:10px;display:flex;flex-direction:column;flex:1}
		.m-name{font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
		.m-actions{margin-top:auto;display:flex;gap:6px;flex-wrap:wrap}
		.action-btn{padding:8px 10px;border-radius:6px;border:none;cursor:pointer;font-size:12px;font-weight:500;transition:all 0.2s ease;display:flex;align-items:center;gap:4px;text-decoration:none;white-space:nowrap;flex:1;justify-content:center}
		.action-btn i{font-size:12px}
		.action-view{background:#06b6d4;color:#fff}.action-view:hover{background:#0891b2;transform:translateY(-1px);box-shadow:0 2px 6px rgba(6,182,212,0.3)}
		.action-edit{background:#3b82f6;color:#fff}.action-edit:hover{background:#2563eb;transform:translateY(-1px);box-shadow:0 2px 6px rgba(59,130,246,0.3)}
		.action-download{background:#10b981;color:#fff}.action-download:hover{background:#059669;transform:translateY(-1px);box-shadow:0 2px 6px rgba(16,185,129,0.3)}
		.action-delete{background:#ef4444;color:#fff}.action-delete:hover{background:#dc2626;transform:translateY(-1px);box-shadow:0 2px 6px rgba(239,68,68,0.3)}
		.action-insert{background:#8b5cf6;color:#fff}.action-insert:hover{background:#7c3aed;transform:translateY(-1px);box-shadow:0 2px 6px rgba(139,92,246,0.3)}

		/* View Modal */
		.view-modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:1000;align-items:center;justify-content:center}
		.view-modal.active{display:flex}
		.view-modal-content{position:relative;max-width:90%;max-height:90%;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.3)}
		.view-modal-image{max-width:100%;max-height:80vh;object-fit:contain;display:block}
		.view-modal-video{max-width:100%;max-height:80vh;display:block}
		.view-modal-header{padding:16px;background:#f3f4f6;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center}
		.view-modal-header h3{margin:0;font-size:16px}
		.view-modal-close{background:#ef4444;color:#fff;border:none;cursor:pointer;padding:8px 12px;border-radius:6px;font-size:14px;font-weight:500;transition:background 0.2s}.view-modal-close:hover{background:#dc2626}

		.stats{display:flex;gap:12px;align-items:center}
		.stat{background:#fff;padding:10px;border-radius:8px;border:1px solid #eef2f6}

		@media(max-width:800px){.filters{flex-direction:column;align-items:stretch}.upload-meta{grid-template-columns:1fr}}
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
						<div class="media-title"><h1><i class="fas fa-photo-video"></i> Media Library</h1></div>
						<div class="page-actions">
							<a href="media.php" class="btn btn-primary"><i class="fas fa-sync-alt"></i> Refresh</a>
						</div>
					</div>

					<?php if ($message): ?>
						<div class="alert <?php echo $messageType === 'success' ? 'success' : 'error'; ?>"><?php echo htmlspecialchars($message); ?></div>
					<?php endif; ?>

					<div class="upload-card">
						<form id="uploadForm" method="post" enctype="multipart/form-data">
							<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
							<div class="upload-zone" id="uploadZone">
								<div style="font-size:28px">📁</div>
								<div style="margin-top:8px;font-weight:600">Drag & drop files here, or click to browse</div>
								<div style="margin-top:6px;color:var(--muted);font-size:13px">Select multiple files — All types supported — up to 15MB each</div>
								<input type="file" id="media_file" name="media_file[]" multiple style="display:none">
							</div>
							<div class="selected-files" id="selectedFiles" style="display:none;margin-top:16px;">
								<div style="font-weight:600;margin-bottom:8px;">Selected files:</div>
								<div id="fileList"></div>
							</div>
							<div class="upload-meta">
								<input class="meta-input" type="text" name="alt_text" placeholder="Alt text (applied to all files)">
								<input class="meta-input" type="text" name="caption" placeholder="Caption (applied to all files)">
								<button class="upload-btn" type="submit" id="uploadBtn">Upload Files</button>
							</div>
						</form>
					</div>

					<div class="filters">
						<form method="get" style="display:flex;flex:1;gap:8px;align-items:center">
							<input class="filter-input" type="text" name="search" placeholder="Search by name, alt or caption" value="<?php echo htmlspecialchars($search); ?>">
			<select class="filter-input" name="filter">
				<option value="all" <?php echo $filter==='all'?'selected':''; ?>>All</option>
				<option value="images" <?php echo $filter==='images'?'selected':''; ?>>Images</option>
				<option value="videos" <?php echo $filter==='videos'?'selected':''; ?>>Videos</option>
				<option value="documents" <?php echo $filter==='documents'?'selected':''; ?>>Documents</option>
				<option value="pdf" <?php echo $filter==='pdf'?'selected':''; ?>>PDFs</option>
			</select>
							<button class="btn-primary" type="submit">Search</button>
						</form>
						<div class="stats">
							<div class="stat"><strong><?php echo $totalFiles; ?></strong> files</div>
							<div class="stat"><strong><?php echo $totalSizeMB; ?></strong> MB used</div>
						</div>
					</div>

					<?php if (empty($mediaFiles)): ?>
						<div style="margin-top:30px;padding:30px;text-align:center;background:#fff;border-radius:8px;border:1px solid #eef2f6">No media yet — upload to get started.</div>
					<?php else: ?>
						<div class="media-grid" style="margin-top:18px">
							<?php foreach ($mediaFiles as $m):
								$isImage = strpos($m['mime_type'],'image/')===0;
								$url = '../uploads/' . $m['file_path'];
								$directory = dirname($m['file_path']);
							?>
							<div class="media-item">
								<div class="thumb">
									<?php if ($isImage && file_exists(__DIR__ . '/../uploads/' . $m['file_path'])): ?>
										<img src="<?php echo htmlspecialchars($url); ?>" alt="<?php echo htmlspecialchars($m['alt_text']); ?>" style="width:100%;height:100%;object-fit:cover;">
									<?php elseif (strpos($m['mime_type'], 'video/') === 0 && file_exists(__DIR__ . '/../uploads/' . $m['file_path'])): ?>
										<video style="width:100%;height:100%;object-fit:cover;" muted>
											<source src="<?php echo htmlspecialchars($url); ?>" type="<?php echo htmlspecialchars($m['mime_type']); ?>">
											<div style="font-size:36px">🎥</div>
										</video>
									<?php elseif (strpos($m['mime_type'], 'audio/') === 0 && file_exists(__DIR__ . '/../uploads/' . $m['file_path'])): ?>
										<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;width:100%;height:100%;background:linear-gradient(135deg,#667eea,#764ba2);color:white;">
											<div style="font-size:24px;margin-bottom:4px;">🎵</div>
											<div style="font-size:10px;text-align:center;">AUDIO</div>
										</div>
									<?php else: ?>
										<div style="font-size:36px">
											<?php
											if (strpos($m['mime_type'], 'video/') === 0) echo '🎥';
											elseif (strpos($m['mime_type'], 'audio/') === 0) echo '🎵';
											elseif (strpos($m['mime_type'], 'pdf') !== false) echo '📄';
											elseif (strpos($m['mime_type'], 'application/') === 0) echo '📋';
											else echo '📄';
											?>
										</div>
									<?php endif; ?>
									<div style="position:absolute;top:5px;right:5px;background:rgba(0,0,0,0.7);color:#fff;padding:2px 6px;border-radius:4px;font-size:10px;text-transform:capitalize;">
										<?php echo htmlspecialchars($directory); ?>
									</div>
								</div>
								<div class="m-info">
									<div class="m-name" title="<?php echo htmlspecialchars($m['original_name']); ?>"><?php echo htmlspecialchars($m['original_name']); ?></div>
									<div style="font-size:12px;color:var(--muted);margin-top:6px"><?php echo round($m['file_size']/1024,1); ?> KB • <?php echo htmlspecialchars($m['mime_type']); ?></div>
									<div class="m-actions">
										<?php if ($isImage): ?>
										<button class="action-btn action-view" onclick="viewImage('<?php echo htmlspecialchars($url); ?>', '<?php echo htmlspecialchars($m['original_name']); ?>')" title="View image">
											<i class="fas fa-eye"></i> View
										</button>
										<?php endif; ?>
										<button class="action-btn action-edit" data-id="<?php echo $m['id']; ?>" data-alt="<?php echo htmlspecialchars($m['alt_text']); ?>" data-caption="<?php echo htmlspecialchars($m['caption']); ?>" onclick="openEditModal(this)" title="Edit metadata">
											<i class="fas fa-edit"></i> Edit
										</button>
										<a class="action-btn action-download" href="<?php echo htmlspecialchars($url); ?>" download title="Download file">
											<i class="fas fa-download"></i> Download
										</a>
										<a class="action-btn action-delete" href="?action=delete&id=<?php echo $m['id']; ?>" onclick="return confirm('Delete this file?');" title="Delete file">
											<i class="fas fa-trash"></i> Delete
										</a>
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

	<script>
		(function(){
			const uploadZone = document.getElementById('uploadZone');
			const fileInput = document.getElementById('media_file');
			const selectedFilesDiv = document.getElementById('selectedFiles');
			const fileList = document.getElementById('fileList');
			const uploadText = uploadZone.querySelector('div:nth-child(2)');
			const uploadBtn = document.getElementById('uploadBtn');

			if (!uploadZone || !fileInput) return;

			function formatFileSize(bytes) {
				if (bytes === 0) return '0 Bytes';
				const k = 1024;
				const sizes = ['Bytes', 'KB', 'MB', 'GB'];
				const i = Math.floor(Math.log(bytes) / Math.log(k));
				return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
			}

			function getFileIcon(mimeType) {
				if (mimeType.startsWith('image/')) return '🖼️';
				if (mimeType === 'application/pdf') return '📄';
				return '📄';
			}

			function updateFileDisplay(files) {
				fileList.innerHTML = '';

				if (files && files.length > 0) {
					selectedFilesDiv.style.display = 'block';
					uploadZone.classList.add('selected');
					uploadText.textContent = `${files.length} file${files.length > 1 ? 's' : ''} selected - ready to upload!`;

					Array.from(files).forEach((file, index) => {
						const fileDiv = document.createElement('div');
						fileDiv.className = 'selected-file';

						// Check if it's an image
						if (file.type.startsWith('image/')) {
							const reader = new FileReader();
							reader.onload = function(e) {
								const previewDiv = fileDiv.querySelector('.file-preview');
								if (previewDiv) {
									previewDiv.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width:60px;max-height:60px;object-fit:cover;border-radius:4px;">`;
								}
							};
							reader.readAsDataURL(file);
						}

						fileDiv.innerHTML = `
							<div class="file-info">
								<div class="file-preview" style="width:60px;height:60px;display:flex;align-items:center;justify-content:center;background:#f8fafc;border-radius:4px;margin-right:12px;">
									${file.type.startsWith('image/') ? '' : `<span style="font-size:24px;">${getFileIcon(file.type)}</span>`}
								</div>
								<div class="file-details" style="flex:1;">
									<div class="file-name">${file.name}</div>
									<div class="file-size">${formatFileSize(file.size)}</div>
								</div>
								<button class="remove-file" onclick="removeFile(${index})">×</button>
							</div>
						`;
						fileList.appendChild(fileDiv);
					});

					if (uploadBtn) uploadBtn.disabled = false;
				} else {
					selectedFilesDiv.style.display = 'none';
					uploadZone.classList.remove('selected');
					uploadText.textContent = 'Drag & drop files here, or click to browse';
					if (uploadBtn) uploadBtn.disabled = true;
				}
			}

			function clearFileSelection() {
				fileInput.value = '';
				updateFileDisplay(null);
			}

			window.removeFile = function(index) {
				const dt = new DataTransfer();
				const files = Array.from(fileInput.files);

				files.forEach((file, i) => {
					if (i !== index) dt.items.add(file);
				});

				fileInput.files = dt.files;
				updateFileDisplay(fileInput.files);
			};

			uploadZone.addEventListener('click', ()=> fileInput.click());
			uploadZone.addEventListener('dragover', e=>{ e.preventDefault(); uploadZone.classList.add('dragover'); });
			uploadZone.addEventListener('dragleave', e=>{ e.preventDefault(); uploadZone.classList.remove('dragover'); });
			uploadZone.addEventListener('drop', e=>{ e.preventDefault(); uploadZone.classList.remove('dragover'); if(e.dataTransfer.files.length) { fileInput.files = e.dataTransfer.files; updateFileDisplay(e.dataTransfer.files); } });

			fileInput.addEventListener('change', function(e) {
				updateFileDisplay(e.target.files);
			});

			// Initialize on page load
			updateFileDisplay(null);

			window.insertImage = function(path){
				// If host page defines a handler, call it (for editors)
				try{
					if (window.parent && typeof window.parent.selectFeaturedImage === 'function'){
						window.parent.selectFeaturedImage(path);
						showToast('Inserted');
						return;
					}
					if (typeof selectFeaturedImage === 'function'){
						selectFeaturedImage(path);
						showToast('Inserted');
						return;
					}
					navigator.clipboard && navigator.clipboard.writeText(path).then(()=> showToast('Path copied'));
				}catch(e){
					console.log(e);
				}
			}

			window.openEditModal = function(btn){
				const id = btn.getAttribute('data-id');
				const alt = btn.getAttribute('data-alt') || '';
				const caption = btn.getAttribute('data-caption') || '';
				const modal = document.createElement('div');
				modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;z-index:9999;';
				modal.innerHTML = `
					<div style="background:#fff;border-radius:8px;padding:18px;max-width:520px;width:100%;">
						<h3 style="margin-top:0">Edit metadata</h3>
						<div style="margin-bottom:8px;"><label style="display:block;margin-bottom:6px">Alt text</label><input id="editAlt" value="${alt}" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px"></div>
						<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:6px">Caption</label><textarea id="editCaption" style="width:100%;height:80px;padding:8px;border:1px solid #ddd;border-radius:6px">${caption}</textarea></div>
						<div style="display:flex;gap:8px;justify-content:flex-end;"><button id="cancelEdit" style="padding:8px 12px;border-radius:6px;border:1px solid #ddd;background:#f3f4f6;">Cancel</button><button id="saveEdit" style="padding:8px 12px;border-radius:6px;background:var(--accent);color:#fff;border:none;">Save</button></div>
					</div>`;
				document.body.appendChild(modal);
				document.getElementById('cancelEdit').addEventListener('click', ()=> modal.remove());
				document.getElementById('saveEdit').addEventListener('click', ()=>{
					const altVal = document.getElementById('editAlt').value;
					const capVal = document.getElementById('editCaption').value;
					fetch('media.php', {
						method: 'POST',
						headers: {'Content-Type':'application/x-www-form-urlencoded'},
						body: 'action=edit_meta&id='+encodeURIComponent(id)+'&alt_text='+encodeURIComponent(altVal)+'&caption='+encodeURIComponent(capVal)+'&csrf_token='+encodeURIComponent('<?php echo $_SESSION['csrf_token']; ?>')
					}).then(r=>r.json()).then(j=>{
						if (j.success){
							showToast('Saved');
							modal.remove();
							// update UI text if present
							const b = document.querySelector('[data-id="'+id+'"]');
							if (b) b.setAttribute('data-alt', altVal);
						} else {
							alert('Failed to save');
						}
					}).catch(()=> alert('Network error'));
				});
			}

			function showToast(msg){
				const d = document.createElement('div'); d.textContent = msg; d.style.cssText = 'position:fixed;top:20px;right:20px;background:var(--accent);color:white;padding:10px 14px;border-radius:8px;z-index:99999;'; document.body.appendChild(d); setTimeout(()=>d.remove(),1400);
			}

			window.viewImage = function(url, name){
				const modal = document.getElementById('viewImageModal');
				const img = document.getElementById('viewImageContent');
				const title = document.getElementById('viewImageTitle');
				if(modal && img){
					img.src = url;
					if(title) title.textContent = name;
					modal.classList.add('active');
				}
			}

			window.closeViewModal = function(){
				const modal = document.getElementById('viewImageModal');
				if(modal) modal.classList.remove('active');
			}

			document.addEventListener('DOMContentLoaded', function(){
				const modal = document.getElementById('viewImageModal');
				if(modal){
					modal.addEventListener('click', function(e){
						if(e.target === modal) closeViewModal();
					});
					document.addEventListener('keydown', function(e){
						if(e.key === 'Escape') closeViewModal();
					});
				}
			});

		})();
	</script>

	<!-- View Image Modal -->
	<div id="viewImageModal" class="view-modal">
		<div class="view-modal-content">
			<div class="view-modal-header">
				<h3 id="viewImageTitle">View Image</h3>
				<button class="view-modal-close" onclick="closeViewModal()"><i class="fas fa-times"></i> Close</button>
			</div>
			<img id="viewImageContent" class="view-modal-image" alt="Preview">
		</div>
	</div>
</body>
</html>
