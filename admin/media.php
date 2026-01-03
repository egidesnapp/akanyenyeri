<?php
session_start();
require_once __DIR__ . '/php/auth_check.php';
requireAuth();

$pdo = getDB();
$db = $pdo;

$message = '';
$messageType = '';

// Config
$MAX_FILE_SIZE = 12 * 1024 * 1024; // 12MB
$ALLOWED_MIME = [
	'image/jpeg', 'image/png', 'image/gif', 'image/webp',
	'application/pdf'
];

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
		$f = $_FILES['media_file'];
		if ($f['error'] !== UPLOAD_ERR_OK) {
			$message = 'Upload error. Please try again.';
			$messageType = 'error';
		} elseif ($f['size'] > $MAX_FILE_SIZE) {
			$message = 'File too large (max 12MB).';
			$messageType = 'error';
		} elseif (!in_array($f['type'], $ALLOWED_MIME)) {
			$message = 'Invalid file type.';
			$messageType = 'error';
		} else {
			$ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
			$safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($f['name']));
			$filename = time() . '_' . bin2hex(random_bytes(6)) . '_' . $safe;
			$uploadDir = __DIR__ . '/../uploads/images/';
			if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
			$target = $uploadDir . $filename;
			if (move_uploaded_file($f['tmp_name'], $target)) {
				try {
					$stmt = $db->prepare('INSERT INTO media (filename, original_name, mime_type, file_size, alt_text, caption, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
					$stmt->execute([
						$filename,
						$f['name'],
						$f['type'],
						$f['size'],
						trim($_POST['alt_text'] ?? ''),
						trim($_POST['caption'] ?? ''),
						$_SESSION['admin_user_id'] ?? null,
					]);
					$message = 'File uploaded.';
					$messageType = 'success';
				} catch (Exception $e) {
					@unlink($target);
					$message = 'Database error.';
					$messageType = 'error';
				}
			} else {
				$message = 'Could not move uploaded file.';
				$messageType = 'error';
			}
		}
	}
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
		.upload-zone{border:2px dashed #dbeafe;border-radius:8px;padding:28px;text-align:center;cursor:pointer;transition:all .18s}
		.upload-zone:hover{background:#f8fbff}
		.upload-meta{display:grid;grid-template-columns:1fr auto;gap:12px;align-items:center;margin-top:14px}
		.meta-input{padding:10px;border:1px solid #e6eef5;border-radius:8px}
		.upload-btn{background:linear-gradient(90deg,var(--accent),#553c9a);color:#fff;padding:10px 14px;border-radius:8px;border:none;cursor:pointer}

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
		.view-modal-image{max-width:100%;max-height:80vh;object-fit:contain}
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
								<div style="margin-top:8px;font-weight:600">Drag & drop a file here, or click to browse</div>
								<div style="margin-top:6px;color:var(--muted);font-size:13px">JPG, PNG, GIF, WebP, PDF — up to 12MB</div>
								<input type="file" id="media_file" name="media_file" accept="image/*,.pdf" style="display:none">
							</div>
							<div class="upload-meta">
								<input class="meta-input" type="text" name="alt_text" placeholder="Alt text (optional)">
								<input class="meta-input" type="text" name="caption" placeholder="Caption (optional)">
								<button class="upload-btn" type="submit">Upload</button>
							</div>
						</form>
					</div>

					<div class="filters">
						<form method="get" style="display:flex;flex:1;gap:8px;align-items:center">
							<input class="filter-input" type="text" name="search" placeholder="Search by name, alt or caption" value="<?php echo htmlspecialchars($search); ?>">
							<select class="filter-input" name="filter">
								<option value="all" <?php echo $filter==='all'?'selected':''; ?>>All</option>
								<option value="images" <?php echo $filter==='images'?'selected':''; ?>>Images</option>
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
								$url = '../uploads/images/' . $m['filename'];
							?>
							<div class="media-item">
								<div class="thumb">
									<?php if ($isImage && file_exists(__DIR__ . '/../uploads/images/' . $m['filename'])): ?>
										<img src="<?php echo htmlspecialchars($url); ?>" alt="<?php echo htmlspecialchars($m['alt_text']); ?>">
									<?php else: ?>
										<div style="font-size:36px">📄</div>
									<?php endif; ?>
								</div>
								<div class="m-info">
									<div class="m-name" title="<?php echo htmlspecialchars($m['original_name']); ?>"><?php echo htmlspecialchars($m['original_name']); ?></div>
									<div style="font-size:12px;color:var(--muted);margin-top:6px"><?php echo round($m['file_size']/1024,1); ?> KB • <?php echo htmlspecialchars($m['mime_type']); ?></div>
									<div class="m-actions">
										<button class="action-btn action-view" onclick="viewImage('<?php echo htmlspecialchars($url); ?>', '<?php echo htmlspecialchars($m['original_name']); ?>')" title="View image">
											<i class="fas fa-eye"></i> View
										</button>
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
			if (!uploadZone || !fileInput) return;

			uploadZone.addEventListener('click', ()=> fileInput.click());
			uploadZone.addEventListener('dragover', e=>{ e.preventDefault(); uploadZone.classList.add('dragover'); });
			uploadZone.addEventListener('dragleave', e=>{ e.preventDefault(); uploadZone.classList.remove('dragover'); });
			uploadZone.addEventListener('drop', e=>{ e.preventDefault(); uploadZone.classList.remove('dragover'); if(e.dataTransfer.files.length) fileInput.files = e.dataTransfer.files; });

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
