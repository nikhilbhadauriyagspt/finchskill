<?php
require_once __DIR__ . '/includes/auth.php';
checkAdminAuth();

$page_title = "Course Categories";
$success_msg = '';
$error_msg = '';

// Handle Messages from GET
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') $success_msg = 'Category created successfully!';
    if ($_GET['msg'] === 'updated') $success_msg = 'Category updated successfully!';
    if ($_GET['msg'] === 'deleted') $success_msg = 'Category deleted successfully!';
    if ($_GET['msg'] === 'status_updated') $success_msg = 'Category status updated!';
    if ($_GET['msg'] === 'err_has_courses') $error_msg = 'Cannot delete category: Active or existing courses are assigned to this category. Please reassign or delete them first.';
    if ($_GET['msg'] === 'err_slug') $error_msg = 'A category with this URL slug already exists. Please choose a different slug.';
}

// -------------------------------------------------------------
// POST ACTIONS
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // SAVE CATEGORY (ADD OR EDIT)
    if ($action === 'save_category') {
        $cat_id = (int)($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $icon = trim($_POST['icon'] ?? 'fa-graduation-cap');
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        if (empty($name)) {
            $error_msg = 'Please enter a category name.';
        } else {
            // Auto generate slug if empty
            if (empty($slug)) {
                $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($name));
                $slug = trim($slug, '-');
            } else {
                $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($slug));
                $slug = trim($slug, '-');
            }

            // Check slug uniqueness
            if ($cat_id > 0) {
                $chk = $pdo->prepare("SELECT id FROM course_categories WHERE slug = ? AND id != ?");
                $chk->execute([$slug, $cat_id]);
                if ($chk->fetch()) {
                    $error_msg = "The slug '{$slug}' is already in use by another category.";
                }
            } else {
                $chk = $pdo->prepare("SELECT id FROM course_categories WHERE slug = ?");
                $chk->execute([$slug]);
                if ($chk->fetch()) {
                    $slug .= '-' . rand(10, 99);
                }
            }

            if (empty($error_msg)) {
                if ($cat_id > 0) {
                    $stmt = $pdo->prepare("UPDATE course_categories SET name = ?, slug = ?, icon = ?, status = ? WHERE id = ?");
                    $stmt->execute([$name, $slug, $icon, $status, $cat_id]);
                    header("Location: categories.php?msg=updated");
                    exit;
                } else {
                    $stmt = $pdo->prepare("INSERT INTO course_categories (name, slug, icon, status) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $slug, $icon, $status]);
                    header("Location: categories.php?msg=added");
                    exit;
                }
            }
        }
    }

    // TOGGLE STATUS
    if ($action === 'toggle_status') {
        $cat_id = (int)($_POST['category_id'] ?? 0);
        if ($cat_id > 0) {
            $stmt = $pdo->prepare("UPDATE course_categories SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
            $stmt->execute([$cat_id]);
            header("Location: categories.php?msg=status_updated");
            exit;
        }
    }

    // DELETE CATEGORY
    if ($action === 'delete_category') {
        $cat_id = (int)($_POST['category_id'] ?? 0);
        if ($cat_id > 0) {
            $chk = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE category_id = ?");
            $chk->execute([$cat_id]);
            $count = (int)$chk->fetchColumn();

            if ($count > 0) {
                header("Location: categories.php?msg=err_has_courses");
                exit;
            } else {
                $stmt = $pdo->prepare("DELETE FROM course_categories WHERE id = ?");
                $stmt->execute([$cat_id]);
                header("Location: categories.php?msg=deleted");
                exit;
            }
        }
    }
}

