<?php
/**
 * Transactional Stock Adjustment Controller & Form
 */
$page_title = "Stock Adjustment";
$current_page = "inventory";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

require_login();
$db = get_db();
$error = '';

$pref_med_id = (int)($_GET['medicine_id'] ?? 0);
$pref_batch_id = (int)($_GET['batch_id'] ?? 0);
$pref_type = trim($_GET['type'] ?? 'ADJUSTMENT_OUT');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $batch_id = (int)($_POST['batch_id'] ?? 0);
    $adjustment_type = trim($_POST['adjustment_type'] ?? 'ADJUSTMENT_OUT'); // ADJUSTMENT_IN, ADJUSTMENT_OUT, DAMAGED, EXPIRED
    $quantity = (int)($_POST['quantity'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    if ($batch_id <= 0) {
        $error = "Please select a valid medicine batch.";
    } elseif ($quantity <= 0) {
        $error = "Adjustment quantity must be greater than zero.";
    } elseif (empty($reason)) {
        $error = "Mandatory reason / audit explanation is required.";
    } else {
        try {
            $db->beginTransaction();

            // Lock batch
            $stmt = $db->prepare("SELECT b.*, m.name as medicine_name FROM medicine_batches b JOIN medicines m ON b.medicine_id = m.id WHERE b.id = ? FOR UPDATE");
            $stmt->execute([$batch_id]);
            $batch = $stmt->fetch();

            if (!$batch) {
                throw new Exception("Batch not found.");
            }

            $current_qty = (int)$batch['quantity'];
            $delta = 0;

            if ($adjustment_type === 'ADJUSTMENT_IN') {
                $delta = $quantity;
                $new_qty = $current_qty + $quantity;
            } else {
                // Reduction
                if ($current_qty < $quantity) {
                    throw new Exception("Cannot adjust! Batch has only {$current_qty} available units on hand.");
                }
                $delta = -$quantity;
                $new_qty = $current_qty - $quantity;
            }

            // Update batch
            $new_status = ($new_qty <= 0) ? 'depleted' : 'active';
            $up = $db->prepare("UPDATE medicine_batches SET quantity = ?, status = ? WHERE id = ?");
            $up->execute([$new_qty, $new_status, $batch_id]);

            // Record movement
            $ref_no = 'ADJ-' . date('Y') . '-' . str_pad($batch_id, 4, '0', STR_PAD_LEFT);
            record_stock_movement($db, $adjustment_type, $batch['medicine_id'], $batch_id, $delta, null, $ref_no, $reason);

            record_audit_log($db, 'STOCK_ADJUST', 'inventory', $batch_id, ['qty' => $current_qty], ['delta' => $delta, 'new_qty' => $new_qty, 'type' => $adjustment_type]);
            $db->commit();

            set_flash('success', "Stock for {$batch['medicine_name']} (Batch {$batch['batch_number']}) successfully adjusted by {$delta} units.");
            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
}

// Fetch all active batches for selection
$batches = $db->query("
    SELECT b.id, b.batch_number, b.expiry_date, b.quantity, m.id as medicine_id, m.name as medicine_name, m.unit
    FROM medicine_batches b
    JOIN medicines m ON b.medicine_id = m.id
    WHERE m.status = 'active'
    ORDER BY m.name ASC, b.expiry_date ASC
")->fetchAll();
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
                    <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">Stock Adjustment &amp; Reconciliation</h1>
                    <p class="text-xs text-slate-500">Record physical stock audits, breakages, expired stock quarantine, or recount corrections</p>
                </div>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-medium">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <div class="max-w-2xl">
            <form method="POST" action="stock-adjustment.php" class="app-card p-6 bg-white border border-slate-200 rounded-xl shadow-xs space-y-4">
                <?= csrf_field() ?>

                <div>
                    <label class="text-xs font-semibold text-slate-700 mb-1 block">Select Medicine Batch <span class="text-rose-500">*</span></label>
                    <select name="batch_id" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                        <option value="">-- Choose Batch --</option>
                        <?php foreach ($batches as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= ($pref_batch_id == $b['id']) ? 'selected' : '' ?>>
                                <?= e($b['medicine_name']) ?> — Batch: <?= e($b['batch_number']) ?> (Exp: <?= format_date($b['expiry_date']) ?>) • Current Stock: <?= (int)$b['quantity'] ?> <?= e($b['unit']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Adjustment Type <span class="text-rose-500">*</span></label>
                        <select name="adjustment_type" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                            <option value="ADJUSTMENT_IN" <?= ($pref_type === 'ADJUSTMENT_IN') ? 'selected' : '' ?>>Addition (+) Recount / Found Stock</option>
                            <option value="ADJUSTMENT_OUT" <?= ($pref_type === 'ADJUSTMENT_OUT') ? 'selected' : '' ?>>Reduction (-) Recount / Missing Stock</option>
                            <option value="DAMAGED" <?= ($pref_type === 'DAMAGED') ? 'selected' : '' ?>>Damaged / Broken Packaging</option>
                            <option value="EXPIRED" <?= ($pref_type === 'EXPIRED') ? 'selected' : '' ?>>Expired / Quarantine Removal</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Quantity to Adjust <span class="text-rose-500">*</span></label>
                        <input type="number" name="quantity" min="1" required value="1" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-700 mb-1 block">Audit Note / Reason <span class="text-rose-500">*</span></label>
                    <textarea name="reason" rows="3" required placeholder="Mandatory explanation for compliance audit (e.g. Broken ampoule during stocking, physical count variance...)" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <a href="index.php" class="btn-secondary text-xs py-2 px-4 bg-slate-100 text-slate-700 rounded-lg">Cancel</a>
                    <button type="submit" class="btn-primary text-xs py-2 px-6 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg shadow-sm">
                        Execute Adjustment
                    </button>
                </div>
            </form>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
