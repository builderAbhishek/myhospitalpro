<?php
/**
 * Edit Medicine Controller & Form
 */
$page_title = "Edit Medicine";
$current_page = "medicines";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

require_login();
$db = get_db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM medicines WHERE id = ?");
$stmt->execute([$id]);
$med = $stmt->fetch();

if (!$med) {
    set_flash('error', "Medicine not found.");
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    $name = trim($_POST['name'] ?? '');
    $generic_name = trim($_POST['generic_name'] ?? '');
    $brand_name = trim($_POST['brand_name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 1);
    $manufacturer_id = !empty($_POST['manufacturer_id']) ? (int)$_POST['manufacturer_id'] : null;
    $dosage_form = trim($_POST['dosage_form'] ?? 'Tablet');
    $strength = trim($_POST['strength'] ?? '');
    $pack_size = trim($_POST['pack_size'] ?? '');
    $unit = trim($_POST['unit'] ?? 'Strip');
    $hsn_code = trim($_POST['hsn_code'] ?? '30049099');
    $gst_rate = (float)($_POST['gst_rate'] ?? 12.0);
    $purchase_price = (float)($_POST['purchase_price'] ?? 0.0);
    $selling_price = (float)($_POST['selling_price'] ?? 0.0);
    $mrp = (float)($_POST['mrp'] ?? 0.0);
    $reorder_level = (int)($_POST['reorder_level'] ?? 20);
    $rack_number = trim($_POST['rack_number'] ?? '');
    $barcode = trim($_POST['barcode'] ?? '');
    $prescription_required = isset($_POST['prescription_required']) ? 1 : 0;
    $schedule_type = trim($_POST['schedule_type'] ?? 'General (OTC)');

    if (empty($name)) {
        $error = "Medicine name is required.";
    } else {
        try {
            $stmt = $db->prepare("
                UPDATE medicines SET 
                    name = ?, generic_name = ?, brand_name = ?, category_id = ?, manufacturer_id = ?,
                    dosage_form = ?, strength = ?, pack_size = ?, unit = ?, hsn_code = ?, gst_rate = ?,
                    purchase_price = ?, selling_price = ?, mrp = ?, reorder_level = ?, rack_number = ?,
                    barcode = ?, prescription_required = ?, schedule_type = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $name, $generic_name, $brand_name, $category_id, $manufacturer_id,
                $dosage_form, $strength, $pack_size, $unit, $hsn_code, $gst_rate,
                $purchase_price, $selling_price, $mrp, $reorder_level, $rack_number,
                $barcode, $prescription_required, $schedule_type, $id
            ]);

            record_audit_log($db, 'MEDICINE_UPDATE', 'medicines', $id, $med, $_POST);
            set_flash('success', "Medicine '{$name}' updated successfully.");
            header("Location: view.php?id={$id}");
            exit;
        } catch (Exception $e) {
            $error = "Failed to update: " . $e->getMessage();
        }
    }
}

$categories = $db->query("SELECT * FROM medicine_categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();
$manufacturers = $db->query("SELECT * FROM manufacturers WHERE status = 'active' ORDER BY name ASC")->fetchAll();
$units = $db->query("SELECT * FROM medicine_units ORDER BY name ASC")->fetchAll();
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
                    <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">Edit Medicine: <?= e($med['name']) ?></h1>
                    <p class="text-xs text-slate-500">Code: <?= e($med['medicine_code']) ?></p>
                </div>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-medium">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="edit.php?id=<?= $id ?>" class="space-y-6 max-w-5xl">
            <?= csrf_field() ?>

            <!-- Section 1: Basic Information -->
            <div class="app-card p-5 bg-white border border-slate-200 rounded-xl shadow-xs space-y-4">
                <div class="border-b border-slate-200 pb-3">
                    <h3 class="text-sm font-bold text-slate-900">1. Basic Product Information</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="lg:col-span-2">
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Medicine Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required value="<?= e($med['name']) ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Trade / Brand Name</label>
                        <input type="text" name="brand_name" value="<?= e($med['brand_name'] ?? '') ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Generic Name / Active Salt <span class="text-rose-500">*</span></label>
                        <input type="text" name="generic_name" required value="<?= e($med['generic_name'] ?? '') ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Category</label>
                        <select name="category_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($med['category_id'] == $cat['id']) ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Manufacturer</label>
                        <select name="manufacturer_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                            <option value="">Select Manufacturer</option>
                            <?php foreach ($manufacturers as $man): ?>
                                <option value="<?= $man['id'] ?>" <?= ($med['manufacturer_id'] == $man['id']) ? 'selected' : '' ?>><?= e($man['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Dosage Form</label>
                        <input type="text" name="dosage_form" value="<?= e($med['dosage_form'] ?? '') ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Strength</label>
                        <input type="text" name="strength" value="<?= e($med['strength'] ?? '') ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Pack Size</label>
                        <input type="text" name="pack_size" value="<?= e($med['pack_size'] ?? '') ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Unit</label>
                        <select name="unit" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                            <?php foreach ($units as $u): ?>
                                <option value="<?= e($u['name']) ?>" <?= ($med['unit'] === $u['name']) ? 'selected' : '' ?>><?= e($u['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 2: Pricing -->
            <div class="app-card p-5 bg-white border border-slate-200 rounded-xl shadow-xs space-y-4">
                <div class="border-b border-slate-200 pb-3">
                    <h3 class="text-sm font-bold text-slate-900">2. Pricing &amp; GST</h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Purchase Price (₹)</label>
                        <input type="number" step="0.01" name="purchase_price" value="<?= e($med['purchase_price']) ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">MRP (₹)</label>
                        <input type="number" step="0.01" name="mrp" value="<?= e($med['mrp']) ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Selling Price (₹)</label>
                        <input type="number" step="0.01" name="selling_price" value="<?= e($med['selling_price']) ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">GST Rate (%)</label>
                        <select name="gst_rate" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                            <option value="0.00" <?= ($med['gst_rate'] == 0) ? 'selected' : '' ?>>0%</option>
                            <option value="5.00" <?= ($med['gst_rate'] == 5) ? 'selected' : '' ?>>5%</option>
                            <option value="12.00" <?= ($med['gst_rate'] == 12) ? 'selected' : '' ?>>12%</option>
                            <option value="18.00" <?= ($med['gst_rate'] == 18) ? 'selected' : '' ?>>18%</option>
                            <option value="28.00" <?= ($med['gst_rate'] == 28) ? 'selected' : '' ?>>28%</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">HSN Code</label>
                        <input type="text" name="hsn_code" value="<?= e($med['hsn_code']) ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                </div>
            </div>

            <!-- Section 3: Parameters -->
            <div class="app-card p-5 bg-white border border-slate-200 rounded-xl shadow-xs space-y-4">
                <div class="border-b border-slate-200 pb-3">
                    <h3 class="text-sm font-bold text-slate-900">3. Parameters &amp; Shelf Location</h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Reorder Alert Threshold</label>
                        <input type="number" name="reorder_level" value="<?= e($med['reorder_level']) ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Rack / Shelf Number</label>
                        <input type="text" name="rack_number" value="<?= e($med['rack_number'] ?? '') ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Barcode</label>
                        <input type="text" name="barcode" value="<?= e($med['barcode'] ?? '') ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Schedule Class</label>
                        <select name="schedule_type" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                            <option value="General (OTC)" <?= ($med['schedule_type'] === 'General (OTC)') ? 'selected' : '' ?>>General (OTC)</option>
                            <option value="Schedule H" <?= ($med['schedule_type'] === 'Schedule H') ? 'selected' : '' ?>>Schedule H</option>
                            <option value="Schedule H1" <?= ($med['schedule_type'] === 'Schedule H1') ? 'selected' : '' ?>>Schedule H1</option>
                            <option value="Schedule X" <?= ($med['schedule_type'] === 'Schedule X') ? 'selected' : '' ?>>Schedule X</option>
                        </select>
                    </div>
                </div>

                <div class="pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="prescription_required" value="1" <?= $med['prescription_required'] ? 'checked' : '' ?> class="rounded text-teal-600 focus:ring-teal-500 border-slate-300 w-4 h-4">
                        <span class="text-xs font-semibold text-slate-700">Requires Doctor Prescription</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="view.php?id=<?= $id ?>" class="btn-secondary text-xs py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Cancel</a>
                <button type="submit" class="btn-primary text-xs py-2.5 px-6 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg shadow-sm">Update Medicine</button>
            </div>
        </form>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
