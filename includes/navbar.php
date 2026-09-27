<?php
/**
 * Master Navbar Partial
 * Top header with mobile hamburger, search shortcut, theme selector, quick status & logout
 */
$root = get_root_url();
$user = current_user();
?>
<header class="h-16 bg-surface border-b border-appborder px-4 md:px-6 flex items-center justify-between gap-4 select-none shrink-0 z-20">
    <div class="flex items-center gap-3">
        <!-- Mobile Sidebar Toggle -->
        <button type="button" onclick="toggleMobileSidebar(true)" class="lg:hidden text-slate-600 hover:text-slate-900 p-1 rounded-md">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
        </button>
        <!-- Store Name Display -->
        <div class="hidden sm:flex flex-col">
            <span class="text-xs font-semibold text-slate-900 leading-tight"><?= e(get_setting('store_name', HOSPITAL_STORE_NAME)) ?></span>
            <span class="text-[11px] text-appmuted">Active Counter • DL: <?= e(get_setting('drug_license', HOSPITAL_DL_NUMBER)) ?></span>
        </div>
    </div>

    <!-- Center Search Trigger -->
    <div class="flex-1 max-w-md hidden md:block">
        <div class="relative">
            <input type="text" 
                   readonly 
                   onclick="window.location.href='<?= $root ?>medicines/index.php'"
                   placeholder="Quick search medicines, batches, customers (Press '/' to focus)..." 
                   class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-10 py-2 text-slate-600 cursor-pointer hover:bg-slate-100 transition-colors">
            <svg class="w-4 h-4 absolute left-3 top-2.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <span class="absolute right-2.5 top-2 text-[10px] bg-white border border-slate-200 text-slate-500 px-1.5 py-0.5 rounded font-mono font-bold">/</span>
        </div>
    </div>

    <!-- Right Controls -->
    <div class="flex items-center gap-2 sm:gap-3">
        
        <!-- Theme Switcher Dropdown -->
        <div class="relative">
            <select id="theme-select" onchange="switchTheme(this.value)" class="text-xs border border-appborder bg-white text-slate-700 py-1.5 px-2.5 rounded-lg font-medium cursor-pointer shadow-2xs hover:bg-slate-50 focus:outline-none">
                <option value="default" <?= ($active_theme === 'default') ? 'selected' : '' ?>>Teal (Default)</option>
                <option value="emerald" <?= ($active_theme === 'emerald') ? 'selected' : '' ?>>Emerald</option>
                <option value="royal" <?= ($active_theme === 'royal') ? 'selected' : '' ?>>Royal Blue</option>
                <option value="warm" <?= ($active_theme === 'warm') ? 'selected' : '' ?>>Warm Amber</option>
            </select>
        </div>

        <!-- POS Quick Action -->
        <a href="<?= $root ?>billing/index.php" class="btn-primary text-xs py-1.5 px-3 shadow-xs hidden sm:inline-flex">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6"/><path d="M16 12h-6"/><path d="M14 16h-4"/></svg>
            <span>POS (F2)</span>
        </a>

        <!-- Sign Out -->
        <a href="<?= $root ?>logout.php" title="Sign out" class="text-slate-500 hover:text-rose-600 p-2 rounded-lg hover:bg-slate-100 transition-colors">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
        </a>
    </div>
</header>
