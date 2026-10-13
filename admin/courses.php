<?php
require_once __DIR__ . '/includes/auth.php';
checkAdminAuth();

$page_title = "Manage Courses";
$success_msg = '';
$error_msg = '';

// Check query param messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') $success_msg = 'Course created successfully!';
    if ($_GET['msg'] === 'updated') $success_msg = 'Course updated successfully!';
    if ($_GET['msg'] === 'deleted') $success_msg = 'Course deleted successfully!';
}

// -------------------------------------------------------------
// POST ACTIONS (QUICK ACTIONS)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // DELETE COURSE
    if ($action === 'delete_course') {
        $delete_id = (int)($_POST['course_id'] ?? 0);
        if ($delete_id > 0) {
            $img_stmt = $pdo->prepare("SELECT image FROM courses WHERE id = ?");
            $img_stmt->execute([$delete_id]);
            $img = $img_stmt->fetchColumn();
            if ($img && strpos($img, 'uploads/courses/') === 0 && file_exists(__DIR__ . '/../' . $img)) {
                @unlink(__DIR__ . '/../' . $img);
            }

            $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->execute([$delete_id]);
            header("Location: courses.php?msg=deleted");
            exit;
        }
    }

    // TOGGLE STATUS
    if ($action === 'toggle_status') {
        $course_id = (int)($_POST['course_id'] ?? 0);
        if ($course_id > 0) {
            $stmt = $pdo->prepare("UPDATE courses SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
            $stmt->execute([$course_id]);
            $success_msg = 'Course status updated!';
        }
    }

    // TOGGLE FEATURED
    if ($action === 'toggle_featured') {
        $course_id = (int)($_POST['course_id'] ?? 0);
        if ($course_id > 0) {
            $stmt = $pdo->prepare("UPDATE courses SET is_featured = IF(is_featured = 1, 0, 1) WHERE id = ?");
            $stmt->execute([$course_id]);
            $success_msg = 'Featured status updated!';
        }
    }

    // ADD CATEGORY
    if ($action === 'add_category') {
        $cat_name = trim($_POST['cat_name'] ?? '');
        $cat_icon = trim($_POST['cat_icon'] ?? 'fa-graduation-cap');
        if (!empty($cat_name)) {
            $cat_slug = preg_replace('/[^a-z0-9]+/i', '-', trim(strtolower($cat_name)));
            $cat_slug = trim($cat_slug, '-');
            
            $chk = $pdo->prepare("SELECT id FROM course_categories WHERE slug = ?");
            $chk->execute([$cat_slug]);
            if ($chk->fetch()) {
                $cat_slug .= '-' . rand(10, 99);
            }

            $stmt = $pdo->prepare("INSERT INTO course_categories (name, slug, icon, status) VALUES (?, ?, ?, 'active')");
            $stmt->execute([$cat_name, $cat_slug, $cat_icon]);
            $success_msg = "Category '{$cat_name}' added!";
        } else {
            $error_msg = 'Please enter a category name.';
        }
    }

    // DELETE CATEGORY
    if ($action === 'delete_category') {
        $cat_id = (int)($_POST['cat_id'] ?? 0);
        if ($cat_id > 0) {
            $chk = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE category_id = ?");
            $chk->execute([$cat_id]);
            if ($chk->fetchColumn() > 0) {
                $error_msg = 'Cannot delete category: courses exist under it.';
            } else {
                $stmt = $pdo->prepare("DELETE FROM course_categories WHERE id = ?");
                $stmt->execute([$cat_id]);
                $success_msg = 'Category removed!';
            }
        }
    }
}

// -------------------------------------------------------------
// FETCH DATA
// -------------------------------------------------------------
$total_count = (int)$pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$active_count = (int)$pdo->query("SELECT COUNT(*) FROM courses WHERE status = 'active'")->fetchColumn();

// Fetch Categories
$categories = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM courses WHERE category_id = c.id) as course_count 
                           FROM course_categories c ORDER BY c.name ASC")->fetchAll();

