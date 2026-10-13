<?php
require_once __DIR__ . '/includes/auth.php';
checkAdminAuth();

$course_id = (int)($_GET['id'] ?? 0);
if ($course_id <= 0) {
    header("Location: courses.php");
    exit;
}

// Fetch Course
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course) {
    header("Location: courses.php");
    exit;
}

$page_title = "Edit: " . $course['title'];
$error_msg = '';

// Helper to make slug
function makeSlug($string) {
    $slug = preg_replace('/[^a-z0-9]+/i', '-', trim(strtolower($string)));
    return trim($slug, '-');
}

// Fetch categories
$categories = $pdo->query("SELECT * FROM course_categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = (int)($_POST['category_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug)) {
        $slug = makeSlug($title);
    } else {
        $slug = makeSlug($slug);
    }

    $duration = trim($_POST['duration'] ?? '12 Months');
    $study_mode = trim($_POST['study_mode'] ?? 'Offline');
    $modules = trim($_POST['modules'] ?? '6 Modules');
    $fee = trim($_POST['fee'] ?? '');
    $short_desc = trim($_POST['short_desc'] ?? '');
    $full_desc = trim($_POST['full_desc'] ?? '');
    $what_you_learn = trim($_POST['what_you_learn'] ?? '');
    $how_we_teach = trim($_POST['how_we_teach'] ?? '');
    $career_roles = trim($_POST['career_roles'] ?? '');
    $eligibility = trim($_POST['eligibility'] ?? '10+2 (12th Pass) in any stream');
    $certification = trim($_POST['certification'] ?? 'Industry Recognized Certification');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    if (empty($title)) {
        $error_msg = 'Please enter a course title.';
    } elseif ($category_id <= 0) {
        $error_msg = 'Please select a valid course category.';
    } else {
        // Ensure unique slug (excluding self)
        $chk = $pdo->prepare("SELECT id FROM courses WHERE slug = ? AND id != ?");
        $chk->execute([$slug, $course_id]);
        if ($chk->fetch()) {
            $slug .= '-' . rand(10, 99);
        }

        // Image upload handling
        $image_path = $course['image']; // Default to existing
        if (isset($_FILES['course_image']) && $_FILES['course_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/courses/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['course_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (in_array($ext, $allowed)) {
                $new_filename = 'course_' . time() . '_' . rand(100, 999) . '.' . $ext;
                if (move_uploaded_file($_FILES['course_image']['tmp_name'], $upload_dir . $new_filename)) {
                    // Remove old image if in uploads/courses/
                    if (!empty($course['image']) && strpos($course['image'], 'uploads/courses/') === 0 && file_exists(__DIR__ . '/../' . $course['image'])) {
                        @unlink(__DIR__ . '/../' . $course['image']);
                    }
                    $image_path = 'uploads/courses/' . $new_filename;
                }
            } else {
                $error_msg = 'Invalid image format. Allowed formats: JPG, PNG, WEBP.';
            }
        }

        if (empty($error_msg)) {
            $stmt = $pdo->prepare("UPDATE courses SET 
                category_id = ?, title = ?, slug = ?, duration = ?, study_mode = ?, modules = ?, fee = ?, image = ?,
                short_desc = ?, full_desc = ?, what_you_learn = ?, how_we_teach = ?, 
                career_roles = ?, eligibility = ?, certification = ?, is_featured = ?, status = ?
                WHERE id = ?");
            $stmt->execute([
                $category_id, $title, $slug, $duration, $study_mode, $modules, $fee, $image_path,
                $short_desc, $full_desc, $what_you_learn, $how_we_teach,
                $career_roles, $eligibility, $certification, $is_featured, $status,
                $course_id
            ]);

            header("Location: courses.php?msg=updated");
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Summernote Lite Styles -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<style>
.note-editor.note-frame {
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    box-shadow: none !important;
}
.note-toolbar {
    background: #f8fafc !important;
    border-bottom: 1px solid #e2e8f0 !important;
    border-top-left-radius: 5px;
    border-top-right-radius: 5px;
    padding: 4px 6px !important;
}
.note-btn {
    border: 1px solid transparent !important;
    background: transparent !important;
    border-radius: 4px !important;
    padding: 3px 6px !important;
    font-size: 11px !important;
}
.note-btn:hover {
    background: #e2e8f0 !important;
}
</style>

<div class="space-y-4">

    <!-- PAGE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-md border border-slate-200">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-base font-bold text-[#0e1e2e]">Edit Course</h2>
                <span class="px-2 py-0.5 text-[10px] font-semibold bg-sky-50 text-sky-700 border border-sky-200 rounded">ID #<?= $course['id'] ?></span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5"><?= htmlspecialchars($course['title']) ?></p>
        </div>
        <div class="flex items-center gap-2">
            <a href="../course-detail.php?slug=<?= htmlspecialchars($course['slug']) ?>" target="_blank" class="px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:text-sky-600 rounded-md transition-colors flex items-center gap-1 no-underline">
                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                <span>Public Page</span>
            </a>
            <a href="courses.php" class="px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:text-[#fe7c03] rounded-md transition-colors flex items-center gap-1 no-underline">
                <i class="fa-solid fa-arrow-left text-slate-400"></i>
                <span>Back</span>
            </a>
        </div>
    </div>

    <!-- ALERTS -->
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

    <!-- MAIN FORM -->
    <form method="POST" action="course-edit.php?id=<?= $course_id ?>" enctype="multipart/form-data" id="courseForm">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            <!-- LEFT 2 COLS: MAIN CONTENT -->
            <div class="lg:col-span-2 space-y-4">
                
                <!-- BASIC INFO -->
                <div class="bg-white border border-slate-200 rounded-md p-4 space-y-3">
                    <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100">
                        <span class="w-6 text-center text-[#fe7c03]"><i class="fa-solid fa-file-lines text-sm"></i></span>
                        <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider">Basic Course Information</h3>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Course Title <span class="text-rose-500">*</span></label>
                            <input type="text" name="title" id="courseTitle" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03] text-slate-800" value="<?= htmlspecialchars($_POST['title'] ?? $course['title']) ?>" required>
                        </div>

                        <div>
                            <label class="font-bold text-slate-700 block mb-1">URL Slug</label>
                            <div class="flex items-center">
                                <span class="px-2 py-2 bg-slate-50 border border-r-0 border-slate-200 text-slate-400 text-xs rounded-l-md font-mono">/course-detail.php?slug=</span>
                                <input type="text" name="slug" id="courseSlug" class="flex-1 text-xs px-3 py-2 border border-slate-200 rounded-r-md font-mono focus:outline-none focus:border-[#fe7c03] text-slate-800" value="<?= htmlspecialchars($_POST['slug'] ?? $course['slug']) ?>">
                            </div>
                        </div>

                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Short Summary / Card Excerpt <span class="text-rose-500">*</span></label>
                            <textarea name="short_desc" rows="2" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03] text-slate-800" required><?= htmlspecialchars($_POST['short_desc'] ?? $course['short_desc']) ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- DETAILED OVERVIEW -->
                <div class="bg-white border border-slate-200 rounded-md p-4 space-y-3">
                    <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="w-6 text-center text-sky-600"><i class="fa-solid fa-paragraph text-sm"></i></span>
                            <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider">Detailed Course Overview</h3>
                        </div>
                        <span class="text-[10px] text-slate-400">Rich Text Editor</span>
                    </div>

                    <div>
                        <textarea name="full_desc" id="summernoteDesc" class="w-full"><?= htmlspecialchars($_POST['full_desc'] ?? $course['full_desc']) ?></textarea>
                    </div>
                </div>

                <!-- MODULES (WHAT WE PROVIDE) -->
                <div class="bg-white border border-slate-200 rounded-md p-4 space-y-3">
                    <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="w-6 text-center text-emerald-600"><i class="fa-solid fa-list-check text-sm"></i></span>
                            <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider">Curriculum Modules (What We Provide)</h3>
                        </div>
                        <span class="text-[10px] text-slate-400">One per line</span>
                    </div>

                    <div class="text-xs">
                        <textarea name="what_you_learn" rows="5" class="w-full font-mono text-xs px-3 py-2 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03] text-slate-800 leading-relaxed"><?= htmlspecialchars($_POST['what_you_learn'] ?? $course['what_you_learn']) ?></textarea>
                    </div>
                </div>

                <!-- PRACTICAL METHODOLOGY & CAREER -->
                <div class="bg-white border border-slate-200 rounded-md p-4 space-y-3">
                    <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100">
                        <span class="w-6 text-center text-purple-600"><i class="fa-solid fa-chalkboard-user text-sm"></i></span>
                        <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider">Practical Training &amp; Career Roles</h3>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div>
                            <label class="font-bold text-slate-700 block mb-1">How We Teach (Training Highlights - One per line):</label>
                            <textarea name="how_we_teach" rows="3" class="w-full font-mono text-xs px-3 py-2 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03] text-slate-800"><?= htmlspecialchars($_POST['how_we_teach'] ?? $course['how_we_teach']) ?></textarea>
                        </div>

                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Target Career Job Roles (Comma separated):</label>
                            <input type="text" name="career_roles" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03] text-slate-800" value="<?= htmlspecialchars($_POST['career_roles'] ?? $course['career_roles']) ?>">
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT 1 COL: SIDEBAR SETTINGS -->
            <div class="space-y-4">
                
                <!-- PUBLISH SETTINGS -->
                <div class="bg-white border border-slate-200 rounded-md p-4 space-y-3">
                    <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100">
                        <span class="w-6 text-center text-[#fe7c03]"><i class="fa-solid fa-sliders text-sm"></i></span>
                        <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider">Publish Actions</h3>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Status</label>
                            <select name="status" class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]">
                                <option value="active" <?= ($course['status'] === 'active') ? 'selected' : '' ?>>Active &bull; Visible Online</option>
                                <option value="inactive" <?= ($course['status'] === 'inactive') ? 'selected' : '' ?>>Inactive &bull; Draft</option>
                            </select>
                        </div>

                        <div class="pt-1">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_featured" value="1" id="featuredSwitch" <?= $course['is_featured'] ? 'checked' : '' ?> class="rounded border-slate-300 text-[#fe7c03] focus:ring-[#fe7c03]">
                                <span class="text-xs font-semibold text-slate-700">Feature on Homepage</span>
                            </label>
                        </div>

                        <div class="pt-3 border-t border-slate-100 space-y-2">
                            <button type="submit" class="w-full px-4 py-2 text-xs font-semibold text-white bg-[#fe7c03] hover:bg-[#ea6c00] rounded-md transition-colors inline-flex items-center justify-center gap-1">
                                <i class="fa-solid fa-floppy-disk text-[11px]"></i>
                                <span>Update Course</span>
                            </button>
                            <a href="courses.php" class="w-full px-3 py-1.5 text-xs text-slate-600 bg-white border border-slate-200 rounded-md hover:bg-slate-50 text-center block no-underline">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>

                <!-- CATEGORY & SPECS -->
                <div class="bg-white border border-slate-200 rounded-md p-4 space-y-3">
                    <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100">
                        <span class="w-6 text-center text-sky-600"><i class="fa-solid fa-tags text-sm"></i></span>
                        <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider">Course Specifications</h3>
                    </div>

                    <div class="space-y-2.5 text-xs">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="font-bold text-slate-700">Category <span class="text-rose-500">*</span></label>
                                <a href="categories.php" target="_blank" class="text-[11px] font-semibold text-[#fe7c03] hover:text-[#ea6c00] hover:underline flex items-center gap-1">
                                    <i class="fa-solid fa-plus text-[10px]"></i> Add / Manage
                                </a>
                            </div>
                            <select name="category_id" class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($course['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Duration</label>
                            <input type="text" name="duration" class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]" value="<?= htmlspecialchars($_POST['duration'] ?? $course['duration']) ?>">
                        </div>

                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Study Mode</label>
                            <input type="text" name="study_mode" class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]" placeholder="e.g. Offline or Online" value="<?= htmlspecialchars($_POST['study_mode'] ?? $course['study_mode']) ?>">
                        </div>

                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Course Modules</label>
                            <input type="text" name="modules" class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]" placeholder="e.g. 6 Modules" value="<?= htmlspecialchars($_POST['modules'] ?? ($course['modules'] ?? '6 Modules')) ?>">
                        </div>

                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Course Fee</label>
                            <input type="text" name="fee" class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]" placeholder="e.g. ₹45,000 or ₹35,000 / Installment" value="<?= htmlspecialchars($_POST['fee'] ?? $course['fee'] ?? '') ?>">
                        </div>

                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Eligibility Criteria</label>
                            <input type="text" name="eligibility" class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]" value="<?= htmlspecialchars($_POST['eligibility'] ?? $course['eligibility']) ?>">
                        </div>

                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Certification Title</label>
                            <input type="text" name="certification" class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-md focus:outline-none focus:border-[#fe7c03]" value="<?= htmlspecialchars($_POST['certification'] ?? $course['certification']) ?>">
                        </div>
                    </div>
                </div>

                <!-- THUMBNAIL IMAGE -->
                <div class="bg-white border border-slate-200 rounded-md p-4 space-y-3">
                    <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100">
                        <span class="w-6 text-center text-amber-500"><i class="fa-solid fa-image text-sm"></i></span>
                        <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider">Course Thumbnail</h3>
                    </div>

                    <div class="space-y-2 text-xs text-center">
                        <div class="h-32 border border-slate-200 rounded-md bg-slate-50 flex items-center justify-center overflow-hidden" id="imagePreviewBox">
                            <?php if (!empty($course['image'])): ?>
                                <img src="../<?= htmlspecialchars($course['image']) ?>" alt="Preview" id="previewImg" class="w-full h-full object-cover">
                                <div class="text-slate-400 text-xs" id="previewPlaceholder" style="display: none;">
                                    <i class="fa-regular fa-image text-2xl mb-1 block"></i>
                                    <span>No image chosen</span>
                                </div>
                            <?php else: ?>
                                <img src="" alt="Preview" id="previewImg" class="w-full h-full object-cover" style="display: none;">
                                <div class="text-slate-400 text-xs" id="previewPlaceholder">
                                    <i class="fa-regular fa-image text-2xl mb-1 block"></i>
                                    <span>No image chosen</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <input type="file" name="course_image" id="courseImageInput" accept="image/*" class="w-full text-xs file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                        <span class="text-[10px] text-slate-400 block text-left">Recommended: 800x520px (JPG, PNG, WEBP)</span>
                    </div>
                </div>

            </div>

        </div>
    </form>

</div>

<!-- jQuery & Summernote Lite JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>

<script>
$(document).ready(function() {
    $('#summernoteDesc').summernote({
        placeholder: 'Write comprehensive program description, objectives, and benefits...',
        tabsize: 2,
        height: 220,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'clear']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link']],
            ['view', ['codeview']]
        ]
    });

    const imageInput = document.getElementById('courseImageInput');
    const previewImg = document.getElementById('previewImg');
    const previewPlaceholder = document.getElementById('previewPlaceholder');

    if (imageInput && previewImg) {
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                    if (previewPlaceholder) previewPlaceholder.style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