// -------------------------------------------------------------
// EDIT MODE DATA
// -------------------------------------------------------------
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    if ($edit_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM course_categories WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_category = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// -------------------------------------------------------------
// STATS & DATA FETCHING
// -------------------------------------------------------------
$total_cats = (int)$pdo->query("SELECT COUNT(*) FROM course_categories")->fetchColumn();
$active_cats = (int)$pdo->query("SELECT COUNT(*) FROM course_categories WHERE status = 'active'")->fetchColumn();
$inactive_cats = $total_cats - $active_cats;
$total_courses = (int)$pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();

// Search & Filter
$search = trim($_GET['search'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

$query = "SELECT c.*, (SELECT COUNT(*) FROM courses WHERE category_id = c.id) AS course_count 
          FROM course_categories c 
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (c.name LIKE ? OR c.slug LIKE ?)";
    $term = "%{$search}%";
    $params = array_merge($params, [$term, $term]);
}

if (!empty($filter_status)) {
    $query .= " AND c.status = ?";
    $params[] = $filter_status;
}

$query .= " ORDER BY c.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-5">

    <!-- PAGE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-md border border-slate-200">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-base font-bold text-[#0e1e2e]">Course Categories</h2>
                <span class="px-2 py-0.5 text-[11px] font-semibold bg-orange-50 text-[#fe7c03] rounded border border-orange-200">
                    <?= $total_cats ?> Categories
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">Manage training classifications, FontAwesome icons, and course relationships</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="courses.php" class="px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:text-[#fe7c03] rounded-md transition-colors flex items-center gap-1 no-underline">
                <i class="fa-solid fa-book-open-reader text-slate-400"></i>
                <span>View Courses</span>
            </a>
            <a href="course-add.php" class="px-3.5 py-1.5 text-xs font-semibold text-white bg-[#fe7c03] hover:bg-[#ea6c00] rounded-md transition-colors flex items-center gap-1 no-underline">
                <i class="fa-solid fa-plus text-[11px]"></i>
                <span>Add Course</span>
            </a>
        </div>
    </div>

    <!-- STATS STRIP -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="stat-card flex items-center gap-3">
            <div class="stat-icon bg-orange-50 text-[#fe7c03]">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
                <p class="text-[11px] font-medium text-slate-400 uppercase tracking-wider">Total Categories</p>
                <h3 class="text-lg font-bold text-[#0e1e2e] leading-tight"><?= $total_cats ?></h3>
            </div>
        </div>

        <div class="stat-card flex items-center gap-3">
            <div class="stat-icon bg-emerald-50 text-emerald-600">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <p class="text-[11px] font-medium text-slate-400 uppercase tracking-wider">Active</p>
                <h3 class="text-lg font-bold text-emerald-700 leading-tight"><?= $active_cats ?></h3>
            </div>
        </div>

        <div class="stat-card flex items-center gap-3">
            <div class="stat-icon bg-slate-100 text-slate-500">
                <i class="fa-solid fa-eye-slash"></i>
            </div>
            <div>
                <p class="text-[11px] font-medium text-slate-400 uppercase tracking-wider">Inactive</p>
                <h3 class="text-lg font-bold text-slate-600 leading-tight"><?= $inactive_cats ?></h3>
            </div>
        </div>

        <div class="stat-card flex items-center gap-3">
            <div class="stat-icon bg-sky-50 text-sky-600">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div>
                <p class="text-[11px] font-medium text-slate-400 uppercase tracking-wider">Mapped Courses</p>
                <h3 class="text-lg font-bold text-sky-700 leading-tight"><?= $total_courses ?></h3>
            </div>
        </div>
    </div>

    <!-- ALERTS -->
    <?php if (!empty($success_msg)): ?>
        <div class="p-3 text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-md flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-500 text-sm"></i>
                <span><?= htmlspecialchars($success_msg) ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="p-3 text-xs bg-rose-50 text-rose-700 border border-rose-200 rounded-md flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-rose-500 text-sm"></i>
                <span><?= htmlspecialchars($error_msg) ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- MAIN TWO-COLUMN CONTENT GRID -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

        <!-- LEFT COLUMN: ADD / EDIT CATEGORY FORM (5 cols) -->
        <div class="lg:col-span-5 bg-white border border-slate-200 rounded-md p-4 space-y-4 shadow-sm" id="categoryFormCard">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded bg-orange-50 text-[#fe7c03] flex items-center justify-center text-xs">
                        <i class="fa-solid <?= $edit_category ? 'fa-pen-to-square' : 'fa-plus' ?>"></i>
                    </span>
                    <div>
                        <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider">
                            <?= $edit_category ? 'Edit Category' : 'Add New Category' ?>
                        </h3>
                        <p class="text-[11px] text-slate-400">
                            <?= $edit_category ? 'Modify category title, icon and status' : 'Create a new course category' ?>
                        </p>
                    </div>
                </div>

                <?php if ($edit_category): ?>
                    <a href="categories.php" class="px-2 py-1 text-[11px] font-semibold text-slate-500 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 rounded transition-colors no-underline">
                        <i class="fa-solid fa-xmark mr-1"></i>Cancel Edit
                    </a>
                <?php endif; ?>
            </div>

            <form method="POST" action="categories.php" class="space-y-3.5">
                <input type="hidden" name="action" value="save_category">
                <input type="hidden" name="category_id" value="<?= $edit_category['id'] ?? 0 ?>">

                <!-- Category Name -->
                <div>
                    <label class="font-bold text-xs text-slate-700 block mb-1">
                        Category Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" 
                           id="cat_name_input"
                           name="name" 
                           required 
                           value="<?= htmlspecialchars($edit_category['name'] ?? ($_POST['name'] ?? '')) ?>" 
                           placeholder="e.g. Aviation Management, Cabin Crew, Hotel Operations..." 
                           class="w-full text-xs px-3 py-2 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03] focus:ring-1 focus:ring-[#fe7c03] transition-all">
                </div>

                <!-- URL Slug -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="font-bold text-xs text-slate-700">URL Slug</label>
                        <span class="text-[10px] text-slate-400">Auto-generated or custom</span>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-2 py-2 text-[11px] text-slate-400 bg-slate-50 border border-r-0 border-slate-200 rounded-l-md select-none">
                            courses.php?category=
                        </span>
                        <input type="text" 
                               id="cat_slug_input"
                               name="slug" 
                               value="<?= htmlspecialchars($edit_category['slug'] ?? ($_POST['slug'] ?? '')) ?>" 
                               placeholder="aviation-management" 
                               class="flex-1 text-xs px-3 py-2 border border-slate-200 rounded-r-md focus:outline-none focus:border-[#fe7c03] focus:ring-1 focus:ring-[#fe7c03] font-mono transition-all">
                    </div>
                </div>

                <!-- FontAwesome Icon Selector with Live Preview -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="font-bold text-xs text-slate-700">Icon Class</label>
                        <span class="text-[10px] text-slate-400">FontAwesome 6</span>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <div id="iconPreviewBadge" class="w-9 h-9 rounded-md bg-orange-50 border border-orange-200 text-[#fe7c03] flex items-center justify-center shrink-0 text-sm shadow-sm">
                            <i id="iconPreviewIcon" class="fa-solid <?= htmlspecialchars($edit_category['icon'] ?? ($_POST['icon'] ?? 'fa-graduation-cap')) ?>"></i>
                        </div>
                        <input type="text" 
                               id="cat_icon_input"
                               name="icon" 
                               value="<?= htmlspecialchars($edit_category['icon'] ?? ($_POST['icon'] ?? 'fa-graduation-cap')) ?>" 
                               placeholder="fa-graduation-cap" 
                               class="flex-1 text-xs px-3 py-2 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03] font-mono transition-all">
                    </div>

                    <!-- Preset Quick Pick Icons -->
                    <div class="mt-2 pt-2 border-t border-slate-100">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Quick Icon Suggestions:</p>
                        <div class="flex flex-wrap gap-1">
                            <?php
                            $quick_icons = [
                                ['icon' => 'fa-graduation-cap', 'label' => 'Degree'],
                                ['icon' => 'fa-plane-departure', 'label' => 'Aviation'],
                                ['icon' => 'fa-hotel', 'label' => 'Hotel'],
                                ['icon' => 'fa-earth-americas', 'label' => 'Travel'],
                                ['icon' => 'fa-ship', 'label' => 'Cruise'],
                                ['icon' => 'fa-id-badge', 'label' => 'Staff'],
                                ['icon' => 'fa-user-tie', 'label' => 'Personality'],
                                ['icon' => 'fa-laptop-code', 'label' => 'Tech'],
                                ['icon' => 'fa-briefcase', 'label' => 'Business'],
                                ['icon' => 'fa-headset', 'label' => 'Support'],
                                ['icon' => 'fa-utensils', 'label' => 'Catering'],
                                ['icon' => 'fa-passport', 'label' => 'Visa']
                            ];
                            foreach ($quick_icons as $qi):
                            ?>
                                <button type="button" 
                                        onclick="selectIcon('<?= $qi['icon'] ?>')" 
                                        title="<?= $qi['label'] ?> (<?= $qi['icon'] ?>)"
                                        class="px-2 py-1 text-[11px] rounded bg-slate-50 border border-slate-200 text-slate-600 hover:bg-[#fff8f3] hover:text-[#fe7c03] hover:border-orange-200 transition-colors flex items-center gap-1">
                                    <i class="fa-solid <?= $qi['icon'] ?> text-[10px]"></i>
                                    <span><?= $qi['label'] ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label class="font-bold text-xs text-slate-700 block mb-1">Status</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-2 p-2 border rounded-md cursor-pointer transition-colors <?= ($edit_category['status'] ?? 'active') === 'active' ? 'border-[#fe7c03] bg-orange-50/40 text-slate-800' : 'border-slate-200 hover:bg-slate-50 text-slate-600' ?>">
                            <input type="radio" name="status" value="active" <?= ($edit_category['status'] ?? 'active') === 'active' ? 'checked' : '' ?> class="text-[#fe7c03] focus:ring-[#fe7c03]">
                            <span class="text-xs font-semibold flex items-center gap-1 text-emerald-600">
                                <i class="fa-solid fa-circle-check text-[11px]"></i> Active
                            </span>
                        </label>
                        <label class="flex items-center gap-2 p-2 border rounded-md cursor-pointer transition-colors <?= ($edit_category['status'] ?? '') === 'inactive' ? 'border-[#fe7c03] bg-orange-50/40 text-slate-800' : 'border-slate-200 hover:bg-slate-50 text-slate-600' ?>">
                            <input type="radio" name="status" value="inactive" <?= ($edit_category['status'] ?? '') === 'inactive' ? 'checked' : '' ?> class="text-[#fe7c03] focus:ring-[#fe7c03]">
                            <span class="text-xs font-semibold flex items-center gap-1 text-slate-500">
                                <i class="fa-solid fa-eye-slash text-[11px]"></i> Inactive
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" class="w-full px-4 py-2 text-xs font-semibold text-white bg-[#fe7c03] hover:bg-[#ea6c00] rounded-md transition-colors flex items-center justify-center gap-2 shadow-sm">
                        <i class="fa-solid <?= $edit_category ? 'fa-check' : 'fa-plus' ?>"></i>
                        <span><?= $edit_category ? 'Update Category' : 'Create Category' ?></span>
                    </button>
                    <?php if ($edit_category): ?>
                        <a href="categories.php" class="w-full mt-2 px-3 py-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-md hover:bg-slate-50 text-center block no-underline transition-colors">
                            Cancel &amp; Add New Category
                        </a>
                    <?php endif; ?>
                </div>

            </form>
        </div>

        <!-- RIGHT COLUMN: CATEGORIES DIRECTORY (7 cols) -->
        <div class="lg:col-span-7 bg-white border border-slate-200 rounded-md shadow-sm overflow-hidden">
            
            <!-- Table Header & Filters -->
            <div class="p-3 border-b border-slate-200 bg-slate-50/60">
                <form method="GET" action="categories.php" class="flex flex-col sm:flex-row items-center justify-between gap-2">
                    
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
                        </span>
                        <input type="text" 
                               name="search" 
                               value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Search category name or slug..." 
                               class="w-full pl-8 pr-3 py-1.5 text-xs bg-white border border-slate-200 rounded-md text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                    </div>

                    <!-- Status Filter & Clear -->
                    <div class="flex items-center gap-1 w-full sm:w-auto">
                        <select name="status" onchange="this.form.submit()" class="text-xs bg-white border border-slate-200 rounded-md px-2 py-1.5 text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                            <option value="">All Statuses (<?= $total_cats ?>)</option>
                            <option value="active" <?= $filter_status === 'active' ? 'selected' : '' ?>>Active Only (<?= $active_cats ?>)</option>
                            <option value="inactive" <?= $filter_status === 'inactive' ? 'selected' : '' ?>>Inactive Only (<?= $inactive_cats ?>)</option>
                        </select>

                        <?php if (!empty($search) || !empty($filter_status)): ?>
                            <a href="categories.php" class="p-1.5 text-xs text-slate-400 hover:text-slate-700 rounded hover:bg-slate-100" title="Reset Filters">
                                <i class="fa-solid fa-rotate-left"></i>
                            </a>
                        <?php endif; ?>
                    </div>

                </form>
            </div>

            <!-- Categories Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold text-[10px] uppercase tracking-wider">
                        <tr>
                            <th class="px-3.5 py-2">Category</th>
                            <th class="px-3 py-2 text-center">Courses</th>
                            <th class="px-3 py-2 text-center">Status</th>
                            <th class="px-3.5 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-slate-400">
                                    <i class="fa-solid fa-layer-group fa-2x mb-2 text-slate-300 block"></i>
                                    <p class="font-medium text-xs text-slate-600">No categories found</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Try refining your search or add a new category on the left.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <?php $is_row_editing = ($edit_category && $edit_category['id'] == $cat['id']); ?>
                                <tr class="hover:bg-slate-50/80 transition-colors <?= $is_row_editing ? 'bg-orange-50/50' : '' ?>">
                                    
                                    <!-- Category Name & Icon -->
                                    <td class="px-3.5 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-md bg-orange-50 border border-orange-200 text-[#fe7c03] flex items-center justify-center shrink-0 text-xs">
                                                <i class="fa-solid <?= htmlspecialchars($cat['icon']) ?>"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-bold text-slate-800 text-xs truncate flex items-center gap-1">
                                                    <span><?= htmlspecialchars($cat['name']) ?></span>
                                                    <?php if ($is_row_editing): ?>
                                                        <span class="px-1.5 py-0.2 text-[9px] font-bold rounded bg-[#fe7c03] text-white uppercase tracking-wider">Editing</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-[10px] text-slate-400 font-mono truncate">
                                                    slug: <?= htmlspecialchars($cat['slug']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Courses Count -->
                                    <td class="px-3 py-3 text-center">
                                        <?php if ($cat['course_count'] > 0): ?>
                                            <a href="courses.php?category=<?= $cat['id'] ?>" class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold bg-sky-50 text-sky-700 hover:bg-sky-100 rounded border border-sky-200 no-underline transition-colors" title="View <?= $cat['course_count'] ?> courses">
                                                <i class="fa-solid fa-book-open-reader text-[10px]"></i>
                                                <span><?= $cat['course_count'] ?></span>
                                            </a>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-medium bg-slate-100 text-slate-400 rounded">
                                                0
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Status with Toggle -->
                                    <td class="px-3 py-3 text-center">
                                        <form method="POST" action="categories.php" class="inline-block">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                            <button type="submit" 
                                                    title="Click to toggle status"
                                                    class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider transition-colors inline-flex items-center gap-1 <?= $cat['status'] === 'active' ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' : 'bg-slate-100 text-slate-500 hover:bg-slate-200 border border-slate-200' ?>">
                                                <span class="w-1.5 h-1.5 rounded-full <?= $cat['status'] === 'active' ? 'bg-emerald-500' : 'bg-slate-400' ?>"></span>
                                                <span><?= $cat['status'] ?></span>
                                            </button>
                                        </form>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-3.5 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <!-- Edit Button -->
                                            <a href="categories.php?edit=<?= $cat['id'] ?>#categoryFormCard" 
                                               class="w-7 h-7 rounded border border-slate-200 bg-white text-slate-600 hover:text-[#fe7c03] hover:border-orange-200 flex items-center justify-center transition-colors no-underline" 
                                               title="Edit Category">
                                                <i class="fa-solid fa-pen-to-square text-[11px]"></i>
                                            </a>

                                            <!-- Delete Button -->
                                            <?php if ($cat['course_count'] == 0): ?>
                                                <form method="POST" action="categories.php" onsubmit="return confirm('Are you sure you want to delete category \'<?= htmlspecialchars(addslashes($cat['name'])) ?>\'?');" class="inline-block">
                                                    <input type="hidden" name="action" value="delete_category">
                                                    <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                                    <button type="submit" 
                                                            class="w-7 h-7 rounded border border-slate-200 bg-white text-slate-400 hover:text-rose-600 hover:border-rose-200 flex items-center justify-center transition-colors" 
                                                            title="Delete Category">
                                                        <i class="fa-regular fa-trash-can text-[11px]"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button type="button" 
                                                        disabled 
                                                        class="w-7 h-7 rounded border border-slate-100 bg-slate-50 text-slate-300 flex items-center justify-center cursor-not-allowed" 
                                                        title="Cannot delete: <?= $cat['course_count'] ?> courses linked">
                                                    <i class="fa-solid fa-lock text-[10px]"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer info -->
            <div class="p-3 border-t border-slate-200 bg-slate-50 flex items-center justify-between text-[11px] text-slate-500">
                <span>Showing <strong><?= count($categories) ?></strong> of <strong><?= $total_cats ?></strong> categories</span>
                <span class="text-slate-400">Locked categories have active courses linked</span>
            </div>

        </div>

    </div>

</div>

<!-- Auto-Slug & Live Icon Preview Scripts -->
<script>
    const nameInput = document.getElementById('cat_name_input');
    const slugInput = document.getElementById('cat_slug_input');
    const iconInput = document.getElementById('cat_icon_input');
    const iconPreview = document.getElementById('iconPreviewIcon');
    let userEditedSlug = <?= $edit_category ? 'true' : 'false' ?>;

    if (slugInput) {
        slugInput.addEventListener('input', () => {
            userEditedSlug = true;
        });
    }

    if (nameInput) {
        nameInput.addEventListener('input', () => {
            if (!userEditedSlug) {
                const slug = nameInput.value
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                slugInput.value = slug;
            }
        });
    }

    function updateIconPreview(cls) {
        if (!iconPreview) return;
        cls = cls.trim();
        if (!cls) cls = 'fa-graduation-cap';
        if (!cls.startsWith('fa-') && !cls.includes('fa-')) {
            cls = 'fa-' + cls;
        }
        iconPreview.className = 'fa-solid ' + cls;
    }

    if (iconInput) {
        iconInput.addEventListener('input', (e) => {
            updateIconPreview(e.target.value);
        });
    }

    function selectIcon(iconClass) {
        if (iconInput) {
            iconInput.value = iconClass;
            updateIconPreview(iconClass);
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
