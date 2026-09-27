<?php
/**
 * Register Customer Controller & Form
 */
$page_title = "Add Customer";
$current_page = "customers";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

require_login();
$db = get_db();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? 'Delhi');
    $state = trim($_POST['state'] ?? 'Delhi');
    $pincode = trim($_POST['pincode'] ?? '110085');
    $customer_type = trim($_POST['customer_type'] ?? 'Regular Patient');
    $credit_limit = (float)($_POST['credit_limit'] ?? 5000.0);
    $opening_balance = (float)($_POST['opening_balance'] ?? 0.0);

    if (empty($name)) {
        $error = "Customer Name is required.";
    } else {
        try {
            $stmt = $db->query("SELECT MAX(id) FROM customers");
            $next_id = (int)$stmt->fetchColumn() + 1;
            $code = 'CUST-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);

            $in = $db->prepare("
                INSERT INTO customers (
                    customer_code, name, mobile, email, address, city, state, pincode,
                    customer_type, credit_limit, opening_balance, current_balance, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $in->execute([
                $code, $name, $mobile, $email, $address, $city, $state, $pincode,
                $customer_type, $credit_limit, $opening_balance, $opening_balance
            ]);
            $cust_id = (int)$db->lastInsertId();

            record_audit_log($db, 'CUSTOMER_CREATE', 'customers', $cust_id, null, ['name' => $name, 'code' => $code]);
            set_flash('success', "Customer '{$name}' registered successfully.");
            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            $error = "Error saving customer: " . $e->getMessage();
        }
    }
}
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 space-y-6">
        
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="index.php" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                </a>
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">Register New Customer / Patient</h1>
                    <p class="text-xs text-slate-500">Record patient demographics and assign credit limits</p>
                </div>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-medium">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <div class="max-w-3xl">
            <form method="POST" action="add.php" class="app-card p-6 bg-white border border-slate-200 rounded-xl shadow-xs space-y-4">
                <?= csrf_field() ?>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Full Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required placeholder="e.g. Ramesh Kumar" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Mobile Number</label>
                        <input type="text" name="mobile" placeholder="+91 98765 43210" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Email Address</label>
                        <input type="email" name="email" placeholder="patient@gmail.com" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Residential / Clinic Address</label>
                        <input type="text" name="address" placeholder="e.g. B-4/102, Sector 14, Rohini" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">City</label>
                        <input type="text" name="city" value="Delhi" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Customer Category</label>
                        <select name="customer_type" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                            <option value="Regular Patient">Regular Patient</option>
                            <option value="VIP / Hospital Staff">VIP / Hospital Staff</option>
                            <option value="Chronic Care Patient">Chronic Care Patient</option>
                            <option value="Corporate / Institutional">Corporate / Institutional</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Credit Limit (₹)</label>
                        <input type="number" step="0.01" name="credit_limit" value="5000.00" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Opening Balance Dues (₹)</label>
                        <input type="number" step="0.01" name="opening_balance" value="0.00" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <a href="index.php" class="btn-secondary text-xs py-2 px-4 bg-slate-100 text-slate-700 rounded-lg">Cancel</a>
                    <button type="submit" class="btn-primary text-xs py-2 px-6 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg shadow-sm">
                        Save Customer
                    </button>
                </div>
            </form>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
