<?php
require_once __DIR__ . '/includes/auth.php';
checkAdminAuth();

$success_msg = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted') $success_msg = 'Inquiry deleted successfully!';
    if ($_GET['msg'] === 'status_updated') $success_msg = 'Inquiry status updated successfully!';
}

// Handle Actions BEFORE HTML output
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($_GET['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM contact_inquiries WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: contacts.php?msg=deleted");
        exit;
    }
    if (in_array($_GET['action'], ['New', 'In Progress', 'Resolved'])) {
        $stmt = $pdo->prepare("UPDATE contact_inquiries SET status = ? WHERE id = ?");
        $stmt->execute([$_GET['action'], $id]);
        header("Location: contacts.php?msg=status_updated");
        exit;
    }
}

// Filters & Search
$search = trim($_GET['search'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

$query = "SELECT * FROM contact_inquiries WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $term = "%$search%";
    $params = array_merge($params, [$term, $term, $term, $term, $term]);
}
if (!empty($filter_status)) {
    $query .= " AND status = ?";
    $params[] = $filter_status;
}

$query .= " ORDER BY id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$inquiries = $stmt->fetchAll();

// Mark unread as read
$pdo->query("UPDATE contact_inquiries SET is_read = 1 WHERE is_read = 0");

$page_title = "Contact Inquiries";
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-4">

    <?php if (!empty($success_msg)): ?>
        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs rounded-md flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-500"></i>
                <span class="font-medium"><?= htmlspecialchars($success_msg) ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fa-solid fa-xmark"></i></button>
        </div>
    <?php endif; ?>

    <!-- PAGE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-md border border-slate-200">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-base font-bold text-[#0e1e2e]">Contact Inquiries &amp; Leads</h2>
                <span class="px-2 py-0.5 text-[11px] font-semibold bg-slate-100 text-slate-600 rounded border border-slate-200">
                    <?= count($inquiries) ?> Total Inquiries
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">Manage messages and student inquiries sent through the public contact form</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="../contact.php" target="_blank" class="px-3.5 py-1.5 text-xs font-semibold text-white bg-[#fe7c03] hover:bg-[#ea6c00] rounded-md transition-colors flex items-center gap-1 no-underline">
                <i class="fa-solid fa-envelope text-[11px]"></i>
                <span>Public Contact Page</span>
            </a>
        </div>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="bg-white border border-slate-200 rounded-md p-3">
        <form method="GET" action="contacts.php" class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2">
                <select name="status" onchange="this.form.submit()" class="text-xs bg-white border border-slate-200 rounded-md px-2 py-1.5 text-slate-700 focus:outline-none focus:border-[#fe7c03]">
                    <option value="">All Statuses</option>
                    <option value="New" <?= $filter_status === 'New' ? 'selected' : '' ?>>New</option>
                    <option value="In Progress" <?= $filter_status === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="Resolved" <?= $filter_status === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                </select>

                <?php if (!empty($filter_status) || !empty($search)): ?>
                    <a href="contacts.php" class="px-2 py-1 text-xs text-slate-500 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 rounded transition-colors flex items-center gap-1 no-underline">
                        <i class="fa-solid fa-xmark text-[10px]"></i> Reset
                    </a>
                <?php endif; ?>
            </div>

            <!-- Search Input -->
            <div class="relative min-w-[240px]">
                <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="search" placeholder="Search sender, email, message..." value="<?= htmlspecialchars($search) ?>" class="w-full text-xs pl-8 pr-3 py-1.5 bg-white border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03] text-slate-800">
            </div>
        </form>
    </div>

    <!-- INQUIRIES TABLE -->
    <div class="bg-white border border-slate-200 rounded-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="px-4 py-2">#</th>
                        <th class="px-4 py-2">Sender Info</th>
                        <th class="px-4 py-2">Subject</th>
                        <th class="px-4 py-2">Message Snippet</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2">Received Date</th>
                        <th class="px-4 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($inquiries)): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400">
                                <i class="fa-solid fa-inbox text-2xl mb-1 text-slate-300 block"></i>
                                <span class="font-semibold text-slate-700 block">No inquiries found</span>
                                <span class="text-[11px]">No contact messages match your search or filter.</span>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inquiries as $idx => $row): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-2 font-mono text-[11px] text-slate-400"><?= $idx + 1 ?></td>
                                
                                <td class="px-4 py-2">
                                    <div class="font-semibold text-[#0e1e2e]"><?= htmlspecialchars($row['name']) ?></div>
                                    <div class="text-[11px] text-slate-500"><i class="fa-regular fa-envelope text-[9px] text-slate-400 mr-1"></i><a href="mailto:<?= htmlspecialchars($row['email']) ?>" class="hover:text-[#fe7c03] no-underline text-inherit"><?= htmlspecialchars($row['email']) ?></a></div>
                                    <?php if (!empty($row['phone'])): ?>
                                        <div class="text-[10px] text-slate-400"><i class="fa-solid fa-phone text-[9px] text-slate-400 mr-1"></i><a href="tel:<?= htmlspecialchars($row['phone']) ?>" class="hover:text-[#fe7c03] no-underline text-inherit"><?= htmlspecialchars($row['phone']) ?></a></div>
                                    <?php endif; ?>
                                </td>

                                <td class="px-4 py-2">
                                    <span class="px-2 py-0.5 text-[10px] font-medium rounded bg-slate-100 text-slate-700 border border-slate-200 inline-block">
                                        <?= htmlspecialchars($row['subject'] ?? 'General') ?>
                                    </span>
                                </td>

                                <td class="px-4 py-2 text-slate-600 max-w-xs">
                                    <div class="truncate"><?= htmlspecialchars($row['message']) ?></div>
                                </td>

                                <td class="px-4 py-2">
                                    <div class="dropdown inline-block">
                                        <button class="px-2 py-0.5 text-[10px] font-semibold rounded border dropdown-toggle <?= $row['status'] === 'Resolved' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($row['status'] === 'In Progress' ? 'bg-sky-50 text-sky-700 border-sky-200' : 'bg-rose-50 text-rose-700 border-rose-200') ?>" type="button" data-bs-toggle="dropdown">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </button>
                                        <ul class="dropdown-menu text-xs border border-slate-200 rounded-md py-1">
                                            <li><a class="dropdown-item py-1.5" href="contacts.php?action=New&id=<?= $row['id'] ?>">Mark New</a></li>
                                            <li><a class="dropdown-item py-1.5 text-sky-600" href="contacts.php?action=In Progress&id=<?= $row['id'] ?>">Set In Progress</a></li>
                                            <li><a class="dropdown-item py-1.5 text-emerald-600" href="contacts.php?action=Resolved&id=<?= $row['id'] ?>">Set Resolved</a></li>
                                        </ul>
                                    </div>
                                </td>

                                <td class="px-4 py-2 text-slate-400 text-[11px] whitespace-nowrap">
                                    <?= date('d M Y', strtotime($row['created_at'])) ?><br>
                                    <span class="text-[10px]"><?= date('h:i A', strtotime($row['created_at'])) ?></span>
                                </td>

                                <td class="px-4 py-2 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <!-- View Modal -->
                                        <button type="button" class="w-7 h-7 rounded border border-slate-200 bg-white text-slate-600 hover:text-[#fe7c03] hover:border-orange-200 inline-flex items-center justify-center transition-colors" data-bs-toggle="modal" data-bs-target="#inquiryModal<?= $row['id'] ?>" title="View Message">
                                            <i class="fa-solid fa-eye text-[10px]"></i>
                                        </button>
                                        <!-- Email Reply -->
                                        <a href="mailto:<?= htmlspecialchars($row['email']) ?>?subject=Re: <?= urlencode($row['subject'] ?? 'Your Inquiry') ?>" class="w-7 h-7 rounded border border-slate-200 bg-white text-slate-600 hover:text-emerald-600 hover:border-emerald-200 inline-flex items-center justify-center transition-colors no-underline" title="Reply via Email">
                                            <i class="fa-solid fa-reply text-[10px]"></i>
                                        </a>
                                        <!-- Delete -->
                                        <a href="contacts.php?action=delete&id=<?= $row['id'] ?>" onclick="return confirm('Delete this inquiry?')" class="w-7 h-7 rounded border border-slate-200 bg-white text-slate-400 hover:text-rose-600 hover:border-rose-200 inline-flex items-center justify-center transition-colors no-underline" title="Delete">
                                            <i class="fa-regular fa-trash-can text-[10px]"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <!-- INQUIRY MODAL -->
                            <div class="modal fade" id="inquiryModal<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border border-slate-200 rounded-md shadow-none overflow-hidden">
                                        <div class="modal-header border-b border-slate-200 py-3 px-4 bg-slate-50">
                                            <div class="flex items-center gap-2">
                                                <span class="w-6 text-center text-[#fe7c03]"><i class="fa-solid fa-envelope-open text-sm"></i></span>
                                                <div>
                                                    <h5 class="modal-title text-xs font-bold text-[#0e1e2e] uppercase tracking-wider mb-0">Inquiry Details</h5>
                                                    <span class="text-[10px] text-slate-400">From <?= htmlspecialchars($row['name']) ?> &bull; <?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></span>
                                                </div>
                                            </div>
                                            <button type="button" class="btn-close text-xs" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4 space-y-3 bg-white text-xs">
                                            <div>
                                                <span class="text-[10px] text-slate-400 block uppercase font-bold">Subject</span>
                                                <span class="font-semibold text-slate-800 text-sm"><?= htmlspecialchars($row['subject']) ?></span>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2 p-2 bg-slate-50 rounded border border-slate-100">
                                                <div>
                                                    <span class="text-[10px] text-slate-400 block uppercase">Email</span>
                                                    <a href="mailto:<?= htmlspecialchars($row['email']) ?>" class="font-semibold text-slate-800 hover:text-[#fe7c03]"><?= htmlspecialchars($row['email']) ?></a>
                                                </div>
                                                <div>
                                                    <span class="text-[10px] text-slate-400 block uppercase">Phone</span>
                                                    <span class="font-semibold text-slate-800"><?= htmlspecialchars($row['phone'] ?? 'N/A') ?></span>
                                                </div>
                                            </div>
                                            <div>
                                                <span class="text-[10px] text-slate-400 block uppercase font-bold mb-1">Message Content</span>
                                                <div class="p-3 bg-slate-50 border border-slate-200 rounded text-slate-700 leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars($row['message']) ?></div>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-t border-slate-200 py-2 px-4 bg-slate-50">
                                            <a href="mailto:<?= htmlspecialchars($row['email']) ?>?subject=Re: <?= urlencode($row['subject'] ?? 'Your Inquiry') ?>" class="px-3 py-1.5 text-xs font-semibold text-white bg-[#fe7c03] hover:bg-[#ea6c00] rounded-md transition-colors no-underline inline-flex items-center gap-1">
                                                <i class="fa-solid fa-reply text-[10px]"></i> Reply via Email
                                            </a>
                                            <button type="button" class="px-3 py-1.5 text-xs text-slate-600 bg-white border border-slate-200 rounded-md hover:bg-slate-50" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
