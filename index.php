<?php
/**
 * Root Entry Point & Login Controller
 */
require_once __DIR__ . '/includes/app.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $db = get_db();
        $stmt = $db->prepare("SELECT u.*, r.name as role_name, r.display_name as role_display 
                              FROM users u 
                              JOIN roles r ON u.role_id = r.id 
                              WHERE (u.username = ? OR u.email = ?) AND u.status = 'active' 
                              LIMIT 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Valid credentials
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role_name'];

            // Update last login
            $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

            // Audit log
            record_audit_log($db, 'LOGIN', 'auth', $user['id'], null, ['status' => 'success']);

            set_flash('success', "Welcome back, " . $user['name'] . "!");
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password. Please try again.";
            record_audit_log($db, 'LOGIN_FAILED', 'auth', null, null, ['username' => $username]);
        }
    }
}

$page_title = "Login";
$active_theme = get_setting('theme', 'default');
if (isset($_COOKIE['mhp_theme']) && in_array($_COOKIE['mhp_theme'], ['default', 'emerald', 'royal', 'warm'])) {
    $active_theme = $_COOKIE['mhp_theme'];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= e($active_theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= e(get_setting('hospital_name', APP_NAME)) ?> Pharmacy ERP</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/themes.css">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="bg-appbg text-apptext min-h-screen flex flex-col justify-between antialiased selection:bg-primary-light selection:text-primary">

    <!-- Top Minimal Bar -->
    <header class="py-4 px-6 border-b border-appborder bg-surface flex items-center justify-between">
        <div class="flex items-center gap-3">
            <img src="assets/images/logo.svg" alt="Logo" class="w-9 h-9 rounded-lg shadow-xs" onerror="this.src='assets/images/favicon.svg'">
            <div>
                <span class="font-bold text-lg text-slate-900 tracking-tight" id="brand-name"><?= e(get_setting('hospital_name', APP_NAME)) ?></span>
                <span class="hidden sm:inline-block ml-2 text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full font-semibold border border-slate-200">Pharmacy ERP</span>
            </div>
        </div>
        <div class="flex items-center gap-3 text-xs text-appmuted">
            <span class="hidden md:inline">Support: <?= e(get_setting('phone', HOSPITAL_PHONE)) ?></span>
            <span class="hidden md:inline">•</span>
            <span class="font-medium text-slate-700">v<?= e(APP_VERSION) ?></span>
        </div>
    </header>

    <!-- Main Container: Clean Split Auth Card -->
    <main class="flex-1 flex items-center justify-center p-4 md:p-8">
        <div class="max-w-4xl w-full grid grid-cols-1 lg:grid-cols-12 bg-surface border border-appborder rounded-2xl shadow-xl overflow-hidden">
            
            <!-- Left Column: Form -->
            <div class="lg:col-span-7 p-6 sm:p-10 flex flex-col justify-between">
                <div>
                    <div class="mb-8">
                        <span class="text-xs font-bold uppercase tracking-wider text-primary bg-primary-light px-2.5 py-1 rounded-full">Secure Staff Portal</span>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-3 tracking-tight">Sign in to your pharmacy</h1>
                        <p class="text-sm text-slate-500 mt-1">Manage medicines, sales counters, vendor invoices and stock.</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="mb-5 p-3.5 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium rounded-lg flex items-center gap-2">
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <span><?= e($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="index.php" class="space-y-4">
                        <?= csrf_field() ?>
                        <div>
                            <label for="username" class="form-label text-xs font-semibold text-slate-700 mb-1 block">Username or Staff Email</label>
                            <input type="text" name="username" id="username" required value="<?= e($_POST['username'] ?? 'admin') ?>" 
                                   class="form-input text-sm w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-600" placeholder="e.g. admin or staff@hospital.com">
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="password" class="form-label text-xs font-semibold text-slate-700 block">Password</label>
                                <span class="text-xs text-primary font-medium">Default: admin123</span>
                            </div>
                            <input type="password" name="password" id="password" required value="admin123" 
                                   class="form-input text-sm w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-600" placeholder="••••••••">
                        </div>

                        <div class="flex items-center justify-between text-xs pt-1">
                            <label class="flex items-center gap-2 text-slate-600 cursor-pointer">
                                <input type="checkbox" name="remember" class="rounded text-primary focus:ring-primary border-slate-300" checked>
                                <span>Remember this workstation</span>
                            </label>
                            <span class="text-slate-400">MySQL Authenticated</span>
                        </div>

                        <button type="submit" class="btn-primary w-full py-2.5 text-sm font-semibold rounded-lg shadow-sm flex items-center justify-center gap-2 mt-4 bg-teal-600 hover:bg-teal-700 text-white">
                            <span>Sign In to Terminal</span>
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                        </button>
                    </form>
                </div>

                <!-- Demo helper -->
                <div class="mt-8 pt-5 border-t border-appborder text-xs text-slate-500">
                    <div class="font-semibold text-slate-700 mb-1 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                        Default Pre-Configured Credentials
                    </div>
                    <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200 font-mono text-[11px] text-slate-600 flex justify-between items-center">
                        <span>Username: <strong>admin</strong> | Password: <strong>admin123</strong></span>
                        <span class="text-emerald-700 font-bold uppercase text-[10px]">Super Admin</span>
                    </div>
                </div>
            </div>

            <!-- Right Column: Visual Value Prop & Hospital Brand -->
            <div class="hidden lg:flex lg:col-span-5 bg-gradient-to-br from-slate-900 to-slate-800 text-white p-10 flex-col justify-between relative overflow-hidden">
                <div class="absolute -right-16 -top-16 w-64 h-64 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-teal-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div>
                    <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md px-3 py-1 rounded-full text-xs font-medium text-teal-300 border border-white/10">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Hospital Pharmacy Module
                    </div>
                    <h2 class="text-2xl font-bold text-white mt-6 tracking-tight leading-snug">
                        <?= e(get_setting('hospital_name', APP_NAME)) ?><br/>
                        <span class="text-teal-300 font-medium text-lg">Hospital Pharmacy ERP</span>
                    </h2>
                    <p class="text-slate-400 text-xs mt-3 leading-relaxed">
                        Complete retail and clinical dispensing suite built for Indian medical stores, hospital pharmacies, and healthcare centers.
                    </p>

                    <div class="mt-8 space-y-3">
                        <div class="flex items-start gap-3 text-xs text-slate-300">
                            <svg class="w-4 h-4 text-teal-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Rapid POS Counter billing with First-Expiry-First-Out (FEFO) logic</span>
                        </div>
                        <div class="flex items-start gap-3 text-xs text-slate-300">
                            <svg class="w-4 h-4 text-teal-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Real-time stock valuation and multi-batch inventory control</span>
                        </div>
                        <div class="flex items-start gap-3 text-xs text-slate-300">
                            <svg class="w-4 h-4 text-teal-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Low stock & 30/60/90-day expiry alert thresholds</span>
                        </div>
                        <div class="flex items-start gap-3 text-xs text-slate-300">
                            <svg class="w-4 h-4 text-teal-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Indian GST compliant tax invoices with HSN breakdown</span>
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-white/10 text-xs text-slate-400 flex items-center justify-between">
                    <span>GSTIN: <?= e(get_setting('gstin', HOSPITAL_GSTIN)) ?></span>
                    <span>DL: <?= e(get_setting('drug_license', HOSPITAL_DL_NUMBER)) ?></span>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-4 px-6 border-t border-appborder bg-surface text-center text-xs text-appmuted">
        &copy; <?= date('Y') ?> <?= e(get_setting('hospital_name', APP_NAME)) ?>. All rights reserved. • Pharmacy ERP System
    </footer>

</body>
</html>
