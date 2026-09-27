/**
 * MY HOSPITAL PRO — Reusable UI Components & Layout Engine
 * Designed to mirror future PHP partials (header.php, sidebar.php, modals.php)
 */

const Components = {
    // Icons SVG dictionary for zero-dependency reliability
    icons: {
        'layout-dashboard': '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>',
        'pill': '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"/><path d="m8.5 8.5 7 7"/></svg>',
        'boxes': '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.97 12.92A2 2 0 0 0 2 14.63v3.24a2 2 0 0 0 .97 1.71l6 3.43a2 2 0 0 0 1.97 0l6-3.43a2 2 0 0 0 .97-1.71v-3.24a2 2 0 0 0-.97-1.71L12 9.5a2 2 0 0 0-1.97 0Z"/><path d="m12 16.5 6-3.43"/><path d="m12 16.5-6-3.43"/><path d="M12 16.5v7"/><path d="M12 9.5 6 6.07a2 2 0 0 1-.97-1.71V1.12a2 2 0 0 1 .97-1.71l6-3.43a2 2 0 0 1 1.97 0l6 3.43a2 2 0 0 1 .97 1.71v3.24a2 2 0 0 1-.97 1.71Z"/><path d="M7 3.5v5"/><path d="M17 3.5v5"/><path d="M12 2v7.5"/></svg>',
        'truck': '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-5.28a2 2 0 0 0-.8-1.6L18 8h-4"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>',
        'receipt': '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6"/><path d="M16 12h-6"/><path d="M14 16h-4"/></svg>',
        'users': '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'building-2': '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>',
        'bar-chart-3': '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>',
        'search': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>',
        'bell': '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>',
        'settings': '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'log-out': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>',
        'menu': '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>',
        'x': '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>',
        'plus': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>',
        'printer': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>',
        'download': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>',
        'arrow-left': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>',
        'alert-triangle': '<svg class="w-5 h-5 text-warning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>',
        'check-circle': '<svg class="w-5 h-5 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'
    },

    getIcon(name) {
        return this.icons[name] || '';
    },

    // Render global sidebar
    renderSidebar(activeKey) {
        const root = getRootPrefix();
        const meds = MHP_DB.getMedicines();
        const lowStockCount = meds.filter(m => {
            const total = (m.batches || []).reduce((acc, b) => acc + (Number(b.quantity) || 0), 0);
            return total <= (Number(m.reorderLevel) || 20);
        }).length;

        const navHtml = APP_CONFIG.navItems.map(item => {
            const isActive = item.key === activeKey;
            const fullPath = root + item.path;
            const iconSvg = this.getIcon(item.icon);
            
            let badge = '';
            if (item.badgeKey === 'lowStockCount' && lowStockCount > 0) {
                badge = `<span class="ml-auto text-xs px-2 py-0.5 rounded-full font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">${lowStockCount}</span>`;
            } else if (item.badgeKey === 'medicinesCount') {
                badge = `<span class="ml-auto text-xs px-2 py-0.5 rounded-full font-medium text-slate-400 bg-white/5">${meds.length}</span>`;
            } else if (item.highlight) {
                badge = `<span class="ml-auto text-[10px] uppercase tracking-wider px-1.5 py-0.5 rounded font-extrabold bg-primary text-white">POS</span>`;
            }

            return `
                <a href="${fullPath}" 
                   class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors ${
                       isActive 
                           ? 'bg-[var(--sidebar-active)] text-white shadow-sm font-semibold' 
                           : 'text-slate-300 hover:bg-white/10 hover:text-white'
                   }">
                    <span class="${isActive ? 'text-white' : 'text-slate-400 group-hover:text-white'}">
                        ${iconSvg}
                    </span>
                    <span class="truncate">${item.label}</span>
                    ${badge}
                </a>
            `;
        }).join('');

        return `
            <!-- Sidebar Desktop & Drawer Overlay -->
            <div id="sidebar-backdrop" onclick="Components.toggleMobileSidebar(false)" class="fixed inset-0 bg-slate-900/60 z-30 hidden lg:hidden backdrop-blur-xs"></div>
            
            <div class="flex flex-col h-full bg-[var(--sidebar-bg)] border-r border-[var(--sidebar-border)] text-[var(--sidebar-text)] w-64 select-none">
                <!-- Branding Header -->
                <div class="h-16 flex items-center justify-between px-4 border-b border-[var(--sidebar-border)]">
                    <a href="${root}dashboard.html" class="flex items-center gap-3">
                        <img src="${root}${APP_CONFIG.logo}" alt="MHP Logo" class="w-9 h-9 rounded-lg shadow-sm" onerror="this.src='${root}assets/images/favicon.svg'"/>
                        <div class="flex flex-col">
                            <span class="font-bold text-base text-white tracking-tight leading-tight">${APP_CONFIG.name}</span>
                            <span class="text-[11px] text-teal-300 font-medium tracking-wide">${APP_CONFIG.tagline}</span>
                        </div>
                    </a>
                    <button type="button" onclick="Components.toggleMobileSidebar(false)" class="lg:hidden text-slate-400 hover:text-white p-1">
                        ${this.getIcon('x')}
                    </button>
                </div>

                <!-- Navigation List -->
                <div class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 px-3 mb-2">Main Navigation</div>
                    ${navHtml}
                </div>

                <!-- Hospital Store & User Profile Footer -->
                <div class="p-3 border-t border-[var(--sidebar-border)] bg-black/10">
                    <div class="p-2.5 rounded-lg bg-white/5 border border-white/10 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-primary flex items-center justify-center font-bold text-white text-sm shadow-inner">
                            AS
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-semibold text-white truncate">${APP_CONFIG.demoAuth.displayName}</div>
                            <div class="text-[11px] text-slate-400 truncate">${APP_CONFIG.demoAuth.role}</div>
                        </div>
                        <a href="${root}index.html" title="Sign out" class="text-slate-400 hover:text-rose-400 p-1 transition-colors">
                            ${this.getIcon('log-out')}
                        </a>
                    </div>
                </div>
            </div>
        `;
    },

    // Render global header
    renderHeader(pageTitle, breadcrumbs = []) {
        const root = getRootPrefix();
        const sales = MHP_DB.getSales();
        const todaySales = sales.reduce((sum, s) => sum + (Number(s.grandTotal) || 0), 0);

        let breadcrumbHtml = `<li class="font-bold text-slate-800 text-base md:text-lg">${pageTitle}</li>`;
        if (breadcrumbs && breadcrumbs.length) {
            breadcrumbHtml = breadcrumbs.map((b, i) => {
                const isLast = i === breadcrumbs.length - 1;
                return isLast 
                    ? `<li class="font-bold text-slate-900">${b.label}</li>`
                    : `<li><a href="${root}${b.path}" class="text-slate-500 hover:text-primary transition-colors">${b.label}</a><span class="mx-2 text-slate-400">/</span></li>`;
            }).join('');
        }

        return `
            <div class="h-16 px-4 md:px-6 bg-surface border-b border-appborder flex items-center justify-between gap-4 sticky top-0 z-20">
                <!-- Left: Hamburger toggle + Breadcrumbs -->
                <div class="flex items-center gap-3 min-w-0">
                    <button type="button" onclick="Components.toggleMobileSidebar(true)" class="lg:hidden p-2 text-slate-600 hover:bg-slate-100 rounded-lg">
                        ${this.getIcon('menu')}
                    </button>
                    <nav class="hidden sm:block">
                        <ol class="flex items-center text-sm">
                            ${breadcrumbHtml}
                        </ol>
                    </nav>
                </div>

                <!-- Center: Global Quick Search Button (Press '/') -->
                <div class="flex-1 max-w-md mx-2 hidden md:block">
                    <button type="button" onclick="Components.openGlobalSearch()" class="w-full flex items-center justify-between px-3.5 py-1.5 bg-slate-100/80 hover:bg-slate-100 text-slate-500 border border-slate-200 rounded-lg text-sm transition-all focus:outline-none focus:ring-2 focus:ring-primary/20">
                        <div class="flex items-center gap-2">
                            ${this.getIcon('search')}
                            <span>Search medicines, bills, customers...</span>
                        </div>
                        <kbd class="kbd-shortcut">/</kbd>
                    </button>
                </div>

                <!-- Right: Quick POS link + Notifications + Store Info -->
                <div class="flex items-center gap-2.5">
                    <!-- Quick Bill Action -->
                    <a href="${root}billing/index.html" class="hidden sm:inline-flex btn-primary text-xs py-1.5 px-3 shadow-sm">
                        ${this.getIcon('plus')}
                        <span>Quick Bill</span>
                    </a>

                    <!-- Notification dropdown button -->
                    <div class="relative">
                        <button type="button" id="notif-btn" onclick="Components.toggleNotifications()" class="relative p-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                            ${this.getIcon('bell')}
                            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-rose-500 rounded-full animate-pulse"></span>
                        </button>

                        <!-- Notification popup panel -->
                        <div id="notif-dropdown" class="hidden absolute right-0 mt-2 w-80 sm:w-96 bg-surface border border-appborder rounded-xl shadow-xl z-50 overflow-hidden text-sm">
                            <div class="p-3 bg-surface-alt border-b border-appborder flex items-center justify-between">
                                <span class="font-bold text-slate-800">Pharmacy Notifications</span>
                                <span class="text-xs bg-primary-light text-primary font-semibold px-2 py-0.5 rounded-full">4 New</span>
                            </div>
                            <div class="divide-y divide-appborder max-h-80 overflow-y-auto">
                                <a href="${root}inventory/low-stock.html" class="p-3 flex items-start gap-3 hover:bg-slate-50 transition-colors block">
                                    <span class="p-1.5 bg-amber-100 text-amber-700 rounded-md mt-0.5">${this.getIcon('alert-triangle')}</span>
                                    <div class="flex-1">
                                        <p class="font-medium text-slate-800 text-xs">Low Stock Alert</p>
                                        <p class="text-slate-500 text-xs mt-0.5">ORS Sachets & Glycomet 500mg are below safety reorder level.</p>
                                        <span class="text-[10px] text-slate-400 mt-1 block">15 mins ago</span>
                                    </div>
                                </a>
                                <a href="${root}inventory/expiry.html" class="p-3 flex items-start gap-3 hover:bg-slate-50 transition-colors block">
                                    <span class="p-1.5 bg-rose-100 text-rose-700 rounded-md mt-0.5">${this.getIcon('alert-triangle')}</span>
                                    <div class="flex-1">
                                        <p class="font-medium text-slate-800 text-xs">Expiring Batches (Under 30 Days)</p>
                                        <p class="text-slate-500 text-xs mt-0.5">Paracetamol Batch PCM25X (24 units) expires on 15 Oct 2026.</p>
                                        <span class="text-[10px] text-slate-400 mt-1 block">1 hour ago</span>
                                    </div>
                                </a>
                                <a href="${root}suppliers/index.html" class="p-3 flex items-start gap-3 hover:bg-slate-50 transition-colors block">
                                    <span class="p-1.5 bg-blue-100 text-blue-700 rounded-md mt-0.5">${this.getIcon('truck')}</span>
                                    <div class="flex-1">
                                        <p class="font-medium text-slate-800 text-xs">Supplier Payment Due</p>
                                        <p class="text-slate-500 text-xs mt-0.5">Apollo Dist. invoice due ₹14,200 due in 3 days.</p>
                                        <span class="text-[10px] text-slate-400 mt-1 block">Yesterday</span>
                                    </div>
                                </a>
                                <div class="p-3 flex items-start gap-3 bg-emerald-50/50">
                                    <span class="p-1.5 bg-emerald-100 text-emerald-700 rounded-md mt-0.5">${this.getIcon('check-circle')}</span>
                                    <div class="flex-1">
                                        <p class="font-medium text-emerald-900 text-xs">Daily Sales Milestone</p>
                                        <p class="text-emerald-700 text-xs mt-0.5">Today's billing counter crossed ₹40,000 threshold.</p>
                                        <span class="text-[10px] text-emerald-600 mt-1 block">Today</span>
                                    </div>
                                </div>
                            </div>
                            <div class="p-2 bg-surface-alt border-t border-appborder text-center">
                                <a href="${root}dashboard.html" class="text-xs font-semibold text-primary hover:underline">View Dashboard Activity</a>
                            </div>
                        </div>
                    </div>

                    <!-- Hospital Branch Tag -->
                    <div class="hidden xl:flex flex-col text-right border-l border-appborder pl-3">
                        <span class="text-xs font-bold text-slate-800">${APP_CONFIG.hospital.storeName}</span>
                        <span class="text-[11px] text-slate-500">DL: ${APP_CONFIG.hospital.dlNumber.split('/')[0]}</span>
                    </div>
                </div>
            </div>
        `;
    },

    // Mobile sidebar drawer toggler
    toggleMobileSidebar(show) {
        const sidebar = document.getElementById('app-sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        if (!sidebar) return;

        if (show) {
            sidebar.classList.remove('-translate-x-full');
            if (backdrop) backdrop.classList.remove('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
            if (backdrop) backdrop.classList.add('hidden');
        }
    },

    // Notification dropdown toggle
    toggleNotifications() {
        const dropdown = document.getElementById('notif-dropdown');
        if (dropdown) dropdown.classList.toggle('hidden');
    },

    // Global Search Modal
    openGlobalSearch() {
        let modal = document.getElementById('global-search-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'global-search-modal';
            modal.className = 'modal-backdrop';
            modal.innerHTML = `
                <div class="bg-surface border border-appborder rounded-xl shadow-2xl max-w-2xl w-full p-4 overflow-hidden" onclick="event.stopPropagation()">
                    <div class="relative flex items-center border-b border-appborder pb-3">
                        <span class="text-slate-400 mr-2">${this.getIcon('search')}</span>
                        <input id="global-search-input" 
                               type="text" 
                               placeholder="Type medicine name, barcode, customer, or invoice #..." 
                               class="w-full bg-transparent text-base text-slate-800 placeholder-slate-400 focus:outline-none"
                               oninput="Components.handleGlobalSearch(this.value)"/>
                        <button onclick="Components.closeGlobalSearch()" class="text-slate-400 hover:text-slate-600 p-1">
                            ${this.getIcon('x')}
                        </button>
                    </div>
                    <div id="global-search-results" class="py-3 max-h-96 overflow-y-auto divide-y divide-slate-100">
                        <div class="text-center py-8 text-sm text-slate-400">
                            Search medicines, batches, customers, suppliers or bills across the ERP system.
                        </div>
                    </div>
                    <div class="pt-2 border-t border-appborder flex items-center justify-between text-xs text-slate-400">
                        <span>Press <kbd class="kbd-shortcut">ESC</kbd> to exit</span>
                        <span>Results filter live as you type</span>
                    </div>
                </div>
            `;
            modal.onclick = () => Components.closeGlobalSearch();
            document.body.appendChild(modal);
        }
        modal.classList.remove('hidden');
        setTimeout(() => {
            const input = document.getElementById('global-search-input');
            if (input) {
                input.value = '';
                input.focus();
                Components.handleGlobalSearch('');
            }
        }, 50);
    },

    closeGlobalSearch() {
        const modal = document.getElementById('global-search-modal');
        if (modal) modal.classList.add('hidden');
    },

    handleGlobalSearch(q) {
        const resultsBox = document.getElementById('global-search-results');
        if (!resultsBox) return;

        const root = getRootPrefix();
        q = (q || '').trim().toLowerCase();
        if (!q) {
            resultsBox.innerHTML = `
                <div class="text-center py-6 text-sm text-slate-400">
                    Start typing to search inventory, sales, customers and suppliers...
                </div>
            `;
            return;
        }

        const meds = MHP_DB.getMedicines().filter(m => 
            m.name.toLowerCase().includes(q) || 
            (m.genericName && m.genericName.toLowerCase().includes(q)) ||
            (m.barcode && m.barcode.includes(q))
        ).slice(0, 4);

        const custs = MHP_DB.getCustomers().filter(c => 
            c.name.toLowerCase().includes(q) || 
            (c.mobile && c.mobile.includes(q))
        ).slice(0, 3);

        const sales = MHP_DB.getSales().filter(s => 
            s.invoiceNo.toLowerCase().includes(q) ||
            s.customerName.toLowerCase().includes(q)
        ).slice(0, 3);

        if (!meds.length && !custs.length && !sales.length) {
            resultsBox.innerHTML = `
                <div class="text-center py-6 text-sm text-slate-400">
                    No results found matching "<strong>${q}</strong>".
                </div>
            `;
            return;
        }

        let html = '';
        if (meds.length) {
            html += `<div class="text-[11px] font-bold uppercase text-slate-400 py-1">Medicines</div>`;
            meds.forEach(m => {
                html += `
                    <a href="${root}medicines/view.html?id=${m.id}" class="flex items-center justify-between py-2 px-2 hover:bg-slate-50 rounded-lg group">
                        <div class="flex items-center gap-3">
                            <span class="p-1.5 bg-teal-50 text-teal-700 rounded-md">${Components.getIcon('pill')}</span>
                            <div>
                                <p class="text-sm font-semibold text-slate-800 group-hover:text-primary">${m.name}</p>
                                <p class="text-xs text-slate-500">${m.genericName} • ${m.rackNumber}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-bold text-slate-900">${Utils.formatINR(m.sellingPrice)}</span>
                            <span class="block text-xs text-slate-400">MRP ${Utils.formatINR(m.mrp)}</span>
                        </div>
                    </a>
                `;
            });
        }

        if (sales.length) {
            html += `<div class="text-[11px] font-bold uppercase text-slate-400 py-1 mt-2">Invoices / Sales</div>`;
            sales.forEach(s => {
                html += `
                    <a href="${root}billing/invoice.html?id=${s.invoiceNo}" class="flex items-center justify-between py-2 px-2 hover:bg-slate-50 rounded-lg group">
                        <div class="flex items-center gap-3">
                            <span class="p-1.5 bg-blue-50 text-blue-700 rounded-md">${Components.getIcon('receipt')}</span>
                            <div>
                                <p class="text-sm font-semibold text-slate-800 group-hover:text-primary">${s.invoiceNo}</p>
                                <p class="text-xs text-slate-500">${s.customerName} • ${Utils.formatDate(s.date)}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-bold text-slate-900">${Utils.formatINR(s.grandTotal)}</span>
                            <span class="block text-xs text-emerald-600 font-medium">${s.paymentMethod}</span>
                        </div>
                    </a>
                `;
            });
        }

        if (custs.length) {
            html += `<div class="text-[11px] font-bold uppercase text-slate-400 py-1 mt-2">Customers</div>`;
            custs.forEach(c => {
                html += `
                    <a href="${root}customers/view.html?id=${c.id}" class="flex items-center justify-between py-2 px-2 hover:bg-slate-50 rounded-lg group">
                        <div class="flex items-center gap-3">
                            <span class="p-1.5 bg-indigo-50 text-indigo-700 rounded-md">${Components.getIcon('users')}</span>
                            <div>
                                <p class="text-sm font-semibold text-slate-800 group-hover:text-primary">${c.name}</p>
                                <p class="text-xs text-slate-500">${c.mobile} • ${c.city}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-semibold text-slate-700">${c.customerType}</span>
                        </div>
                    </a>
                `;
            });
        }

        resultsBox.innerHTML = html;
    },

    // Empty state generator
    renderEmptyState(iconName, title, message, actionBtnHtml = '') {
        return `
            <div class="app-card p-12 text-center flex flex-col items-center justify-center my-6">
                <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-4">
                    ${this.getIcon(iconName || 'boxes')}
                </div>
                <h3 class="text-base font-bold text-slate-800 mb-1">${title}</h3>
                <p class="text-sm text-slate-500 max-w-sm mb-5">${message}</p>
                ${actionBtnHtml}
            </div>
        `;
    },

    // Status Badges
    renderStatusBadge(status) {
        status = (status || '').toLowerCase();
        if (['active', 'paid', 'instock', 'in stock', 'good'].includes(status)) {
            return `<span class="badge badge-success"><span class="w-1.5 h-1.5 rounded-full bg-success"></span>Active</span>`;
        }
        if (['pending', 'low stock', 'lowstock', 'due'].includes(status)) {
            return `<span class="badge badge-warning"><span class="w-1.5 h-1.5 rounded-full bg-warning"></span>Pending</span>`;
        }
        if (['expired', 'out of stock', 'outofstock', 'overdue', 'damage'].includes(status)) {
            return `<span class="badge badge-danger"><span class="w-1.5 h-1.5 rounded-full bg-danger"></span>${status.toUpperCase()}</span>`;
        }
        return `<span class="badge badge-neutral">${status}</span>`;
    },

    // Toast Notification System
    showToast(message, type = 'success') {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            document.body.appendChild(container);
        }

        const icons = {
            success: `<span class="text-emerald-500">${Components.getIcon('check-circle')}</span>`,
            error: `<span class="text-rose-500">${Components.getIcon('alert-triangle')}</span>`,
            warning: `<span class="text-amber-500">${Components.getIcon('alert-triangle')}</span>`,
            info: `<span class="text-blue-500">${Components.getIcon('bell')}</span>`
        };

        const toast = document.createElement('div');
        toast.className = 'toast-item';
        toast.innerHTML = `
            ${icons[type] || icons.success}
            <div class="flex-1 min-w-0 text-xs md:text-sm font-medium text-slate-800 leading-snug">
                ${message}
            </div>
            <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600 p-0.5">
                ${Components.getIcon('x')}
            </button>
        `;

        container.appendChild(toast);
        setTimeout(() => {
            if (toast.parentElement) toast.remove();
        }, 3500);
    },

    // Modal Manager
    showModal({ title, bodyHtml, confirmText = 'Confirm', confirmClass = 'btn-primary', onConfirm = null }) {
        let existing = document.getElementById('app-dynamic-modal');
        if (existing) existing.remove();

        const modal = document.createElement('div');
        modal.id = 'app-dynamic-modal';
        modal.className = 'modal-backdrop';
        modal.innerHTML = `
            <div class="bg-surface border border-appborder rounded-xl shadow-2xl max-w-lg w-full p-6" onclick="event.stopPropagation()">
                <div class="flex items-center justify-between pb-3 border-b border-appborder mb-4">
                    <h3 class="text-base font-bold text-slate-900">${title}</h3>
                    <button onclick="Components.closeModal()" class="text-slate-400 hover:text-slate-600 p-1">
                        ${Components.getIcon('x')}
                    </button>
                </div>
                <div class="text-sm text-slate-600 mb-6">
                    ${bodyHtml}
                </div>
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-appborder">
                    <button type="button" onclick="Components.closeModal()" class="btn-secondary text-xs">Cancel</button>
                    <button type="button" id="modal-confirm-btn" class="${confirmClass} text-xs">${confirmText}</button>
                </div>
            </div>
        `;
        modal.onclick = () => Components.closeModal();
        document.body.appendChild(modal);

        document.getElementById('modal-confirm-btn').onclick = () => {
            if (onConfirm) onConfirm();
            Components.closeModal();
        };
    },

    closeModal() {
        const modal = document.getElementById('app-dynamic-modal');
        if (modal) modal.remove();
    },

    // Standard pagination renderer
    renderPagination(currentPage, totalPages, targetFunc = 'changePage') {
        if (totalPages <= 1) return '';
        let pagesHtml = '';
        for (let i = 1; i <= totalPages; i++) {
            pagesHtml += `
                <button type="button" 
                        onclick="${targetFunc}(${i})" 
                        class="w-8 h-8 rounded-md text-xs font-semibold ${
                            i === currentPage 
                                ? 'bg-primary text-white shadow-xs' 
                                : 'bg-surface text-slate-700 hover:bg-slate-100 border border-slate-200'
                        }">
                    ${i}
                </button>
            `;
        }
        return `
            <div class="flex items-center justify-between pt-4 border-t border-appborder text-xs text-slate-500">
                <span>Page ${currentPage} of ${totalPages}</span>
                <div class="flex items-center gap-1.5">
                    <button type="button" 
                            onclick="${targetFunc}(${Math.max(1, currentPage - 1)})" 
                            ${currentPage === 1 ? 'disabled class="opacity-50 cursor-not-allowed"' : ''}
                            class="px-2.5 py-1.5 rounded-md bg-surface border border-slate-200 hover:bg-slate-100 font-medium">
                        Previous
                    </button>
                    ${pagesHtml}
                    <button type="button" 
                            onclick="${targetFunc}(${Math.min(totalPages, currentPage + 1)})" 
                            ${currentPage === totalPages ? 'disabled class="opacity-50 cursor-not-allowed"' : ''}
                            class="px-2.5 py-1.5 rounded-md bg-surface border border-slate-200 hover:bg-slate-100 font-medium">
                        Next
                    </button>
                </div>
            </div>
        `;
    },

    // Master layout initializer
    initLayout(activeKey, pageTitle, breadcrumbs = []) {
        const sidebarContainer = document.getElementById('app-sidebar');
        if (sidebarContainer) {
            sidebarContainer.innerHTML = this.renderSidebar(activeKey);
        }

        const headerContainer = document.getElementById('app-header');
        if (headerContainer) {
            headerContainer.innerHTML = this.renderHeader(pageTitle, breadcrumbs);
        }

        // Close dropdowns on outside click
        document.addEventListener('click', (e) => {
            const notifBtn = document.getElementById('notif-btn');
            const notifDropdown = document.getElementById('notif-dropdown');
            if (notifDropdown && notifBtn && !notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
                notifDropdown.classList.add('hidden');
            }
        });

        // Global keyboard shortcut for search and escape
        document.addEventListener('keydown', (e) => {
            if (e.key === '/' && !['INPUT', 'TEXTAREA'].includes(e.target.tagName)) {
                e.preventDefault();
                Components.openGlobalSearch();
            }
            if (e.key === 'Escape') {
                Components.closeGlobalSearch();
                Components.closeModal();
            }
        });
    }
};

window.showToast = (msg, type) => Components.showToast(msg, type);
