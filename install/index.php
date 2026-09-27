<?php
session_start();

$step = (int)($_GET['step'] ?? 1);
$error = '';
$success = '';

// Check prerequisites
$php_version = phpversion();
$php_ok = version_compare($php_version, '8.0.0', '>=');
$pdo_ok = extension_loaded('pdo');
$pdo_mysql_ok = extension_loaded('pdo_mysql');
$mbstring_ok = extension_loaded('mbstring');
$openssl_ok = extension_loaded('openssl');
$config_writable = is_writable(__DIR__ . '/../config.php') || is_writable(__DIR__ . '/..');

$all_reqs_met = $php_ok && $pdo_ok && $pdo_mysql_ok && $mbstring_ok && $openssl_ok;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'test_and_import') {
        $db_host = trim($_POST['db_host'] ?? 'localhost');
        $db_port = (int)($_POST['db_port'] ?? 3306);
        $db_name = trim($_POST['db_name'] ?? 'mhp_database');
        $db_user = trim($_POST['db_user'] ?? 'root');
        $db_pass = $_POST['db_pass'] ?? '';

        try {
            // Test connection to MySQL server
            $pdo = new PDO("mysql:host={$db_host};port={$db_port};charset=utf8mb4", $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$db_name}`");

            // Import database/mhp_database.sql
            $sql_file = __DIR__ . '/../database/mhp_database.sql';
            if (!file_exists($sql_file)) {
                throw new Exception("Database schema file not found at: database/mhp_database.sql");
            }

            $sql_content = file_get_contents($sql_file);
            $pdo->exec($sql_content);

            // Update config.php with these credentials
            $config_path = __DIR__ . '/../config.php';
            if (file_exists($config_path)) {
                $cfg = file_get_contents($config_path);
                $cfg = preg_replace("/'host'\s*=>\s*'.*?'/", "'host' => '{$db_host}'", $cfg);
                $cfg = preg_replace("/'port'\s*=>\s*\d+/", "'port' => {$db_port}", $cfg);
                $cfg = preg_replace("/'name'\s*=>\s*'.*?'/", "'name' => '{$db_name}'", $cfg);
                $cfg = preg_replace("/'user'\s*=>\s*'.*?'/", "'user' => '{$db_user}'", $cfg);
                $cfg = preg_replace("/'pass'\s*=>\s*'.*?'/", "'pass' => '{$db_pass}'", $cfg);
                @file_put_contents($config_path, $cfg);
            }

            $_SESSION['install_success'] = true;
            header("Location: index.php?step=3");
            exit;

        } catch (Exception $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Wizard | My Hospital Pro (MHP)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-xl w-full bg-white rounded-3xl shadow-2xl overflow-hidden border border-slate-100">
        <!-- Wizard Header -->
        <div class="bg-indigo-600 px-8 py-6 text-white text-center relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md mb-3">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
            </div>
            <h1 class="text-2xl font-black tracking-tight">MY HOSPITAL PRO</h1>
            <p class="text-indigo-100 text-xs mt-1">Pharmacy ERP & Hospital Management System Installer</p>
            
            <!-- Step Indicators -->
            <div class="flex items-center justify-center gap-3 mt-6">
                <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold <?= $step >= 1 ? 'bg-white text-indigo-600' : 'bg-white/20 text-white' ?>">1</span>
                <span class="w-8 h-0.5 bg-white/30"></span>
                <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold <?= $step >= 2 ? 'bg-white text-indigo-600' : 'bg-white/20 text-white' ?>">2</span>
                <span class="w-8 h-0.5 bg-white/30"></span>
                <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold <?= $step >= 3 ? 'bg-white text-indigo-600' : 'bg-white/20 text-white' ?>">3</span>
            </div>
        </div>

        <div class="p-8">
            <?php if ($error): ?>
                <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-medium">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <h2 class="text-lg font-bold text-gray-900 mb-2">Step 1: System Health Verification</h2>
                <p class="text-xs text-gray-500 mb-6">Confirming your web server runtime meets all operational requirements.</p>

                <div class="space-y-3 mb-8">
                    <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-gray-100">
                        <span class="text-xs font-medium text-gray-700">PHP Version (>= 8.0)</span>
                        <span class="text-xs font-bold <?= $php_ok ? 'text-emerald-600' : 'text-red-600' ?>"><?= $php_version ?> <?= $php_ok ? '&#10004;' : '&#10008;' ?></span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-gray-100">
                        <span class="text-xs font-medium text-gray-700">PDO Driver Extension</span>
                        <span class="text-xs font-bold <?= $pdo_ok ? 'text-emerald-600' : 'text-red-600' ?>"><?= $pdo_ok ? 'Enabled &#10004;' : 'Missing &#10008;' ?></span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-gray-100">
                        <span class="text-xs font-medium text-gray-700">PDO MySQL Extension</span>
                        <span class="text-xs font-bold <?= $pdo_mysql_ok ? 'text-emerald-600' : 'text-red-600' ?>"><?= $pdo_mysql_ok ? 'Enabled &#10004;' : 'Missing &#10008;' ?></span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-gray-100">
                        <span class="text-xs font-medium text-gray-700">Mbstring & OpenSSL</span>
                        <span class="text-xs font-bold <?= ($mbstring_ok && $openssl_ok) ? 'text-emerald-600' : 'text-red-600' ?>"><?= ($mbstring_ok && $openssl_ok) ? 'Enabled &#10004;' : 'Missing &#10008;' ?></span>
                    </div>
                </div>

                <div class="flex justify-end">
                    <?php if ($all_reqs_met): ?>
                        <a href="index.php?step=2" class="px-6 py-2.5 bg-indigo-600 text-white text-xs font-bold rounded-xl hover:bg-indigo-700 transition">
                            Next: Configure Database &rarr;
                        </a>
                    <?php else: ?>
                        <button disabled class="px-6 py-2.5 bg-gray-300 text-gray-500 text-xs font-bold rounded-xl cursor-not-allowed">
                            Requirements Not Met
                        </button>
                    <?php endif; ?>
                </div>

            <?php elseif ($step === 2): ?>
                <h2 class="text-lg font-bold text-gray-900 mb-2">Step 2: MySQL Connection & Setup</h2>
                <p class="text-xs text-gray-500 mb-6">Enter your MySQL database credentials. The installer will create the database and import all 20 required tables with seed data.</p>

                <form method="POST" action="index.php?step=2" class="space-y-4">
                    <input type="hidden" name="action" value="test_and_import">
                    
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Database Host</label>
                            <input type="text" name="db_host" value="localhost" required class="w-full text-xs px-3 py-2 border border-gray-200 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Port</label>
                            <input type="number" name="db_port" value="3306" required class="w-full text-xs px-3 py-2 border border-gray-200 rounded-lg">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Database Name</label>
                        <input type="text" name="db_name" value="mhp_database" required class="w-full text-xs px-3 py-2 border border-gray-200 rounded-lg font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">MySQL Username</label>
                        <input type="text" name="db_user" value="root" required class="w-full text-xs px-3 py-2 border border-gray-200 rounded-lg">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">MySQL Password</label>
                        <input type="password" name="db_pass" placeholder="Leave empty if none" class="w-full text-xs px-3 py-2 border border-gray-200 rounded-lg">
                    </div>

                    <div class="pt-4 flex items-center justify-between">
                        <a href="index.php?step=1" class="text-xs text-gray-500 hover:text-gray-700">&larr; Back</a>
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-xs font-bold rounded-xl hover:bg-indigo-700 transition flex items-center gap-2">
                            <span>Install & Import Schema</span>
                            &rarr;
                        </button>
                    </div>
                </form>

            <?php elseif ($step === 3): ?>
                <div class="text-center py-4">
                    <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h2 class="text-xl font-black text-gray-900 mb-2">Installation Successful!</h2>
                    <p class="text-xs text-gray-500 max-w-md mx-auto mb-6">The database has been configured, schemas imported, and demo dataset successfully loaded.</p>

                    <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200 text-left mb-6 max-w-sm mx-auto">
                        <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-2">Default Admin Credentials</span>
                        <div class="flex items-center justify-between text-xs py-1 border-b border-gray-200">
                            <span class="text-gray-600">Username:</span>
                            <span class="font-mono font-bold text-gray-900">admin</span>
                        </div>
                        <div class="flex items-center justify-between text-xs py-1">
                            <span class="text-gray-600">Password:</span>
                            <span class="font-mono font-bold text-indigo-600">admin123</span>
                        </div>
                    </div>

                    <a href="../login.php" class="inline-flex items-center justify-center px-8 py-3 bg-indigo-600 text-white text-xs font-bold rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-600/30">
                        Launch Application Login &rarr;
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
