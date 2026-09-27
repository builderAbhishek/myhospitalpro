<?php
/**
 * Master Footer Partial
 * Renders Flash Toast notifications, modal overlays, theme switching logic & closing tags
 */
$flash_messages = get_flash_messages();
$root = get_root_url();
?>
    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 pointer-events-none">
        <?php foreach ($flash_messages as $flash): ?>
            <?php 
                $bg = 'bg-slate-900 text-white';
                if ($flash['type'] === 'success') $bg = 'bg-emerald-600 text-white shadow-lg';
                if ($flash['type'] === 'error') $bg = 'bg-rose-600 text-white shadow-lg';
                if ($flash['type'] === 'warning') $bg = 'bg-amber-600 text-white shadow-lg';
                if ($flash['type'] === 'info') $bg = 'bg-blue-600 text-white shadow-lg';
            ?>
            <div class="pointer-events-auto px-4 py-3 rounded-xl shadow-xl <?= $bg ?> text-sm font-medium flex items-center gap-3 transition-all duration-300 animate-slide-in">
                <span><?= e($flash['message']) ?></span>
                <button type="button" onclick="this.parentElement.remove()" class="opacity-70 hover:opacity-100">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Core Scripts -->
    <script>
        function toggleMobileSidebar(open) {
            const sidebar = document.getElementById('app-sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            if (!sidebar || !backdrop) return;
            if (open) {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
            }
        }

        function switchTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            document.cookie = "mhp_theme=" + encodeURIComponent(theme) + "; path=/; max-age=" + (365*24*60*60);
        }

        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            if (!container) return;
            const toast = document.createElement('div');
            let bg = 'bg-slate-900 text-white';
            if (type === 'success') bg = 'bg-emerald-600 text-white shadow-lg';
            if (type === 'error') bg = 'bg-rose-600 text-white shadow-lg';
            if (type === 'warning') bg = 'bg-amber-600 text-white shadow-lg';
            if (type === 'info') bg = 'bg-blue-600 text-white shadow-lg';
            toast.className = `pointer-events-auto px-4 py-3 rounded-xl shadow-xl ${bg} text-sm font-medium flex items-center gap-3 transition-all duration-300`;
            toast.innerHTML = `<span>${message}</span><button type="button" onclick="this.parentElement.remove()" class="opacity-70 hover:opacity-100"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg></button>`;
            container.appendChild(toast);
            setTimeout(() => { if (toast.parentElement) toast.remove(); }, 4000);
        }

        // Global hotkey: '/' to search, 'F2' for POS
        document.addEventListener('keydown', (e) => {
            if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
                e.preventDefault();
                const searchInput = document.querySelector('input[name="search"], #pos-search, input[placeholder*="search"]');
                if (searchInput) searchInput.focus();
            }
            if (e.key === 'F2') {
                e.preventDefault();
                window.location.href = '<?= $root ?>billing/index.php';
            }
        });

        // Auto-dismiss initial toasts
        setTimeout(() => {
            const toasts = document.querySelectorAll('#toast-container > div');
            toasts.forEach(t => t.remove());
        }, 4500);
    </script>
</body>
</html>
