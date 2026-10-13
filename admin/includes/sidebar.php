<?php
if (!isset($admin_stats)) {
    $admin_stats = function_exists('getAdminStats') ? getAdminStats($pdo) : [];
}
$current_page = basename($_SERVER['PHP_SELF']);
$admin_name = htmlspecialchars($_SESSION['admin_username'] ?? 'Admin');
$admin_initial = strtoupper(substr($admin_name, 0, 1));
?>

<!-- Mobile Sidebar Backdrop -->
<div id="sidebarBackdrop" class="fixed inset-0 bg-slate-900/40 z-40 lg:hidden hidden transition-opacity"></div>

<!-- LIGHT CLEAN SIDEBAR -->
<aside id="adminSidebar" class="fixed top-0 left-0 bottom-0 w-64 bg-white text-slate-700 flex flex-col z-50 transition-transform duration-200 ease-in-out -translate-x-full lg:translate-x-0 border-r border-slate-200">
    
    <!-- Sidebar Brand -->
    <div class="h-16 flex items-center justify-between px-4 border-b border-slate-200 bg-white">
        <a href="index.php" class="flex items-center gap-2 no-underline text-inherit">
            <div class="w-8 h-8 rounded-md bg-[#fe7c03] flex items-center justify-center text-white font-bold text-sm">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div>
                <span class="text-sm font-bold text-[#0e1e2e] tracking-tight block leading-tight">
                    YourGoodGuide
                </span>
                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 block leading-tight">
                    Admin Panel
                </span>
            </div>
        </a>
        <button id="sidebarCloseBtn" class="lg:hidden text-slate-400 hover:text-slate-700 p-1 rounded hover:bg-slate-100">
            <i class="fa-solid fa-xmark text-sm"></i>
        </button>
    </div>

    <!-- Navigation Links -->
    <div class="flex-1 overflow-y-auto px-3 py-4 space-y-5 custom-scrollbar">
        
        <!-- Section: Overview -->
        <div>
            <p class="px-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Overview</p>
            <nav class="space-y-0.5">
                <a href="index.php" class="flex items-center justify-between px-2 py-2 rounded-md font-medium text-xs transition-colors no-underline <?= $current_page === 'index.php' ? 'bg-[#fe7c03] text-white font-semibold' : 'text-slate-700 hover:text-[#fe7c03] hover:bg-[#fff8f3]' ?>">
                    <div class="flex items-center gap-2">
                        <span class="w-4 text-center"><i class="fa-solid fa-gauge-high <?= $current_page === 'index.php' ? 'text-white' : 'text-slate-400' ?>"></i></span>
                        <span>Dashboard</span>
                    </div>
                    <span id="sidebarOverviewBadge" class="px-1.5 py-0.5 text-[10px] font-bold rounded <?= ($admin_stats['total_unread'] ?? 0) > 0 ? '' : 'hidden ' ?><?= $current_page === 'index.php' ? 'bg-white text-[#fe7c03]' : 'bg-[#fe7c03] text-white' ?>">
                        <?= $admin_stats['total_unread'] ?? 0 ?>
                    </span>
                </a>
            </nav>
        </div>

        <!-- Section: Management -->
        <div>
            <p class="px-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Management</p>
            <nav class="space-y-0.5">
                <!-- Courses -->
                <a href="courses.php" class="flex items-center justify-between px-2 py-2 rounded-md font-medium text-xs transition-colors no-underline <?= in_array($current_page, ['courses.php', 'course-add.php', 'course-edit.php']) ? 'bg-[#fe7c03] text-white font-semibold' : 'text-slate-700 hover:text-[#fe7c03] hover:bg-[#fff8f3]' ?>">
                    <div class="flex items-center gap-2">
                        <span class="w-4 text-center"><i class="fa-solid fa-book-open-reader <?= in_array($current_page, ['courses.php', 'course-add.php', 'course-edit.php']) ? 'text-white' : 'text-slate-400' ?>"></i></span>
                        <span>Courses</span>
                    </div>
                    <?php if (isset($admin_stats['total_courses'])): ?>
                        <span class="px-1.5 py-0.2 rounded text-[10px] font-semibold <?= in_array($current_page, ['courses.php', 'course-add.php', 'course-edit.php']) ? 'bg-white text-[#fe7c03]' : 'bg-slate-100 text-slate-600' ?>">
                            <?= $admin_stats['total_courses'] ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- Course Categories -->
                <a href="categories.php" class="flex items-center justify-between px-2 py-2 rounded-md font-medium text-xs transition-colors no-underline <?= in_array($current_page, ['categories.php']) ? 'bg-[#fe7c03] text-white font-semibold' : 'text-slate-700 hover:text-[#fe7c03] hover:bg-[#fff8f3]' ?>">
                    <div class="flex items-center gap-2">
                        <span class="w-4 text-center"><i class="fa-solid fa-layer-group <?= in_array($current_page, ['categories.php']) ? 'text-white' : 'text-slate-400' ?>"></i></span>
                        <span>Course Categories</span>
                    </div>
                    <?php if (isset($admin_stats['total_categories'])): ?>
                        <span class="px-1.5 py-0.2 rounded text-[10px] font-semibold <?= in_array($current_page, ['categories.php']) ? 'bg-white text-[#fe7c03]' : 'bg-slate-100 text-slate-600' ?>">
                            <?= $admin_stats['total_categories'] ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- Registered Students -->
                <a href="students.php" class="flex items-center justify-between px-2 py-2 rounded-md font-medium text-xs transition-colors no-underline <?= $current_page === 'students.php' ? 'bg-[#fe7c03] text-white font-semibold' : 'text-slate-700 hover:text-[#fe7c03] hover:bg-[#fff8f3]' ?>">
                    <div class="flex items-center gap-2">
                        <span class="w-4 text-center"><i class="fa-solid fa-users <?= $current_page === 'students.php' ? 'text-white' : 'text-slate-400' ?>"></i></span>
                        <span>Registered Students</span>
                    </div>
                    <span id="sidebarStudentsBadge" class="px-1.5 py-0.2 rounded text-[10px] font-semibold <?= isset($admin_stats['total_students']) && $admin_stats['total_students'] > 0 ? '' : 'hidden ' ?><?= $current_page === 'students.php' ? 'bg-white text-[#fe7c03]' : 'bg-slate-100 text-slate-600' ?>">
                        <?= $admin_stats['total_students'] ?? 0 ?>
                    </span>
                </a>

                <!-- Admissions -->
                <a href="admissions.php" class="flex items-center justify-between px-2 py-2 rounded-md font-medium text-xs transition-colors no-underline <?= in_array($current_page, ['admissions.php', 'admission-add.php']) ? 'bg-[#fe7c03] text-white font-semibold' : 'text-slate-700 hover:text-[#fe7c03] hover:bg-[#fff8f3]' ?>">
                    <div class="flex items-center gap-2">
                        <span class="w-4 text-center"><i class="fa-solid fa-id-card <?= in_array($current_page, ['admissions.php', 'admission-add.php']) ? 'text-white' : 'text-slate-400' ?>"></i></span>
                        <span>Admissions</span>
                    </div>
                    <span id="sidebarAdmissionsBadge" class="px-1.5 py-0.5 text-[10px] font-bold rounded <?= ($admin_stats['unread_admissions'] ?? 0) > 0 ? '' : 'hidden ' ?><?= in_array($current_page, ['admissions.php', 'admission-add.php']) ? 'bg-white text-[#fe7c03]' : 'bg-rose-500 text-white' ?>">
                        <?= $admin_stats['unread_admissions'] ?? 0 ?>
                    </span>
                </a>

                <!-- Fee Submissions -->
                <a href="fees.php" class="flex items-center justify-between px-2 py-2 rounded-md font-medium text-xs transition-colors no-underline <?= $current_page === 'fees.php' ? 'bg-[#fe7c03] text-white font-semibold' : 'text-slate-700 hover:text-[#fe7c03] hover:bg-[#fff8f3]' ?>">
                    <div class="flex items-center gap-2">
                        <span class="w-4 text-center"><i class="fa-solid fa-receipt <?= $current_page === 'fees.php' ? 'text-white' : 'text-slate-400' ?>"></i></span>
                        <span>Fee Submissions</span>
                    </div>
                    <span id="sidebarFeesBadge" class="px-1.5 py-0.5 text-[10px] font-bold rounded <?= ($admin_stats['unread_fees'] ?? 0) > 0 ? '' : 'hidden ' ?><?= $current_page === 'fees.php' ? 'bg-white text-[#fe7c03]' : 'bg-teal-600 text-white' ?>">
                        <?= $admin_stats['unread_fees'] ?? 0 ?>
                    </span>
                </a>

                <!-- Contact Inquiries -->
                <a href="contacts.php" class="flex items-center justify-between px-2 py-2 rounded-md font-medium text-xs transition-colors no-underline <?= $current_page === 'contacts.php' ? 'bg-[#fe7c03] text-white font-semibold' : 'text-slate-700 hover:text-[#fe7c03] hover:bg-[#fff8f3]' ?>">
                    <div class="flex items-center gap-2">
                        <span class="w-4 text-center"><i class="fa-solid fa-envelope <?= $current_page === 'contacts.php' ? 'text-white' : 'text-slate-400' ?>"></i></span>
                        <span>Inquiries</span>
                    </div>
                    <span id="sidebarInquiriesBadge" class="px-1.5 py-0.5 text-[10px] font-bold rounded <?= ($admin_stats['unread_inquiries'] ?? 0) > 0 ? '' : 'hidden ' ?><?= $current_page === 'contacts.php' ? 'bg-white text-[#fe7c03]' : 'bg-sky-600 text-white' ?>">
                        <?= $admin_stats['unread_inquiries'] ?? 0 ?>
                    </span>
                </a>

                <!-- Pages & Policies -->
                <a href="pages.php" class="flex items-center justify-between px-2 py-2 rounded-md font-medium text-xs transition-colors no-underline <?= in_array($current_page, ['pages.php', 'page-edit.php']) ? 'bg-[#fe7c03] text-white font-semibold' : 'text-slate-700 hover:text-[#fe7c03] hover:bg-[#fff8f3]' ?>">
                    <div class="flex items-center gap-2">
                        <span class="w-4 text-center"><i class="fa-solid fa-file-lines <?= in_array($current_page, ['pages.php', 'page-edit.php']) ? 'text-white' : 'text-slate-400' ?>"></i></span>
                        <span>Pages &amp; Policies</span>
                    </div>
                </a>
            </nav>
        </div>

        <!-- Section: Settings -->
        <div>
            <p class="px-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Settings</p>
            <nav class="space-y-0.5">
                <a href="payment-settings.php" class="flex items-center justify-between px-2 py-2 rounded-md font-medium text-xs transition-colors no-underline <?= $current_page === 'payment-settings.php' ? 'bg-[#fe7c03] text-white font-semibold' : 'text-slate-700 hover:text-[#fe7c03] hover:bg-[#fff8f3]' ?>">
                    <div class="flex items-center gap-2">
                        <span class="w-4 text-center"><i class="fa-solid fa-credit-card <?= $current_page === 'payment-settings.php' ? 'text-white' : 'text-slate-400' ?>"></i></span>
                        <span>Payment Gateway</span>
                    </div>
                </a>

                <a href="smtp-settings.php" class="flex items-center justify-between px-2 py-2 rounded-md font-medium text-xs transition-colors no-underline <?= $current_page === 'smtp-settings.php' ? 'bg-[#fe7c03] text-white font-semibold' : 'text-slate-700 hover:text-[#fe7c03] hover:bg-[#fff8f3]' ?>">
                    <div class="flex items-center gap-2">
                        <span class="w-4 text-center"><i class="fa-solid fa-paper-plane <?= $current_page === 'smtp-settings.php' ? 'text-white' : 'text-slate-400' ?>"></i></span>
                        <span>Email &amp; SMTP</span>
                    </div>
                </a>

                <a href="settings.php" class="flex items-center justify-between px-2 py-2 rounded-md font-medium text-xs transition-colors no-underline <?= $current_page === 'settings.php' ? 'bg-[#fe7c03] text-white font-semibold' : 'text-slate-700 hover:text-[#fe7c03] hover:bg-[#fff8f3]' ?>">
                    <div class="flex items-center gap-2">
                        <span class="w-4 text-center"><i class="fa-solid fa-sliders <?= $current_page === 'settings.php' ? 'text-white' : 'text-slate-400' ?>"></i></span>
                        <span>Site Settings</span>
                    </div>
                </a>
            </nav>
        </div>

        <!-- Live Website Link -->
        <div class="pt-2">
            <a href="../index.php" target="_blank" class="flex items-center justify-between px-2 py-2 rounded-md text-xs font-medium text-slate-700 bg-slate-50 border border-slate-200 hover:bg-[#fff8f3] hover:text-[#fe7c03] hover:border-orange-200 transition-colors no-underline">
                <div class="flex items-center gap-2">
                    <span class="w-4 text-center text-[#fe7c03]"><i class="fa-solid fa-globe"></i></span>
                    <span>Live Website</span>
                </div>
                <i class="fa-solid fa-arrow-up-right-from-square text-[10px] text-slate-400"></i>
            </a>
        </div>

    </div>

    <!-- Sidebar User Footer -->
    <div class="p-3 border-t border-slate-200 bg-slate-50">
        <div class="flex items-center justify-between gap-2 p-2 rounded-md bg-white border border-slate-200">
            <div class="flex items-center gap-2 min-w-0">
                <div class="w-7 h-7 rounded bg-[#fe7c03] text-white font-bold flex items-center justify-center text-xs shrink-0">
                    <?= $admin_initial ?>
                </div>
                <div class="truncate">
                    <p class="text-xs font-semibold text-[#0e1e2e] truncate leading-tight"><?= $admin_name ?></p>
                    <p class="text-[10px] text-slate-400 truncate leading-tight"><?= htmlspecialchars($_SESSION['admin_email'] ?? 'admin@finchskills.com') ?></p>
                </div>
            </div>
            <a href="logout.php" title="Logout" class="w-7 h-7 rounded flex items-center justify-center text-rose-500 hover:text-white hover:bg-rose-500 transition-colors shrink-0">
                <i class="fa-solid fa-right-from-bracket text-xs"></i>
            </a>
        </div>
    </div>

</aside>
