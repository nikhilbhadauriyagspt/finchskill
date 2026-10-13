<?php
require_once __DIR__ . '/includes/auth.php';
checkAdminAuth();

$page_title = "Pages & Policies";
$success_msg = '';
$error_msg = '';

// Handle Messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') $success_msg = 'Page created successfully!';
    if ($_GET['msg'] === 'updated') $success_msg = 'Page updated successfully!';
    if ($_GET['msg'] === 'deleted') $success_msg = 'Page deleted successfully!';
    if ($_GET['msg'] === 'status_updated') $success_msg = 'Page status updated!';
}

// Handle Status Toggle / Delete
if (isset($_GET['action']) && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    
    // Toggle Status
    if ($_GET['action'] === 'toggle_status') {
        $stmt = $pdo->prepare("UPDATE site_pages SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
        $stmt->execute([$target_id]);
        header("Location: pages.php?msg=status_updated");
        exit;
    }

    // Delete Custom Page (Protect system pages)
    if ($_GET['action'] === 'delete') {
        $chk = $pdo->prepare("SELECT is_system FROM site_pages WHERE id = ?");
        $chk->execute([$target_id]);
        $page = $chk->fetch();
        if ($page && $page['is_system'] == 0) {
            $del = $pdo->prepare("DELETE FROM site_pages WHERE id = ?");
            $del->execute([$target_id]);
            header("Location: pages.php?msg=deleted");
            exit;
        } else {
            $error_msg = 'System policy pages cannot be deleted. You can edit their content or deactivate them.';
        }
    }
}

// Fetch Stats
$total_pages = (int)$pdo->query("SELECT COUNT(*) FROM site_pages")->fetchColumn();
$active_pages = (int)$pdo->query("SELECT COUNT(*) FROM site_pages WHERE status = 'active'")->fetchColumn();
$system_pages = (int)$pdo->query("SELECT COUNT(*) FROM site_pages WHERE is_system = 1")->fetchColumn();
$custom_pages = (int)$pdo->query("SELECT COUNT(*) FROM site_pages WHERE is_system = 0")->fetchColumn();

// Filter & Search
$search = trim($_GET['search'] ?? '');
$filter_status = trim($_GET['status'] ?? '');
$filter_type = trim($_GET['type'] ?? '');

$query = "SELECT * FROM site_pages WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (title LIKE ? OR slug LIKE ? OR subtitle LIKE ? OR content LIKE ?)";
    $term = "%$search%";
    $params = array_merge($params, [$term, $term, $term, $term]);
}

if (!empty($filter_status)) {
    $query .= " AND status = ?";
    $params[] = $filter_status;
}

if ($filter_type === 'system') {
    $query .= " AND is_system = 1";
} elseif ($filter_type === 'custom') {
    $query .= " AND is_system = 0";
}

