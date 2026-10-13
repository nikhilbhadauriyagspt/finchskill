<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    if (ini_get('session.cookie_path') !== '/') {
        @ini_set('session.cookie_path', '/');
    }
    @session_set_cookie_params([
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['photo'])) {
    echo json_encode(['success' => false, 'message' => 'No photo uploaded.']);
    exit;
}

$file = $_FILES['photo'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Upload error code: ' . $file['error']]);
    exit;
}

// Validate file size (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Photo size must be less than 5MB.']);
    exit;
}

$allowed = ['jpg', 'jpeg', 'png', 'webp'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid format. Allowed: JPG, JPEG, PNG, WEBP.']);
    exit;
}

$upload_dir = __DIR__ . '/uploads/students/';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

$filename = 'std_' . time() . '_' . mt_rand(100, 999) . '.' . $ext;
$target = $upload_dir . $filename;

if (move_uploaded_file($file['tmp_name'], $target)) {
    $relative_path = 'uploads/students/' . $filename;
    $_SESSION['reg_uploaded_photo'] = $relative_path;
    echo json_encode([
        'success' => true,
        'file_path' => $relative_path,
        'message' => 'Photo uploaded successfully.'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save uploaded photo on server.']);
}
