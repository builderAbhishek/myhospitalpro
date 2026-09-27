<?php
/**
 * Edit Customer Controller & Form
 */
$page_title = "Edit Customer";
$current_page = "customers";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

require_login();
$db = get_db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$cust = $stmt->fetch();

if (!$cust) {
    set_flash('error', "Customer not found.");
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? 'Delhi');
    $customer_type = trim($_POST['customer_type'] ?? 'Regular Patient');
    $credit_limit = (float)($_POST['credit_limit'] ?? 5000.0);

    if (empty($name)) {
        $error = "Customer Name is required.";
    } else {
        try {
            $up = $db->prepare("
                UPDATE customers SET 
                    name = ?, mobile = ?, email = ?, address = ?, city = ?,
                    customer_type = ?, credit_limit = ?
                WHERE id = ?
            ");
            $up->execute([$name, $mobile, $email, $address, $city, $customer_type, $credit_limit, $id]);

            record_audit_log($db, 'CUSTOMER_UPDATE', 'customers', $id, $cust, $_POST);
            set_flash('success', "Customer details updated successfully.");
            header("Location: view.php?id={$id}");
            exit;
        } catch (Exception $e) {
            $error = "Error updating customer: " . $e->getMessage();
        }
    }
}
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 space-y-6">
        
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="view.php?id=<?= $id ?>" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                </a>
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">Edit Customer: <?= e($cust['name']) ?></h1>
                    <p class="text-xs text-slate-500">Customer Code: <?= e($cust['customer_code']) ?></p>
                </div>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-medium">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <div class="max-w-3xl">
            <form method="POST" action="edit.php?id=<?= $id ?>" class="app-card p-6 bg-white border border-slate-200 rounded-xl shadow-xs space-y-4">
                <?= csrf_field() ?>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Full Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required value="<?= e($cust['name']) ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Mobile Number</label>
                        <input type="text" name="mobile" value="<?= e($cust['mobile'] ?? '') ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Email Address</label>
                        <input type="email" name="email" value="<?= e($cust['email'] ?? '') ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Address</label>
                        <input type="text" name="address" value="<?= e($cust['address'] ?? '') ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">City</label>
                        <input type="text" name="city" value="<?= e($cust['city'] ?? 'Delhi') ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Customer Category</label>
                        <select name="customer_type" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                            <option value="Regular Patient" <?= ($cust['customer_type'] === 'Regular Patient') ? 'selected' : '' ?>>Regular Patient</option>
                            <option value="VIP / Hospital Staff" <?= ($cust['customer_type'] === 'VIP / Hospital Staff') ? 'selected' : '' ?>>VIP / Hospital Staff</option>
                            <option value="Chronic Care Patient" <?= ($cust['customer_type'] === 'Chronic Care Patient') ? 'selected' : '' ?>>Chronic Care Patient</option>
                            <option value="Corporate / Institutional" <?= ($cust['customer_type'] === 'Corporate / Institutional') ? 'selected' : '' ?>>Corporate / Institutional</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Credit Limit (₹)</label>
                        <input type="number" step="0.01" name="credit_limit" value="<?= e($cust['credit_limit']) ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <a href="view.php?id=<?= $id ?>" class="btn-secondary text-xs py-2 px-4 bg-slate-100 text-slate-700 rounded-lg">Cancel</a>
                    <button type="submit" class="btn-primary text-xs py-2 px-6 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg shadow-sm">
                        Update Customer
                    </button>
                </div>
            </form>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
