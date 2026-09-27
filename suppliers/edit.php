<?php
/**
 * Edit Supplier Controller & Form
 */
$page_title = "Edit Supplier";
$current_page = "suppliers";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

require_login();
$db = get_db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->execute([$id]);
$supp = $stmt->fetch();

if (!$supp) {
    set_flash('error', "Supplier not found.");
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['name'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $gstin = trim($_POST['gstin'] ?? '');
    $drug_license = trim($_POST['drug_license'] ?? '');
    $payment_terms = trim($_POST['payment_terms'] ?? '30 Days Net');

    if (empty($name) || empty($gstin)) {
        $error = "Distributor Name and GSTIN are required.";
    } else {
        try {
            $up = $db->prepare("
                UPDATE suppliers SET 
                    name = ?, company_name = ?, contact_person = ?, phone = ?, email = ?,
                    address = ?, gstin = ?, drug_license = ?, payment_terms = ?
                WHERE id = ?
            ");
            $up->execute([$name, $company_name, $contact_person, $phone, $email, $address, $gstin, $drug_license, $payment_terms, $id]);

            record_audit_log($db, 'SUPPLIER_UPDATE', 'suppliers', $id, $supp, $_POST);
            set_flash('success', "Supplier details updated successfully.");
            header("Location: view.php?id={$id}");
            exit;
        } catch (Exception $e) {
            $error = "Error updating supplier: " . $e->getMessage();
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
                    <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">Edit Supplier: <?= e($supp['name']) ?></h1>
                    <p class="text-xs text-slate-500">Code: <?= e($supp['supplier_code']) ?></p>
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
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Distributor Agency / Trade Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required value="<?= e($supp['name']) ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Company Name</label>
                        <input type="text" name="company_name" value="<?= e($supp['company_name'] ?? '') ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Contact Person</label>
                        <input type="text" name="contact_person" value="<?= e($supp['contact_person'] ?? '') ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Phone</label>
                        <input type="text" name="phone" required value="<?= e($supp['phone']) ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Email</label>
                        <input type="email" name="email" value="<?= e($supp['email'] ?? '') ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">GSTIN <span class="text-rose-500">*</span></label>
                        <input type="text" name="gstin" required value="<?= e($supp['gstin']) ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm font-mono focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Drug License</label>
                        <input type="text" name="drug_license" value="<?= e($supp['drug_license'] ?? '') ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm font-mono focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Address</label>
                        <input type="text" name="address" value="<?= e($supp['address'] ?? '') ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Payment Terms</label>
                        <input type="text" name="payment_terms" value="<?= e($supp['payment_terms'] ?? '30 Days Net') ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <a href="view.php?id=<?= $id ?>" class="btn-secondary text-xs py-2 px-4 bg-slate-100 text-slate-700 rounded-lg">Cancel</a>
                    <button type="submit" class="btn-primary text-xs py-2 px-6 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg shadow-sm">
                        Update Supplier
                    </button>
                </div>
            </form>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
