<?php
/**
 * Master Sidebar Partial
 * Reusable dynamic sidebar with active state highlighting, low-stock badge, and mobile drawer
 */
$root = get_root_url();
$current_page = $current_page ?? '';

// Fetch dynamic badge counts from MySQL
$db = get_db();
$med_count = 0;
$low_stock_count = 0;
try {
    $med_count = (int)$db->query("SELECT COUNT(*) FROM medicines WHERE status = 'active'")->fetchColumn();
    $low_stock_count = (int)$db->query("
        SELECT COUNT(DISTINCT m.id)
        FROM medicines m
        LEFT JOIN medicine_batches b ON m.id = b.medicine_id AND b.status = 'active'
        WHERE m.status = 'active'
        GROUP BY m.id, m.reorder_level
        HAVING COALESCE(SUM(b.quantity), 0) <= m.reorder_level
    ")->rowCount();
} catch (Exception $e) {
    // Graceful fallback if tables are empty
}

$nav_items = [
    [
        'key' => 'dashboard',
        'label' => 'Dashboard',
        'url' => $root . 'dashboard.php',
        'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>'
    ],
    [
        'key' => 'medicines',
        'label' => 'Medicines',
        'url' => $root . 'medicines/index.php',
        'badge' => $med_count > 0 ? (string)$med_count : null,
        'badge_class' => 'bg-white/10 text-slate-300',
        'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"/><path d="m8.5 8.5 7 7"/></svg>'
    ],
    [
        'key' => 'inventory',
        'label' => 'Inventory',
        'url' => $root . 'inventory/index.php',
        'badge' => $low_stock_count > 0 ? (string)$low_stock_count : null,
        'badge_class' => 'bg-amber-500/20 text-amber-300 border border-amber-500/30',
        'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.97 12.92A2 2 0 0 0 2 14.63v3.24a2 2 0 0 0 .97 1.71l6 3.43a2 2 0 0 0 1.97 0l6-3.43a2 2 0 0 0 .97-1.71v-3.24a2 2 0 0 0-.97-1.71L12 9.5a2 2 0 0 0-1.97 0Z"/><path d="m12 16.5 6-3.43"/><path d="m12 16.5-6-3.43"/><path d="M12 16.5v7"/><path d="M12 9.5 6 6.07a2 2 0 0 1-.97-1.71V1.12a2 2 0 0 1 .97-1.71l6-3.43a2 2 0 0 1 1.97 0l6 3.43a2 2 0 0 1 .97 1.71v3.24a2 2 0 0 1-.97 1.71Z"/><path d="M7 3.5v5"/><path d="M17 3.5v5"/><path d="M12 2v7.5"/></svg>'
    ],
    [
        'key' => 'purchases',
        'label' => 'Purchases',
        'url' => $root . 'purchases/index.php',
        'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-5.28a2 2 0 0 0-.8-1.6L18 8h-4"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>'
    ],
    [
        'key' => 'billing',
        'label' => 'Billing / POS',
        'url' => $root . 'billing/index.php',
        'highlight' => true,
        'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6"/><path d="M16 12h-6"/><path d="M14 16h-4"/></svg>'
    ],
    [
        'key' => 'customers',
        'label' => 'Customers',
        'url' => $root . 'customers/index.php',
        'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'
    ],
    [
        'key' => 'suppliers',
        'label' => 'Suppliers',
        'url' => $root . 'suppliers/index.php',
        'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>'
    ],
    [
        'key' => 'reports',
        'label' => 'Reports',
        'url' => $root . 'reports/index.php',
        'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>'
    ]
];

$user = current_user();
?>
<!-- Sidebar Mobile Backdrop -->
<div id="sidebar-backdrop" onclick="toggleMobileSidebar(false)" class="fixed inset-0 bg-slate-900/60 z-30 hidden lg:hidden backdrop-blur-xs"></div>

<!-- Aside Navigation -->
<aside id="app-sidebar" class="transition-transform duration-200 z-30 fixed lg:static inset-y-0 left-0 -translate-x-full lg:translate-x-0 flex flex-col h-full bg-[var(--sidebar-bg)] border-r border-[var(--sidebar-border)] text-[var(--sidebar-text)] w-64 select-none">
    
    <!-- Branding Header -->
    <div class="h-16 flex items-center justify-between px-4 border-b border-[var(--sidebar-border)]">
        <a href="<?= $root ?>dashboard.php" class="flex items-center gap-3">
            <img src="<?= $root ?>assets/images/logo.svg" alt="Logo" class="w-9 h-9 rounded-lg shadow-sm" onerror="this.src='<?= $root ?>assets/images/favicon.svg'"/>
            <div class="flex flex-col">
                <span class="font-bold text-base text-white tracking-tight leading-tight"><?= e(get_setting('hospital_name', APP_NAME)) ?></span>
                <span class="text-[11px] text-teal-300 font-medium tracking-wide"><?= e(get_setting('tagline', APP_TAGLINE)) ?></span>
            </div>
        </a>
        <button type="button" onclick="toggleMobileSidebar(false)" class="lg:hidden text-slate-400 hover:text-white p-1">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
    </div>

    <!-- Navigation List -->
    <div class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 px-3 mb-2">Main Navigation</div>
        <?php foreach ($nav_items as $item): ?>
            <?php $is_active = ($item['key'] === $current_page); ?>
            <a href="<?= e($item['url']) ?>" 
               class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?= $is_active ? 'bg-[var(--sidebar-active)] text-white shadow-sm font-semibold' : 'text-slate-300 hover:bg-white/10 hover:text-white' ?>">
                <span class="<?= $is_active ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>">
                    <?= $item['icon'] ?>
                </span>
                <span class="truncate"><?= e($item['label']) ?></span>
                <?php if (!empty($item['badge'])): ?>
                    <span class="ml-auto text-xs px-2 py-0.5 rounded-full font-bold <?= $item['badge_class'] ?? 'bg-white/10 text-slate-300' ?>">
                        <?= e($item['badge']) ?>
                    </span>
                <?php elseif (!empty($item['highlight'])): ?>
                    <span class="ml-auto text-[10px] uppercase tracking-wider px-1.5 py-0.5 rounded font-extrabold bg-primary text-white">POS</span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- User Profile & Store Footer -->
    <div class="p-3 border-t border-[var(--sidebar-border)] bg-black/10">
        <div class="flex items-center gap-3 p-2 rounded-lg bg-white/5">
            <div class="w-9 h-9 rounded-full bg-teal-600 flex items-center justify-center font-bold text-white text-xs ring-2 ring-white/10">
                <?= strtoupper(substr($user['name'] ?? 'AD', 0, 2)) ?>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-xs font-semibold text-white truncate"><?= e($user['name'] ?? 'Staff User') ?></div>
                <div class="text-[11px] text-slate-400 truncate"><?= e($user['role_display'] ?? 'Staff') ?></div>
            </div>
            <a href="<?= $root ?>logout.php" title="Sign Out" class="text-slate-400 hover:text-rose-400 p-1 rounded transition-colors">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
            </a>
        </div>
    </div>
</aside>
