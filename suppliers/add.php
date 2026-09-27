<?php
/**
 * Register Supplier Controller & Form
 */
$page_title = "Register Supplier";
$current_page = "suppliers";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

require_login();
$db = get_db();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['name'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? 'Delhi');
    $state = trim($_POST['state'] ?? 'Delhi');
    $pincode = trim($_POST['pincode'] ?? '110006');
    $gstin = trim($_POST['gstin'] ?? '');
    $drug_license = trim($_POST['drug_license'] ?? '');
    $payment_terms = trim($_POST['payment_terms'] ?? '30 Days Net');
    $opening_balance = (float)($_POST['opening_balance'] ?? 0.0);

    if (empty($name) || empty($gstin)) {
        $error = "Distributor Name and GSTIN are required.";
    } else {
        try {
            $stmt = $db->query("SELECT MAX(id) FROM suppliers");
            $next_id = (int)$stmt->fetchColumn() + 1;
            $code = 'SUPP-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);

            $in = $db->prepare("
                INSERT INTO suppliers (
                    supplier_code, name, company_name, contact_person, phone, email,
                    address, city, state, pincode, gstin, drug_license, payment_terms,
                    opening_balance, current_balance, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $in->execute([
                $code, $name, $company_name, $contact_person, $phone, $email,
                $address, $city, $state, $pincode, $gstin, $drug_license, $payment_terms,
                $opening_balance, $opening_balance
            ]);
            $supp_id = (int)$db->lastInsertId();

            record_audit_log($db, 'SUPPLIER_CREATE', 'suppliers', $supp_id, null, ['name' => $name, 'gstin' => $gstin]);
            set_flash('success', "Supplier '{$name}' registered successfully.");
            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            $error = "Error saving supplier: " . $e->getMessage();
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
                    <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">Register Distributor / Supplier</h1>
                    <p class="text-xs text-slate-500">Record pharma stockist details, drug licensing and statutory tax info</p>
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
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Distributor Agency / Trade Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required placeholder="e.g. Apollo Healthcare Distributors Pvt Ltd" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Legal Company Name</label>
                        <input type="text" name="company_name" placeholder="e.g. Apollo Logistics Division" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Key Contact Person</label>
                        <input type="text" name="contact_person" placeholder="e.g. Sanjay Singhal" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Phone / Mobile <span class="text-rose-500">*</span></label>
                        <input type="text" name="phone" required placeholder="+91 98112 34567" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Email</label>
                        <input type="email" name="email" placeholder="orders@distributor.in" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">GSTIN <span class="text-rose-500">*</span></label>
                        <input type="text" name="gstin" required placeholder="07AAAAA0000A1Z5" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm font-mono focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Drug License Number</label>
                        <input type="text" name="drug_license" placeholder="DL-20B-112233 / 21B-112234" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm font-mono focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Warehouse Address</label>
                        <input type="text" name="address" placeholder="e.g. Building 14, Okhla Industrial Area Phase 3" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Payment Terms</label>
                        <input type="text" name="payment_terms" value="30 Days Net" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Opening Balance Payable (₹)</label>
                        <input type="number" step="0.01" name="opening_balance" value="0.00" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <a href="index.php" class="btn-secondary text-xs py-2 px-4 bg-slate-100 text-slate-700 rounded-lg">Cancel</a>
                    <button type="submit" class="btn-primary text-xs py-2 px-6 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg shadow-sm">
                        Register Supplier
                    </button>
                </div>
            </form>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