$query .= " ORDER BY is_system DESC, id ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-4">

    <!-- PAGE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-md border border-slate-200">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-base font-bold text-[#0e1e2e]">Pages &amp; Policy Manager</h2>
                <span class="px-2 py-0.5 text-[11px] font-semibold bg-slate-100 text-slate-600 rounded border border-slate-200">
                    <?= $total_pages ?> Total (<?= $active_pages ?> Active)
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">Manage policy documents, terms &amp; conditions, and custom static website pages</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="page-edit.php" class="px-3.5 py-1.5 text-xs font-semibold text-white bg-[#fe7c03] hover:bg-[#ea6c00] rounded-md transition-colors flex items-center gap-1 no-underline">
                <i class="fa-solid fa-plus text-[11px]"></i>
                <span>Create New Page</span>
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

    <!-- KPI STATS CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200 rounded-md p-3.5">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[11px] font-bold uppercase text-slate-400">Total Pages</span>
                <span class="w-7 h-7 rounded bg-orange-50 text-[#fe7c03] flex items-center justify-center text-xs"><i class="fa-solid fa-file-lines"></i></span>
            </div>
            <h4 class="text-xl font-bold text-[#0e1e2e]"><?= $total_pages ?></h4>
            <p class="text-[10px] text-slate-400 mt-1"><?= $active_pages ?> Published &amp; Live</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-md p-3.5">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[11px] font-bold uppercase text-slate-400">Legal &amp; Policy Pages</span>
                <span class="w-7 h-7 rounded bg-blue-50 text-blue-600 flex items-center justify-center text-xs"><i class="fa-solid fa-shield-halved"></i></span>
            </div>
            <h4 class="text-xl font-bold text-[#0e1e2e]"><?= $system_pages ?></h4>
            <p class="text-[10px] text-slate-400 mt-1">Privacy, Terms, Refund, etc.</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-md p-3.5">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[11px] font-bold uppercase text-slate-400">Custom Pages</span>
                <span class="w-7 h-7 rounded bg-teal-50 text-teal-600 flex items-center justify-center text-xs"><i class="fa-solid fa-file-circle-plus"></i></span>
            </div>
            <h4 class="text-xl font-bold text-[#0e1e2e]"><?= $custom_pages ?></h4>
            <p class="text-[10px] text-slate-400 mt-1">Created by Admin</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-md p-3.5">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[11px] font-bold uppercase text-slate-400">Active Status</span>
                <span class="w-7 h-7 rounded bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs"><i class="fa-solid fa-circle-check"></i></span>
            </div>
            <h4 class="text-xl font-bold text-emerald-600"><?= $active_pages ?> / <?= $total_pages ?></h4>
            <p class="text-[10px] text-slate-400 mt-1">Publicly Accessible</p>
        </div>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="bg-white border border-slate-200 rounded-md p-3">
        <form method="GET" action="pages.php" class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2">
                <!-- Status Filter -->
                <select name="status" onchange="this.form.submit()" class="text-xs bg-white border border-slate-200 rounded-md px-2 py-1.5 text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                    <option value="">All Statuses</option>
                    <option value="active" <?= $filter_status === 'active' ? 'selected' : '' ?>>Active Only</option>
                    <option value="inactive" <?= $filter_status === 'inactive' ? 'selected' : '' ?>>Inactive Only</option>
                </select>

                <!-- Type Filter -->
                <select name="type" onchange="this.form.submit()" class="text-xs bg-white border border-slate-200 rounded-md px-2 py-1.5 text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                    <option value="">All Page Types</option>
                    <option value="system" <?= $filter_type === 'system' ? 'selected' : '' ?>>System Policies</option>
                    <option value="custom" <?= $filter_type === 'custom' ? 'selected' : '' ?>>Custom Pages</option>
                </select>

                <?php if (!empty($search) || !empty($filter_status) || !empty($filter_type)): ?>
                    <a href="pages.php" class="text-xs text-rose-500 hover:text-rose-700 flex items-center gap-1 font-semibold no-underline">
                        <i class="fa-solid fa-xmark"></i> Reset Filters
                    </a>
                <?php endif; ?>
            </div>

            <!-- Search Input -->
            <div class="relative w-full sm:w-64">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search page title, slug..." class="w-full text-xs bg-white border border-slate-200 rounded-md pl-8 pr-3 py-1.5 text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2 text-[11px] text-slate-400"></i>
            </div>
        </form>
    </div>

    <!-- PAGES TABLE -->
    <div class="bg-white border border-slate-200 rounded-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="px-4 py-3">Page Title &amp; Details</th>
                        <th class="px-4 py-3">Slug / URL</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Last Modified</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    <?php if (empty($pages)): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                                <i class="fa-solid fa-folder-open text-2xl mb-2 text-slate-300 block"></i>
                                <span>No pages found matching your filters.</span>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pages as $p): ?>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                
                                <!-- Title -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded bg-orange-50 text-[#fe7c03] flex items-center justify-center font-bold text-xs shrink-0">
                                            <i class="fa-solid <?= $p['is_system'] ? 'fa-shield-halved' : 'fa-file-lines' ?>"></i>
                                        </div>
                                        <div>
                                            <a href="page-edit.php?id=<?= $p['id'] ?>" class="font-bold text-[#0e1e2e] hover:text-[#fe7c03] no-underline block leading-tight">
                                                <?= htmlspecialchars($p['title']) ?>
                                            </a>
                                            <?php if (!empty($p['subtitle'])): ?>
                                                <p class="text-[11px] text-slate-400 mt-0.5 truncate max-w-sm"><?= htmlspecialchars($p['subtitle']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- Slug / URL -->
                                <td class="px-4 py-3 font-mono text-[11px]">
                                    <a href="../page.php?slug=<?= urlencode($p['slug']) ?>" target="_blank" class="text-slate-600 hover:text-[#fe7c03] flex items-center gap-1 no-underline">
                                        <span>/page.php?slug=<?= htmlspecialchars($p['slug']) ?></span>
                                        <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-slate-400"></i>
                                    </a>
                                </td>

                                <!-- Type -->
                                <td class="px-4 py-3">
                                    <?php if ($p['is_system']): ?>
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-blue-50 text-blue-700 border border-blue-200">
                                            System Policy
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-slate-100 text-slate-600 border border-slate-200">
                                            Custom Page
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-3">
                                    <a href="pages.php?action=toggle_status&id=<?= $p['id'] ?>" title="Click to toggle status" class="no-underline">
                                        <?php if ($p['status'] === 'active'): ?>
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1 hover:bg-emerald-100">
                                                <i class="fa-solid fa-circle text-[6px]"></i> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-slate-100 text-slate-500 border border-slate-200 inline-flex items-center gap-1 hover:bg-slate-200">
                                                <i class="fa-solid fa-circle text-[6px]"></i> Inactive
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                </td>

                                <!-- Last Modified -->
                                <td class="px-4 py-3 text-slate-400 text-[11px] whitespace-nowrap">
                                    <?= date('d M Y, h:i A', strtotime($p['updated_at'] ?? $p['created_at'])) ?>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <!-- Public View -->
                                        <a href="../page.php?slug=<?= urlencode($p['slug']) ?>" target="_blank" title="View Public Page" class="w-7 h-7 rounded border border-slate-200 bg-white text-slate-600 hover:text-[#fe7c03] hover:border-orange-200 flex items-center justify-center transition-colors no-underline">
                                            <i class="fa-solid fa-eye text-xs"></i>
                                        </a>

                                        <!-- Edit -->
                                        <a href="page-edit.php?id=<?= $p['id'] ?>" title="Edit Page Content" class="w-7 h-7 rounded border border-slate-200 bg-white text-slate-600 hover:text-[#fe7c03] hover:border-orange-200 flex items-center justify-center transition-colors no-underline">
                                            <i class="fa-solid fa-pen text-xs"></i>
                                        </a>

                                        <!-- Delete (Custom Only) -->
                                        <?php if (!$p['is_system']): ?>
                                            <a href="pages.php?action=delete&id=<?= $p['id'] ?>" onclick="return confirm('Are you sure you want to permanently delete this page?')" title="Delete Page" class="w-7 h-7 rounded border border-slate-200 bg-white text-rose-500 hover:text-white hover:bg-rose-500 hover:border-rose-500 flex items-center justify-center transition-colors no-underline">
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                            </a>
                                        <?php endif; ?>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