// Filters & Search
$selected_category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$selected_status = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

$query = "SELECT cr.*, cat.name as category_name, cat.icon as category_icon 
          FROM courses cr 
          JOIN course_categories cat ON cr.category_id = cat.id 
          WHERE 1=1";
$params = [];

if ($selected_category > 0) {
    $query .= " AND cr.category_id = ?";
    $params[] = $selected_category;
}

if (!empty($selected_status)) {
    $query .= " AND cr.status = ?";
    $params[] = $selected_status;
}

if (!empty($search)) {
    $query .= " AND (cr.title LIKE ? OR cr.short_desc LIKE ?)";
    $term = "%{$search}%";
    $params = array_merge($params, [$term, $term]);
}

$query .= " ORDER BY cr.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$courses = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-4">

    <!-- PAGE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-md border border-slate-200">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-base font-bold text-[#0e1e2e]">Courses Directory</h2>
                <span class="px-2 py-0.5 text-[11px] font-semibold bg-slate-100 text-slate-600 rounded border border-slate-200">
                    <?= $total_count ?> Total (<?= $active_count ?> Active)
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">Manage training programs, categories, syllabus and tuition settings</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="categories.php" class="px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:text-[#fe7c03] rounded-md transition-colors flex items-center gap-1 no-underline">
                <i class="fa-solid fa-layer-group text-slate-400"></i>
                <span>Categories</span>
            </a>
            <a href="course-add.php" class="px-3.5 py-1.5 text-xs font-semibold text-white bg-[#fe7c03] hover:bg-[#ea6c00] rounded-md transition-colors flex items-center gap-1 no-underline">
                <i class="fa-solid fa-plus text-[11px]"></i>
                <span>Add Course</span>
            </a>
        </div>
    </div>

    <!-- ALERTS -->
    <?php if (!empty($success_msg)): ?>
        <div class="p-3 text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-md flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-500"></i>
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
                <i class="fa-solid fa-circle-exclamation text-rose-500"></i>
                <span><?= htmlspecialchars($error_msg) ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- FILTER & SEARCH BAR -->
    <div class="bg-white border border-slate-200 rounded-md p-3">
        <form method="GET" action="courses.php" class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2">
                <!-- Category Select -->
                <select name="category" onchange="this.form.submit()" class="text-xs bg-white border border-slate-200 rounded-md px-2 py-1.5 text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                    <option value="">All Categories (<?= count($categories) ?>)</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $selected_category === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?> (<?= $cat['course_count'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Status Select -->
                <select name="status" onchange="this.form.submit()" class="text-xs bg-white border border-slate-200 rounded-md px-2 py-1.5 text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                    <option value="">All Status</option>
                    <option value="active" <?= $selected_status === 'active' ? 'selected' : '' ?>>Active only</option>
                    <option value="inactive" <?= $selected_status === 'inactive' ? 'selected' : '' ?>>Inactive only</option>
                </select>

                <?php if ($selected_category > 0 || !empty($selected_status) || !empty($search)): ?>
                    <a href="courses.php" class="px-2 py-1 text-xs text-slate-500 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 rounded transition-colors flex items-center gap-1 no-underline">
                        <i class="fa-solid fa-xmark text-[10px]"></i> Reset
                    </a>
                <?php endif; ?>
            </div>

            <!-- Search Input -->
            <div class="relative min-w-[220px]">
                <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="search" placeholder="Search course title..." value="<?= htmlspecialchars($search) ?>" class="w-full text-xs pl-8 pr-3 py-1.5 bg-white border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03] text-slate-800">
            </div>
        </form>
    </div>

    <!-- COURSES TABLE -->
    <div class="bg-white border border-slate-200 rounded-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="px-4 py-2">Course</th>
                        <th class="px-4 py-2">Category</th>
                        <th class="px-4 py-2">Duration</th>
                        <th class="px-4 py-2">Fee</th>
                        <th class="px-4 py-2">Mode</th>
                        <th class="px-4 py-2 text-center">Featured</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($courses)): ?>
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                                <i class="fa-regular fa-folder-open text-2xl mb-1 text-slate-300 block"></i>
                                <span class="font-semibold text-slate-700 block">No courses found</span>
                                <span class="text-[11px]">Try changing your filters or add a new course.</span>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($courses as $c): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <!-- Title & Thumb -->
                                <td class="px-4 py-2">
                                    <div class="flex items-center gap-2">
                                        <?php if (!empty($c['image'])): ?>
                                            <img src="../<?= htmlspecialchars($c['image']) ?>" alt="" class="w-10 h-7 rounded object-cover border border-slate-200 bg-slate-100 shrink-0">
                                        <?php else: ?>
                                            <div class="w-10 h-7 rounded border border-slate-200 bg-slate-100 flex items-center justify-center text-slate-400 shrink-0">
                                                <i class="fa-solid <?= htmlspecialchars($c['category_icon']) ?> text-[10px]"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <a href="course-edit.php?id=<?= $c['id'] ?>" class="font-semibold text-[#0e1e2e] hover:text-[#fe7c03] no-underline block">
                                                <?= htmlspecialchars($c['title']) ?>
                                            </a>
                                            <span class="text-[10px] text-slate-400">/course-detail.php?slug=<?= htmlspecialchars($c['slug']) ?></span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Category -->
                                <td class="px-4 py-2">
                                    <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200">
                                        <i class="fa-solid <?= htmlspecialchars($c['category_icon']) ?> text-[9px] text-[#fe7c03]"></i>
                                        <?= htmlspecialchars($c['category_name']) ?>
                                    </span>
                                </td>

                                <!-- Duration -->
                                <td class="px-4 py-2 text-slate-700 font-medium"><?= htmlspecialchars($c['duration']) ?></td>

                                <!-- Fee -->
                                <td class="px-4 py-2 font-semibold text-emerald-700">
                                    <?= !empty($c['fee']) ? htmlspecialchars($c['fee']) : '<span class="text-slate-300 font-normal text-[11px]">Not set</span>' ?>
                                </td>

                                <!-- Mode & Modules -->
                                <td class="px-4 py-2">
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[11px] font-medium <?= (stripos($c['study_mode'], 'online') !== false) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-blue-50 text-blue-700 border border-blue-200' ?>">
                                        <?= htmlspecialchars($c['study_mode']) ?>
                                    </span>
                                    <?php if (!empty($c['modules'])): ?>
                                        <div class="text-[10.5px] text-slate-400 mt-0.5"><i class="fa-solid fa-book-open text-[9px] mr-1"></i><?= htmlspecialchars($c['modules']) ?></div>
                                    <?php endif; ?>
                                </td>

                                <!-- Featured Star -->
                                <td class="px-4 py-2 text-center">
                                    <form method="POST" action="courses.php" class="inline-block">
                                        <input type="hidden" name="action" value="toggle_featured">
                                        <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="p-1 text-xs text-slate-300 hover:text-amber-400 transition-colors" title="Toggle Featured">
                                            <?php if ($c['is_featured']): ?>
                                                <i class="fa-solid fa-star text-amber-400"></i>
                                            <?php else: ?>
                                                <i class="fa-regular fa-star text-slate-300"></i>
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-2">
                                    <form method="POST" action="courses.php" class="inline-block">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="px-2 py-0.5 text-[10px] font-semibold rounded transition-colors <?= $c['status'] === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200' ?>" title="Click to toggle status">
                                            <span class="inline-block w-1.5 h-1.5 rounded-full <?= $c['status'] === 'active' ? 'bg-emerald-500' : 'bg-slate-400' ?> mr-1"></span>
                                            <?= ucfirst($c['status']) ?>
                                        </button>
                                    </form>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-2 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <!-- Edit -->
                                        <a href="course-edit.php?id=<?= $c['id'] ?>" class="w-7 h-7 rounded border border-slate-200 bg-white text-slate-600 hover:text-[#fe7c03] hover:border-orange-200 flex items-center justify-center transition-colors no-underline" title="Edit Course">
                                            <i class="fa-solid fa-pen text-[10px]"></i>
                                        </a>

                                        <!-- Live Preview -->
                                        <a href="../course-detail.php?slug=<?= htmlspecialchars($c['slug']) ?>" target="_blank" class="w-7 h-7 rounded border border-slate-200 bg-white text-slate-600 hover:text-sky-600 hover:border-sky-200 flex items-center justify-center transition-colors no-underline" title="Public Preview">
                                            <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                        </a>

                                        <!-- Delete -->
                                        <form method="POST" action="courses.php" onsubmit="return confirm('Delete course \'<?= htmlspecialchars(addslashes($c['title'])) ?>\'?');" class="inline-block">
                                            <input type="hidden" name="action" value="delete_course">
                                            <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                                            <button type="submit" class="w-7 h-7 rounded border border-slate-200 bg-white text-slate-400 hover:text-rose-600 hover:border-rose-200 flex items-center justify-center transition-colors" title="Delete Course">
                                                <i class="fa-regular fa-trash-can text-[10px]"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- CATEGORY MODAL -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border border-slate-200 rounded-md shadow-none">
            <div class="modal-header border-b border-slate-200 py-3 px-4 bg-slate-50">
                <h6 class="modal-title text-xs font-bold text-[#0e1e2e] uppercase tracking-wider mb-0">Manage Course Categories</h6>
                <button type="button" class="btn-close text-xs" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 space-y-3">
                
                <!-- Quick Add -->
                <form method="POST" action="courses.php" class="flex gap-2">
                    <input type="hidden" name="action" value="add_category">
                    <input type="text" name="cat_name" class="flex-1 text-xs border border-slate-200 rounded-md px-3 py-1.5 focus:outline-none focus:border-[#fe7c03]" placeholder="New category name..." required>
                    <input type="text" name="cat_icon" class="w-32 text-xs border border-slate-200 rounded-md px-2 py-1.5 focus:outline-none focus:border-[#fe7c03]" value="fa-graduation-cap" placeholder="Icon class">
                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white bg-[#fe7c03] hover:bg-[#ea6c00] rounded-md transition-colors">Add</button>
                </form>

                <!-- List -->
                <div class="border border-slate-200 rounded-md overflow-hidden max-h-64 overflow-y-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold text-[10px] uppercase">
                            <tr>
                                <th class="px-3 py-2">Category</th>
                                <th class="px-3 py-2 text-center">Courses</th>
                                <th class="px-3 py-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td class="px-3 py-2">
                                        <i class="fa-solid <?= htmlspecialchars($cat['icon']) ?> text-[#fe7c03] text-[10px] mr-1.5"></i>
                                        <strong class="text-slate-800"><?= htmlspecialchars($cat['name']) ?></strong>
                                    </td>
                                    <td class="px-3 py-2 text-center text-slate-500"><?= $cat['course_count'] ?></td>
                                    <td class="px-3 py-2 text-right">
                                        <?php if ($cat['course_count'] == 0): ?>
                                            <form method="POST" action="courses.php" onsubmit="return confirm('Delete this category?');" class="inline-block">
                                                <input type="hidden" name="action" value="delete_category">
                                                <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                                                <button type="submit" class="text-rose-500 hover:text-rose-700 text-xs">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-slate-300 text-[10px]" title="Contains courses"><i class="fa-solid fa-lock"></i></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
            <div class="modal-footer border-t border-slate-200 py-2 px-4 bg-slate-50">
                <button type="button" class="px-3 py-1.5 text-xs text-slate-600 bg-white border border-slate-200 rounded-md hover:bg-slate-50" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
