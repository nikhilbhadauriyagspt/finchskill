<?php
require_once __DIR__ . '/includes/auth.php';
checkAdminAuth();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = ($id > 0);
$page_title = $is_edit ? "Edit Page / Policy" : "Create New Page";
$error_msg = '';
$success_msg = '';

$page = [
    'id' => 0,
    'slug' => '',
    'title' => '',
    'subtitle' => '',
    'content' => '',
    'meta_title' => '',
    'meta_description' => '',
    'status' => 'active',
    'is_system' => 0
];

if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM site_pages WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        header("Location: pages.php");
        exit;
    }
    $page = $existing;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

    // Auto-generate slug if empty
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    } else {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug)));
    }

    if (empty($title) || empty($slug) || empty($content)) {
        $error_msg = "Please provide Page Title, URL Slug, and Content.";
    } else {
        // Check slug uniqueness
        $chk_sql = "SELECT id FROM site_pages WHERE slug = ?" . ($is_edit ? " AND id != ?" : "");
        $chk_params = $is_edit ? [$slug, $id] : [$slug];
        $chk_stmt = $pdo->prepare($chk_sql);
        $chk_stmt->execute($chk_params);

        if ($chk_stmt->fetch()) {
            $error_msg = "The URL slug '<strong>" . htmlspecialchars($slug) . "</strong>' is already in use by another page. Please choose a unique slug.";
        } else {
            try {
                if ($is_edit) {
                    $upd = $pdo->prepare("UPDATE site_pages SET 
                        title = ?, 
                        slug = ?, 
                        subtitle = ?, 
                        content = ?, 
                        meta_title = ?, 
                        meta_description = ?, 
                        status = ? 
                        WHERE id = ?");
                    $upd->execute([$title, $slug, $subtitle, $content, $meta_title, $meta_description, $status, $id]);
                    header("Location: pages.php?msg=updated");
                    exit;
                } else {
                    $ins = $pdo->prepare("INSERT INTO site_pages 
                        (title, slug, subtitle, content, meta_title, meta_description, status, is_system) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
                    $ins->execute([$title, $slug, $subtitle, $content, $meta_title, $meta_description, $status]);
                    header("Location: pages.php?msg=created");
                    exit;
                }
            } catch (Exception $e) {
                $error_msg = "Database Error: " . $e->getMessage();
            }
        }
    }

    // Keep submitted values in case of error
    $page['title'] = $title;
    $page['slug'] = $slug;
    $page['subtitle'] = $subtitle;
    $page['content'] = $content;
    $page['meta_title'] = $meta_title;
    $page['meta_description'] = $meta_description;
    $page['status'] = $status;
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Quill Rich Text Editor CSS -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
    .ql-toolbar.ql-snow {
        border-color: #cbd5e1 !important;
        border-top-left-radius: 6px;
        border-top-right-radius: 6px;
        background: #f8fafc;
        font-family: inherit;
    }
    .ql-container.ql-snow {
        border-color: #cbd5e1 !important;
        border-bottom-left-radius: 6px;
        border-bottom-right-radius: 6px;
        background: #ffffff;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 14px;
        min-height: 380px;
    }
    .ql-editor {
        min-height: 380px;
        line-height: 1.7;
        color: #1e293b;
    }
    .ql-editor h1, .ql-editor h2, .ql-editor h3 {
        color: #0f172a;
        font-weight: 700;
        margin-top: 1.2rem;
        margin-bottom: 0.6rem;
    }
    .ql-editor p {
        margin-bottom: 0.8rem;
    }
</style>

<div class="space-y-4 max-w-5xl mx-auto pb-12">

    <!-- TOP ACTION HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200">
        <div>
            <h2 class="text-base font-bold text-[#0e1e2e] flex items-center gap-2">
                <i class="fa-solid <?= $is_edit ? 'fa-pen-to-square' : 'fa-plus-circle' ?> text-[#fe7c03]"></i>
                <span><?= $is_edit ? 'Edit Page / Policy Content' : 'Create New Static Page' ?></span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
                <?= $is_edit ? 'Update content, formatted headings, and SEO meta tags for ' . htmlspecialchars($page['title']) : 'Design a new custom page or policy document with live rich text formatting' ?>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($is_edit): ?>
                <a href="../page.php?slug=<?= urlencode($page['slug']) ?>" target="_blank" class="px-3 py-1.5 rounded text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors no-underline inline-flex items-center gap-1">
                    <i class="fa-solid fa-eye text-slate-400"></i> View Live Page
                </a>
            <?php endif; ?>
            <a href="pages.php" class="px-3 py-1.5 rounded text-xs font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 transition-colors no-underline">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Pages
            </a>
        </div>
    </div>

    <!-- ERROR ALERT -->
    <?php if (!empty($error_msg)): ?>
        <div class="p-3 text-xs bg-rose-50 text-rose-700 border border-rose-200 rounded-md flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-rose-500 shrink-0"></i>
                <span><?= $error_msg ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- EDIT / CREATE FORM -->
    <form method="POST" id="pageEditForm" class="space-y-4">
        
        <!-- Main Card: Title, URL, Subtitle -->
        <div class="bg-white border border-slate-200 rounded-md p-4 sm:p-5 space-y-4">
            <div class="pb-2.5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider">
                    Page Header &amp; URL Settings
                </h3>
                <?php if (!empty($page['is_system'])): ?>
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-blue-50 text-blue-700 border border-blue-200">
                        System Policy Page
                    </span>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Title -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Page Title <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" id="pageTitleInput" required value="<?= htmlspecialchars($page['title']) ?>" placeholder="e.g. Terms and Conditions" class="w-full text-xs rounded border border-slate-200 px-3 py-2 text-slate-800 focus:outline-none focus:border-[#fe7c03]">
                </div>

                <!-- Slug -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">URL Slug <span class="text-rose-500">*</span></label>
                    <div class="flex items-center rounded border border-slate-200 bg-slate-50 overflow-hidden focus-within:border-[#fe7c03]">
                        <span class="px-2 py-2 text-slate-400 text-xs font-mono select-none bg-slate-100 border-r border-slate-200">/page.php?slug=</span>
                        <input type="text" name="slug" id="pageSlugInput" required value="<?= htmlspecialchars($page['slug']) ?>" placeholder="terms-conditions" class="w-full text-xs bg-white px-2 py-2 text-slate-800 font-mono focus:outline-none" <?= !empty($page['is_system']) ? 'readonly' : '' ?>>
                    </div>
                    <?php if (!empty($page['is_system'])): ?>
                        <p class="text-[10px] text-slate-400 mt-1">System policy slugs cannot be modified to preserve link integrity.</p>
                    <?php endif; ?>
                </div>

                <!-- Subtitle / Tagline -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Banner Subtitle / Tagline (Optional)</label>
                    <input type="text" name="subtitle" value="<?= htmlspecialchars($page['subtitle'] ?? '') ?>" placeholder="e.g. Please read these terms carefully before applying" class="w-full text-xs rounded border border-slate-200 px-3 py-2 text-slate-800 focus:outline-none focus:border-[#fe7c03]">
                </div>
            </div>
        </div>

        <!-- Rich Text Content Editor Card -->
        <div class="bg-white border border-slate-200 rounded-md p-4 sm:p-5 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div>
                    <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider">
                        Page Content (WYSIWYG Rich Text Editor) <span class="text-rose-500">*</span>
                    </h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Use headings, bold, lists, and links to structure clean policy documentation</p>
                </div>
                <div class="text-[11px] text-slate-400 font-mono">
                    <span id="wordCountDisplay">0 words</span>
                </div>
            </div>

            <!-- Hidden input to store raw HTML from Quill -->
            <input type="hidden" name="content" id="hiddenContentInput" value="<?= htmlspecialchars($page['content']) ?>">

            <!-- Quill Editor Container -->
            <div id="quillEditor"><?= $page['content'] ?></div>
        </div>

        <!-- SEO Metadata & Status Card -->
        <div class="bg-white border border-slate-200 rounded-md p-4 sm:p-5 space-y-4">
            <div class="pb-2.5 border-b border-slate-100">
                <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider">
                    SEO Meta Details &amp; Publication Status
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Status -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Publication Status</label>
                    <select name="status" class="w-full text-xs rounded border border-slate-200 px-3 py-2 text-slate-800 bg-white focus:outline-none focus:border-[#fe7c03]">
                        <option value="active" <?= $page['status'] === 'active' ? 'selected' : '' ?>>Active (Published &amp; Live)</option>
                        <option value="inactive" <?= $page['status'] === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden / Draft)</option>
                    </select>
                </div>

                <!-- Meta Title -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Meta Browser Title (SEO)</label>
                    <input type="text" name="meta_title" value="<?= htmlspecialchars($page['meta_title'] ?? '') ?>" placeholder="e.g. Terms and Conditions - Finchskills Institute" class="w-full text-xs rounded border border-slate-200 px-3 py-2 text-slate-800 focus:outline-none focus:border-[#fe7c03]">
                </div>

                <!-- Meta Description -->
                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Meta Description (SEO)</label>
                    <textarea name="meta_description" rows="2" placeholder="Brief summary for Google search engines..." class="w-full text-xs rounded border border-slate-200 px-3 py-2 text-slate-800 focus:outline-none focus:border-[#fe7c03]"><?= htmlspecialchars($page['meta_description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Save Actions Bar -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="pages.php" class="px-4 py-2 rounded text-xs font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 transition-colors no-underline">
                Cancel
            </a>
            <button type="submit" id="savePageBtn" class="px-5 py-2 rounded bg-[#fe7c03] hover:bg-[#e06b00] text-white text-xs font-bold transition-colors inline-flex items-center gap-2">
                <i class="fa-solid fa-floppy-disk"></i>
                <span><?= $is_edit ? 'Save Changes' : 'Publish Page' ?></span>
            </button>
        </div>

    </form>
</div>

<!-- Quill Rich Text Editor JS -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isEdit = <?= json_encode($is_edit) ?>;
    const isSystem = <?= json_encode(!empty($page['is_system'])) ?>;
    const titleInput = document.getElementById('pageTitleInput');
    const slugInput = document.getElementById('pageSlugInput');
    const hiddenContent = document.getElementById('hiddenContentInput');
    const wordCountDisplay = document.getElementById('wordCountDisplay');

    // Initialize Quill
    const quill = new Quill('#quillEditor', {
        theme: 'snow',
        placeholder: 'Write your policy or page content here...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, 4, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['blockquote', 'code-block'],
                ['link'],
                ['clean']
            ]
        }
    });

    // Calculate Word Count
    function updateWordCount() {
        const text = quill.getText().trim();
        const words = text ? text.split(/\s+/).length : 0;
        wordCountDisplay.textContent = words + ' words';
    }

    quill.on('text-change', function() {
        updateWordCount();
    });
    updateWordCount();

    // Auto Slug Generator if new page and not system
    if (!isEdit && !isSystem) {
        titleInput.addEventListener('input', function() {
            slugInput.value = this.value
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/[\s-]+/g, '-');
        });
    }

    // Sync Quill HTML to hidden input before submit
    document.getElementById('pageEditForm').addEventListener('submit', function(e) {
        const html = quill.root.innerHTML;
        const text = quill.getText().trim();

        if (!text || text.length === 0) {
            alert('Please enter some content for the page before saving.');
            e.preventDefault();
            return false;
        }

        hiddenContent.value = html;
        document.getElementById('savePageBtn').disabled = true;
        document.getElementById('savePageBtn').innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
