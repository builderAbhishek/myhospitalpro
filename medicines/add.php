<?php
/**
 * Register New Medicine Controller & Form
 */
$page_title = "Add Medicine";
$current_page = "medicines";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

require_login();
$db = get_db();
$error = '';

// Handle POST
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

    // Initial batch info (optional)
    $batch_number = trim($_POST['batch_number'] ?? '');
    $expiry_date = trim($_POST['expiry_date'] ?? '');
    $initial_qty = (int)($_POST['initial_qty'] ?? 0);

    if (empty($name)) {
        $error = "Medicine name is required.";
    } else {
        try {
            $db->beginTransaction();

            // Generate unique code
            $stmt = $db->query("SELECT MAX(id) FROM medicines");
            $next_id = (int)$stmt->fetchColumn() + 1;
            $medicine_code = 'MED-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);

            $stmt = $db->prepare("
                INSERT INTO medicines (
                    medicine_code, name, generic_name, brand_name, category_id, manufacturer_id, 
                    dosage_form, strength, pack_size, unit, hsn_code, gst_rate, purchase_price, 
                    selling_price, mrp, reorder_level, rack_number, barcode, prescription_required, 
                    schedule_type, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([
                $medicine_code, $name, $generic_name, $brand_name, $category_id, $manufacturer_id,
                $dosage_form, $strength, $pack_size, $unit, $hsn_code, $gst_rate, $purchase_price,
                $selling_price, $mrp, $reorder_level, $rack_number, $barcode, $prescription_required,
                $schedule_type
            ]);
            $medicine_id = (int)$db->lastInsertId();

            // If initial batch provided with quantity
            if (!empty($batch_number) && !empty($expiry_date) && $initial_qty > 0) {
                $batch_stmt = $db->prepare("
                    INSERT INTO medicine_batches (medicine_id, batch_number, expiry_date, purchase_price, mrp, selling_price, quantity, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
                ");
                $batch_stmt->execute([$medicine_id, $batch_number, $expiry_date, $purchase_price, $mrp, $selling_price, $initial_qty]);
                $batch_id = (int)$db->lastInsertId();

                record_stock_movement($db, 'ADJUSTMENT_IN', $medicine_id, $batch_id, $initial_qty, null, 'INIT-STOCK', 'Initial formulation opening stock');
            }

            record_audit_log($db, 'MEDICINE_CREATE', 'medicines', $medicine_id, null, ['name' => $name, 'code' => $medicine_code]);
            $db->commit();

            set_flash('success', "Medicine '{$name}' registered successfully into formulary.");
            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Failed to save medicine: " . $e->getMessage();
        }
    }
}

// Fetch categories, manufacturers and units
$categories = $db->query("SELECT * FROM medicine_categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();
$manufacturers = $db->query("SELECT * FROM manufacturers WHERE status = 'active' ORDER BY name ASC")->fetchAll();
$units = $db->query("SELECT * FROM medicine_units ORDER BY name ASC")->fetchAll();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 space-y-6">
        
        <!-- Header Strip -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="index.php" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                </a>
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">Add New Medicine</h1>
                    <p class="text-xs text-slate-500">Register new drug formulation into the pharmacy master database</p>
                </div>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-medium flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="add.php" class="space-y-6 max-w-5xl">
            <?= csrf_field() ?>

            <!-- Section 1: Basic Information -->
            <div class="app-card p-5 bg-white border border-slate-200 rounded-xl shadow-xs space-y-4">
                <div class="border-b border-slate-200 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-6 h-6 rounded bg-teal-50 text-teal-700 flex items-center justify-center text-xs font-bold">1</span>
                        <span>Basic Product Information</span>
                    </h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="lg:col-span-2">
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Medicine Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required placeholder="e.g. Paracetamol 500mg" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Trade / Brand Name</label>
                        <input type="text" name="brand_name" placeholder="e.g. Crocin Advance / Calpol" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Generic Name / Active Salt <span class="text-rose-500">*</span></label>
                        <input type="text" name="generic_name" required placeholder="e.g. Paracetamol / Acetaminophen" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Category <span class="text-rose-500">*</span></label>
                        <select name="category_id" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Manufacturer</label>
                        <select name="manufacturer_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                            <option value="">Select Manufacturer</option>
                            <?php foreach ($manufacturers as $man): ?>
                                <option value="<?= $man['id'] ?>"><?= e($man['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Dosage Form</label>
                        <input type="text" name="dosage_form" value="Tablet" placeholder="e.g. Tablet, Syrup, Injection" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Strength</label>
                        <input type="text" name="strength" placeholder="e.g. 500 mg / 10 ml" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Pack Size</label>
                        <input type="text" name="pack_size" value="10 Tablets / Strip" placeholder="e.g. 10 Tablets / Strip" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Dispensing Unit</label>
                        <select name="unit" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                            <?php foreach ($units as $u): ?>
                                <option value="<?= e($u['name']) ?>"><?= e($u['name']) ?> (<?= e($u['short_code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 2: Pricing & GST -->
            <div class="app-card p-5 bg-white border border-slate-200 rounded-xl shadow-xs space-y-4">
                <div class="border-b border-slate-200 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-6 h-6 rounded bg-teal-50 text-teal-700 flex items-center justify-center text-xs font-bold">2</span>
                        <span>Pricing &amp; GST Details</span>
                    </h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Purchase Price (₹) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" name="purchase_price" required value="0.00" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">MRP (₹) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" name="mrp" required value="0.00" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Selling Price (₹) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" name="selling_price" required value="0.00" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">GST Rate (%)</label>
                        <select name="gst_rate" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                            <option value="0.00">0% (Nil)</option>
                            <option value="5.00">5% (Life Saving)</option>
                            <option value="12.00" selected>12% (Standard Pharma)</option>
                            <option value="18.00">18% (General Formulations)</option>
                            <option value="28.00">28% (Luxury/Special)</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">HSN Code</label>
                        <input type="text" name="hsn_code" value="30049099" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                </div>
            </div>

            <!-- Section 3: Inventory & Location -->
            <div class="app-card p-5 bg-white border border-slate-200 rounded-xl shadow-xs space-y-4">
                <div class="border-b border-slate-200 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-6 h-6 rounded bg-teal-50 text-teal-700 flex items-center justify-center text-xs font-bold">3</span>
                        <span>Inventory Parameters &amp; Shelf Location</span>
                    </h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Reorder Alert Threshold</label>
                        <input type="number" name="reorder_level" value="20" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Rack / Shelf Number</label>
                        <input type="text" name="rack_number" placeholder="e.g. A-12-04" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Barcode / EAN</label>
                        <input type="text" name="barcode" placeholder="Scan or enter barcode" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Schedule Class</label>
                        <select name="schedule_type" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                            <option value="General (OTC)">General (OTC)</option>
                            <option value="Schedule H">Schedule H</option>
                            <option value="Schedule H1">Schedule H1</option>
                            <option value="Schedule X">Schedule X (Narcotics)</option>
                            <option value="Schedule G">Schedule G</option>
                        </select>
                    </div>
                </div>

                <div class="pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="prescription_required" value="1" class="rounded text-teal-600 focus:ring-teal-500 border-slate-300 w-4 h-4">
                        <span class="text-xs font-semibold text-slate-700">Requires Doctor Prescription (Schedule Rx mandatory check at POS counter)</span>
                    </label>
                </div>
            </div>

            <!-- Optional Initial Batch -->
            <div class="app-card p-5 bg-white border border-slate-200 rounded-xl shadow-xs space-y-4">
                <div class="border-b border-slate-200 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-6 h-6 rounded bg-slate-100 text-slate-700 flex items-center justify-center text-xs font-bold">4</span>
                        <span>Opening Stock / Initial Batch (Optional)</span>
                    </h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Batch Number</label>
                        <input type="text" name="batch_number" placeholder="e.g. BATCH-01" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Expiry Date</label>
                        <input type="date" name="expiry_date" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Initial Quantity</label>
                        <input type="number" name="initial_qty" value="0" min="0" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="index.php" class="btn-secondary text-xs py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Cancel</a>
                <button type="submit" class="btn-primary text-xs py-2.5 px-6 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg shadow-sm">Save Medicine</button>
            </div>
        </form>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
