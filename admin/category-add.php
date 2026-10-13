<?php
require_once __DIR__ . '/includes/auth.php';
checkAdminAuth();
header("Location: categories.php#categoryFormCard");
exit;
