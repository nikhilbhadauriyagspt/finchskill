    </main>
    
    <!-- Clean Minimal Footer -->
    <footer class="border-t border-slate-200 bg-white py-3 px-4 sm:px-6 text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <span class="font-bold text-[#0e1e2e]">YourGoodGuide</span>
            <span class="text-slate-300">/</span>
            <span>Admin Console &copy; <?= date('Y') ?></span>
        </div>
        <div class="flex items-center gap-3 text-[11px] text-slate-400">
            <span class="inline-flex items-center gap-1 text-emerald-600 font-medium">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Online
            </span>
            <span>v2.5</span>
        </div>
    </footer>
</div>

<!-- Toast Notification Container -->
<div id="adminLiveToastContainer" class="fixed bottom-5 right-5 z-[9999] flex flex-col gap-2 max-w-sm w-full pointer-events-none px-3 sm:px-0"></div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- UI Interaction & Real-Time Live Notification Scripts -->
<script>
    // Sidebar Mobile Toggle
    const sidebarOpenBtn = document.getElementById('sidebarOpenBtn');
    const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
    const adminSidebar = document.getElementById('adminSidebar');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');

    function openSidebar() {
        if (adminSidebar && sidebarBackdrop) {
            adminSidebar.classList.remove('-translate-x-full');
            sidebarBackdrop.classList.remove('hidden');
            document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
        }
    }

    function closeSidebar() {
        if (adminSidebar && sidebarBackdrop) {
            adminSidebar.classList.add('-translate-x-full');
            sidebarBackdrop.classList.add('hidden');
            document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
        }
    }

    if (sidebarOpenBtn) sidebarOpenBtn.addEventListener('click', openSidebar);
    if (sidebarCloseBtn) sidebarCloseBtn.addEventListener('click', closeSidebar);
    if (sidebarBackdrop) sidebarBackdrop.addEventListener('click', closeSidebar);

    // Profile Dropdown Toggle
    const profileBtn = document.getElementById('profileDropdownBtn');
    const profileMenu = document.getElementById('profileDropdownMenu');
    if (profileBtn && profileMenu) {
        profileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            profileMenu.classList.toggle('hidden');
            if (notifMenu) notifMenu.classList.add('hidden');
        });
    }

    // Notification Dropdown Toggle
    const notifBtn = document.getElementById('notificationDropdownBtn');
    const notifMenu = document.getElementById('notificationDropdownMenu');
    if (notifBtn && notifMenu) {
        notifBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            notifMenu.classList.toggle('hidden');
            if (profileMenu) profileMenu.classList.add('hidden');
        });
    }

    // Close Dropdowns on Outside Click
    document.addEventListener('click', (e) => {
        if (profileMenu && !profileMenu.contains(e.target) && !profileBtn.contains(e.target)) {
            profileMenu.classList.add('hidden');
        }
        if (notifMenu && !notifMenu.contains(e.target) && !notifBtn.contains(e.target)) {
            notifMenu.classList.add('hidden');
        }
    });

    // Close on Escape Key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeSidebar();
            if (profileMenu) profileMenu.classList.add('hidden');
            if (notifMenu) notifMenu.classList.add('hidden');
        }
    });

    // ==========================================
    // REAL-TIME NOTIFICATION SYSTEM (POLLING)
    // ==========================================
    let lastAdmId = null;
    let lastFeeId = null;
    let lastInqId = null;
    let lastStdId = null;
    let isFirstPoll = true;
    const baseDocumentTitle = document.title.replace(/^\(\d+\)\s*/, '');

    // Web Audio API Gentle Chime
    function playNotificationChime() {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            
            // Oscillator 1: High tone
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
            osc1.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.15); // A5
            gain1.gain.setValueAtTime(0.2, ctx.currentTime);
            gain1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.start(ctx.currentTime);
            osc1.stop(ctx.currentTime + 0.35);

            // Oscillator 2: Harmonics
            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(880, ctx.currentTime + 0.1);
            osc2.frequency.exponentialRampToValueAtTime(1174.66, ctx.currentTime + 0.3); // D6
            gain2.gain.setValueAtTime(0.15, ctx.currentTime + 0.1);
            gain2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.45);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);
            osc2.start(ctx.currentTime + 0.1);
            osc2.stop(ctx.currentTime + 0.45);
        } catch (err) {
            // Audio context not allowed until user gesture or not supported
        }
    }

    // Show Floating Toast Notification
    function showLiveToast(alert) {
        const container = document.getElementById('adminLiveToastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = 'pointer-events-auto flex items-start gap-3 p-3.5 bg-white border border-slate-200 rounded-lg shadow-xl transform transition-all duration-300 translate-y-3 opacity-0';
        toast.style.borderLeft = `4px solid ${alert.color || '#fe7c03'}`;
        
        toast.innerHTML = `
            <div class="w-8 h-8 rounded-md flex items-center justify-center shrink-0" style="background-color: ${alert.bg || '#fff8f3'}; color: ${alert.color || '#fe7c03'};">
                <i class="fa-solid ${alert.icon || 'fa-bell'} text-sm"></i>
            </div>
            <div class="flex-1 min-w-0 pr-1">
                <div class="flex items-center justify-between gap-1 mb-0.5">
                    <p class="text-xs font-bold text-slate-800 leading-tight truncate">${alert.title}</p>
                    <span class="text-[10px] text-slate-400 shrink-0">${alert.time || 'Just now'}</span>
                </div>
                <p class="text-xs text-slate-600 leading-snug line-clamp-2">${alert.message}</p>
                ${alert.url ? `
                    <a href="${alert.url}" class="inline-flex items-center gap-1 text-[11px] font-semibold mt-1.5 no-underline hover:underline" style="color: ${alert.color || '#fe7c03'};">
                        <span>View Details</span>
                        <i class="fa-solid fa-arrow-right text-[9px]"></i>
                    </a>
                ` : ''}
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-600 p-1 shrink-0 -mr-1 -mt-1" onclick="this.closest('div.pointer-events-auto').remove()">
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
        `;

        container.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            toast.classList.remove('translate-y-3', 'opacity-0');
        });

        // Auto remove after 7 seconds
        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-y-2');
            setTimeout(() => toast.remove(), 300);
        }, 7000);
    }

    // Helper to update badge elements
    function updateBadge(elementId, count) {
        const el = document.getElementById(elementId);
        if (!el) return;
        if (count > 0) {
            el.textContent = count;
            el.classList.remove('hidden');
        } else {
            el.classList.add('hidden');
        }
    }

    // Check Notifications from Server
    async function checkLiveNotifications() {
        try {
            let url = 'api-notifications.php';
            if (lastAdmId !== null) {
                url += `?last_adm_id=${lastAdmId}&last_fee_id=${lastFeeId}&last_inq_id=${lastInqId}&last_std_id=${lastStdId}`;
            }

            const response = await fetch(url);
            if (!response.ok) return;
            const data = await response.json();

            if (!data.success) return;

            // Initialize IDs on first run
            if (isFirstPoll) {
                lastAdmId = data.max_ids.adm;
                lastFeeId = data.max_ids.fee;
                lastInqId = data.max_ids.inq;
                lastStdId = data.max_ids.std;
                isFirstPoll = false;
            } else {
                // If new alerts exist
                if (data.has_new && data.new_alerts && data.new_alerts.length > 0) {
                    playNotificationChime();
                    data.new_alerts.forEach(alert => {
                        showLiveToast(alert);
                    });
                }
                lastAdmId = Math.max(lastAdmId, data.max_ids.adm);
                lastFeeId = Math.max(lastFeeId, data.max_ids.fee);
                lastInqId = Math.max(lastInqId, data.max_ids.inq);
                lastStdId = Math.max(lastStdId, data.max_ids.std);
            }

            // Update Header Badges
            const totalUnread = data.counts.total_unread || 0;
            const bellDot = document.getElementById('headerBellDot');
            if (bellDot) {
                if (totalUnread > 0) bellDot.classList.remove('hidden');
                else bellDot.classList.add('hidden');
            }

            const headerTotal = document.getElementById('headerNotifBadgeTotal');
            if (headerTotal) {
                if (totalUnread > 0) {
                    headerTotal.textContent = `${totalUnread} New`;
                    headerTotal.classList.remove('hidden');
                } else {
                    headerTotal.classList.add('hidden');
                }
            }

            updateBadge('headerAdmBadge', data.counts.admissions || 0);
            updateBadge('headerFeeBadge', data.counts.fees || 0);
            updateBadge('headerInqBadge', data.counts.inquiries || 0);

            // Update Sidebar Badges
            updateBadge('sidebarOverviewBadge', totalUnread);
            updateBadge('sidebarAdmissionsBadge', data.counts.admissions || 0);
            updateBadge('sidebarFeesBadge', data.counts.fees || 0);
            updateBadge('sidebarInquiriesBadge', data.counts.inquiries || 0);
            updateBadge('sidebarStudentsBadge', data.counts.expiring_seats || 0);

            // Update Browser Tab Title
            if (totalUnread > 0) {
                document.title = `(${totalUnread}) ${baseDocumentTitle}`;
            } else {
                document.title = baseDocumentTitle;
            }

            // Render Recent Alerts inside dropdown
            if (data.recent_items && data.recent_items.length > 0) {
                const container = document.getElementById('headerRecentAlertsContainer');
                const list = document.getElementById('headerRecentAlertsList');
                if (container && list) {
                    container.classList.remove('hidden');
                    list.innerHTML = data.recent_items.map(item => `
                        <a href="${item.url}" class="flex items-center gap-2 p-1.5 rounded hover:bg-slate-50 transition-colors no-underline text-inherit">
                            <span class="w-6 text-center text-xs" style="color: ${item.color};"><i class="fa-solid ${item.icon}"></i></span>
                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] font-semibold text-slate-800 leading-tight truncate">${item.title}</p>
                                <p class="text-[9px] text-slate-400 truncate">${item.desc}</p>
                            </div>
                            <span class="text-[9px] text-slate-400 shrink-0">${item.time}</span>
                        </a>
                    `).join('');
                }
            }
        } catch (err) {
            // Silently fail network error during poll
        }
    }

    // Start Real-Time Live Polling every 5 seconds
    checkLiveNotifications();
    setInterval(checkLiveNotifications, 5000);
</script>
</body>
</html>
