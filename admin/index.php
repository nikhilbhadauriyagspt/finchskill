<?php
$page_title = "Dashboard";
require_once __DIR__ . '/includes/header.php';

// Fetch Totals & Stats
$total_revenue = $pdo->query("SELECT COALESCE(SUM(payment_amount), 0) FROM students WHERE payment_status = 'Paid'")->fetchColumn();
$total_categories = $pdo->query("SELECT COUNT(*) FROM course_categories")->fetchColumn();

// Fetch Recent Admissions
$recent_admissions = $pdo->query("SELECT * FROM admissions ORDER BY id DESC LIMIT 5")->fetchAll();
// Fetch Recent Fees
$recent_fees = $pdo->query("SELECT * FROM fee_submissions ORDER BY id DESC LIMIT 5")->fetchAll();
// Fetch Recent Inquiries
$recent_inquiries = $pdo->query("SELECT * FROM contact_inquiries ORDER BY id DESC LIMIT 5")->fetchAll();
// Fetch Recent Candidates
$recent_students = $pdo->query("SELECT * FROM students ORDER BY id DESC LIMIT 5")->fetchAll();

// Prepare 6-Month Chart Data (Monthly registrations)
$months = [];
$student_counts = [];
$admission_counts = [];

for ($i = 5; $i >= 0; $i--) {
    $month_str = date('Y-m', strtotime("-$i months"));
    $month_name = date('M Y', strtotime("-$i months"));
    $months[] = $month_name;
    
    // Students in this month
    $stmt_s = $pdo->prepare("SELECT COUNT(*) FROM students WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt_s->execute([$month_str]);
    $student_counts[] = (int)$stmt_s->fetchColumn();

    // Admissions in this month
    $stmt_a = $pdo->prepare("SELECT COUNT(*) FROM admissions WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt_a->execute([$month_str]);
    $admission_counts[] = (int)$stmt_a->fetchColumn();
}

// Seat status breakdown for Doughnut
$confirmed_cnt = (int)($admin_stats['confirmed_students'] ?? 0);
$total_students_cnt = (int)($admin_stats['total_students'] ?? 0);
$pending_seats_cnt = max(0, $total_students_cnt - $confirmed_cnt);
$paid_cnt = (int)($admin_stats['paid_students'] ?? 0);
?>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- DASHBOARD CONTENT WRAPPER -->
<div class="space-y-6">

    <!-- TOP STATS CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Registered Students -->
        <div class="bg-white border border-slate-200 rounded-md p-4 transition-colors hover:border-slate-300">
            <div class="flex items-center justify-between mb-2.5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Students</span>
                <div class="w-8 h-8 rounded-md bg-orange-50 text-[#fe7c03] border border-orange-100 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between">
                <h3 class="text-2xl font-bold text-[#0e1e2e]"><?= $admin_stats['total_students'] ?? 0 ?></h3>
                <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-100">
                    <?= $confirmed_cnt ?> Confirmed
                </span>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-400">Paid: <?= $paid_cnt ?></span>
                <a href="students.php" class="text-[#fe7c03] hover:underline font-semibold text-[11px] flex items-center gap-1 no-underline">
                    <span>View all</span>
                    <i class="fa-solid fa-arrow-right text-[9px]"></i>
                </a>
            </div>
        </div>

        <!-- Admissions -->
        <div class="bg-white border border-slate-200 rounded-md p-4 transition-colors hover:border-slate-300">
            <div class="flex items-center justify-between mb-2.5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Admissions</span>
                <div class="w-8 h-8 rounded-md bg-sky-50 text-sky-600 border border-sky-100 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-id-card"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between">
                <h3 class="text-2xl font-bold text-[#0e1e2e]"><?= $admin_stats['total_admissions'] ?? 0 ?></h3>
                <?php if (($admin_stats['unread_admissions'] ?? 0) > 0): ?>
                    <span class="text-[11px] font-semibold text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-100">
                        <?= $admin_stats['unread_admissions'] ?> New
                    </span>
                <?php else: ?>
                    <span class="text-[11px] text-slate-400 font-medium">All reviewed</span>
                <?php endif; ?>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-400">Enrollment Forms</span>
                <a href="admissions.php" class="text-[#fe7c03] hover:underline font-semibold text-[11px] flex items-center gap-1 no-underline">
                    <span>Manage</span>
                    <i class="fa-solid fa-arrow-right text-[9px]"></i>
                </a>
            </div>
        </div>

        <!-- Revenue / Fees Collection -->
        <div class="bg-white border border-slate-200 rounded-md p-4 transition-colors hover:border-slate-300">
            <div class="flex items-center justify-between mb-2.5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Fees</span>
                <div class="w-8 h-8 rounded-md bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-receipt"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between">
                <h3 class="text-2xl font-bold text-[#0e1e2e]">₹<?= number_format($total_revenue, 0) ?></h3>
                <span class="text-[11px] font-semibold text-teal-600 bg-teal-50 px-1.5 py-0.5 rounded border border-teal-100">
                    <?= $admin_stats['total_fees'] ?? 0 ?> Submissions
                </span>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-400"><?= $admin_stats['unread_fees'] ?? 0 ?> Pending review</span>
                <a href="fees.php" class="text-[#fe7c03] hover:underline font-semibold text-[11px] flex items-center gap-1 no-underline">
                    <span>Check receipts</span>
                    <i class="fa-solid fa-arrow-right text-[9px]"></i>
                </a>
            </div>
        </div>

        <!-- Courses & Categories -->
        <div class="bg-white border border-slate-200 rounded-md p-4 transition-colors hover:border-slate-300">
            <div class="flex items-center justify-between mb-2.5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Active Courses</span>
                <div class="w-8 h-8 rounded-md bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-book-open-reader"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between">
                <h3 class="text-2xl font-bold text-[#0e1e2e]"><?= $admin_stats['total_courses'] ?? 0 ?></h3>
                <span class="text-[11px] font-semibold text-purple-600 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-100">
                    <?= $total_categories ?> Categories
                </span>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-400">Programs Available</span>
                <a href="courses.php" class="text-[#fe7c03] hover:underline font-semibold text-[11px] flex items-center gap-1 no-underline">
                    <span>Manage</span>
                    <i class="fa-solid fa-arrow-right text-[9px]"></i>
                </a>
            </div>
        </div>

    </div>

    <!-- CHARTS SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        
        <!-- Main Line/Bar Chart: Registration & Admission Trends (2 Columns) -->
        <div class="lg:col-span-2 bg-white border border-slate-200 rounded-md p-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div>
                    <h2 class="text-sm font-bold text-[#0e1e2e]">Registration &amp; Admission Trends</h2>
                    <p class="text-[11px] text-slate-400">Monthly student enrollments over the last 6 months</p>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    <span class="inline-flex items-center gap-1 text-slate-600">
                        <span class="w-2.5 h-2.5 rounded-sm bg-[#fe7c03]"></span> Students
                    </span>
                    <span class="inline-flex items-center gap-1 text-slate-600">
                        <span class="w-2.5 h-2.5 rounded-sm bg-sky-500"></span> Admissions
                    </span>
                </div>
            </div>
            <div class="relative h-64 w-full">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <!-- Doughnut Chart: Candidate Status Overview (1 Column) -->
        <div class="bg-white border border-slate-200 rounded-md p-4 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <h2 class="text-sm font-bold text-[#0e1e2e]">Candidate Seat Status</h2>
                    <span class="text-[11px] font-semibold text-slate-400">Total: <?= $total_students_cnt ?></span>
                </div>
                <div class="relative h-48 w-full flex items-center justify-center">
                    <canvas id="statusDoughnutChart"></canvas>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 grid grid-cols-3 gap-2 text-center text-xs">
                <div class="p-2 bg-slate-50 rounded border border-slate-100">
                    <p class="text-[10px] text-slate-400 font-medium">Confirmed</p>
                    <p class="text-xs font-bold text-emerald-600"><?= $confirmed_cnt ?></p>
                </div>
                <div class="p-2 bg-slate-50 rounded border border-slate-100">
                    <p class="text-[10px] text-slate-400 font-medium">Reserved</p>
                    <p class="text-xs font-bold text-[#fe7c03]"><?= $pending_seats_cnt ?></p>
                </div>
                <div class="p-2 bg-slate-50 rounded border border-slate-100">
                    <p class="text-[10px] text-slate-400 font-medium">Paid Fees</p>
                    <p class="text-xs font-bold text-sky-600"><?= $paid_cnt ?></p>
                </div>
            </div>
        </div>

    </div>

    <!-- RECENT REGISTERED CANDIDATES TABLE -->
    <div class="bg-white border border-slate-200 rounded-md">
        <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between bg-slate-50/70">
            <div class="flex items-center gap-2">
                <span class="w-6 text-center text-[#fe7c03]"><i class="fa-solid fa-users text-sm"></i></span>
                <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider">Recent Registered Students</h3>
            </div>
            <a href="students.php" class="text-xs font-semibold text-[#fe7c03] hover:underline flex items-center gap-1 no-underline">
                <span>View All Candidates</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="px-4 py-2">Candidate</th>
                        <th class="px-4 py-2">Student ID</th>
                        <th class="px-4 py-2">Phone</th>
                        <th class="px-4 py-2">Aadhaar</th>
                        <th class="px-4 py-2">Seat Status</th>
                        <th class="px-4 py-2">Payment</th>
                        <th class="px-4 py-2">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($recent_students)): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-slate-400 text-xs">
                                No candidate registrations yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_students as $st): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-2">
                                    <div class="font-semibold text-[#0e1e2e]"><?= htmlspecialchars($st['name']) ?></div>
                                    <div class="text-[11px] text-slate-400"><?= htmlspecialchars($st['email']) ?></div>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-0.5 text-[11px] font-mono font-medium rounded bg-slate-100 text-slate-700 border border-slate-200">
                                        <?= htmlspecialchars($st['student_id']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-slate-600 font-medium"><?= htmlspecialchars($st['phone']) ?></td>
                                <td class="px-4 py-2 text-slate-500 font-mono text-[11px]"><?= htmlspecialchars($st['aadhaar']) ?></td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded <?= $st['seat_status'] === 'Confirmed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' ?>">
                                        <?= htmlspecialchars($st['seat_status']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded <?= $st['payment_status'] === 'Paid' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 border border-slate-200' ?>">
                                        <?= htmlspecialchars($st['payment_status']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-slate-400 text-[11px]"><?= date('d M, Y', strtotime($st['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2-COLUMN GRID: RECENT ADMISSIONS & RECENT INQUIRIES -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        
        <!-- Recent Admissions -->
        <div class="bg-white border border-slate-200 rounded-md">
            <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between bg-slate-50/70">
                <div class="flex items-center gap-2">
                    <span class="w-6 text-center text-sky-600"><i class="fa-solid fa-id-card text-sm"></i></span>
                    <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider">Recent Admissions</h3>
                </div>
                <a href="admissions.php" class="text-xs font-semibold text-[#fe7c03] hover:underline flex items-center gap-1 no-underline">
                    <span>View All</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase text-[10px] tracking-wider">
                        <tr>
                            <th class="px-4 py-2">Candidate</th>
                            <th class="px-4 py-2">Course</th>
                            <th class="px-4 py-2">Mobile</th>
                            <th class="px-4 py-2">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($recent_admissions)): ?>
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-slate-400 text-xs">
                                    No admission submissions yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_admissions as $adm): ?>
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-4 py-2">
                                        <div class="font-semibold text-[#0e1e2e]"><?= htmlspecialchars($adm['name']) ?></div>
                                        <div class="text-[11px] text-slate-400"><?= htmlspecialchars($adm['email']) ?></div>
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="px-1.5 py-0.5 text-[10px] font-medium bg-slate-100 text-slate-700 rounded border border-slate-200">
                                            <?= htmlspecialchars($adm['course'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-slate-600"><?= htmlspecialchars($adm['mobile'] ?? 'N/A') ?></td>
                                    <td class="px-4 py-2 text-slate-400 text-[11px]"><?= date('d M, Y', strtotime($adm['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Contact Inquiries -->
        <div class="bg-white border border-slate-200 rounded-md">
            <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between bg-slate-50/70">
                <div class="flex items-center gap-2">
                    <span class="w-6 text-center text-fuchsia-600"><i class="fa-solid fa-envelope text-sm"></i></span>
                    <h3 class="text-xs font-bold text-[#0e1e2e] uppercase tracking-wider">Recent Inquiries</h3>
                </div>
                <a href="contacts.php" class="text-xs font-semibold text-[#fe7c03] hover:underline flex items-center gap-1 no-underline">
                    <span>View All</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase text-[10px] tracking-wider">
                        <tr>
                            <th class="px-4 py-2">Name</th>
                            <th class="px-4 py-2">Subject</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($recent_inquiries)): ?>
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-slate-400 text-xs">
                                    No contact inquiries yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_inquiries as $inq): ?>
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-4 py-2">
                                        <div class="font-semibold text-[#0e1e2e]"><?= htmlspecialchars($inq['name']) ?></div>
                                        <div class="text-[11px] text-slate-400"><?= htmlspecialchars($inq['email']) ?></div>
                                    </td>
                                    <td class="px-4 py-2 text-slate-600 truncate max-w-[140px]"><?= htmlspecialchars($inq['subject']) ?></td>
                                    <td class="px-4 py-2">
                                        <span class="px-1.5 py-0.5 text-[10px] font-semibold rounded <?= $inq['status'] === 'New' ? 'bg-rose-50 text-rose-600 border border-rose-200' : 'bg-emerald-50 text-emerald-600 border border-emerald-200' ?>">
                                            <?= htmlspecialchars($inq['status'] ?? 'New') ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-slate-400 text-[11px]"><?= date('d M, Y', strtotime($inq['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<!-- Chart Initialization Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Trend Line/Bar Chart
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($months) ?>,
                datasets: [
                    {
                        label: 'Students Registered',
                        data: <?= json_encode($student_counts) ?>,
                        borderColor: '#fe7c03',
                        backgroundColor: 'rgba(254, 124, 3, 0.08)',
                        borderWidth: 2,
                        tension: 0.35,
                        fill: true,
                        pointBackgroundColor: '#fe7c03',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    },
                    {
                        label: 'Admissions Submitted',
                        data: <?= json_encode($admission_counts) ?>,
                        borderColor: '#0284c7',
                        backgroundColor: 'rgba(2, 132, 199, 0.05)',
                        borderWidth: 2,
                        tension: 0.35,
                        fill: true,
                        pointBackgroundColor: '#0284c7',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#0e1e2e',
                        titleFont: { family: 'Plus Jakarta Sans', size: 12 },
                        bodyFont: { family: 'Plus Jakarta Sans', size: 11 },
                        padding: 10,
                        cornerRadius: 6,
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Plus Jakarta Sans', size: 11 }, color: '#64748b' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            precision: 0,
                            font: { family: 'Plus Jakarta Sans', size: 11 },
                            color: '#64748b'
                        }
                    }
                }
            }
        });
    }

    // 2. Candidate Status Doughnut Chart
    const statusCtx = document.getElementById('statusDoughnutChart');
    if (statusCtx) {
        const confirmed = <?= $confirmed_cnt ?>;
        const pending = <?= $pending_seats_cnt ?>;
        const hasData = (confirmed + pending) > 0;

        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Confirmed Seats', 'Reserved / Pending'],
                datasets: [{
                    data: hasData ? [confirmed, pending] : [1, 0],
                    backgroundColor: hasData ? ['#10b981', '#fe7c03'] : ['#e2e8f0', '#f1f5f9'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: hasData,
                        backgroundColor: '#0e1e2e',
                        titleFont: { family: 'Plus Jakarta Sans', size: 12 },
                        bodyFont: { family: 'Plus Jakarta Sans', size: 11 },
                        padding: 10,
                        cornerRadius: 6,
                    }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
