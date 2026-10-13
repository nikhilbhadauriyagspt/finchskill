<?php
// Admin Context Receipt Viewer
// Ensures Admin always remains within Admin panel navigation and session
require_once __DIR__ . '/includes/auth.php';
checkAdminAuth();

$_GET['from'] = 'admin';
require_once __DIR__ . '/../receipt.php';
