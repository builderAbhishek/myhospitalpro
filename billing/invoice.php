<?php
/**
 * Print-Ready Indian GST Pharmacy Tax Invoice
 */
$page_title = "Tax Invoice";
require_once __DIR__ . '/../includes/app.php';
require_login();

$db = get_db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT s.*, u.name as staff_name, c.address as customer_address, c.pincode as customer_pincode
    FROM sales s
    LEFT JOIN users u ON s.created_by = u.id
    LEFT JOIN customers c ON s.customer_id = c.id
    WHERE s.id = ?
");
$stmt->execute([$id]);
$sale = $stmt->fetch();

if (!$sale) {
    set_flash('error', "Invoice not found.");
    header("Location: ../dashboard.php");
    exit;
}

// Fetch line items with HSN
$items_stmt = $db->prepare("
    SELECT si.*, m.hsn_code, m.dosage_form, m.unit
    FROM sale_items si
    JOIN medicines m ON si.medicine_id = m.id
    WHERE si.sale_id = ?
");
$items_stmt->execute([$id]);
$items = $items_stmt->fetchAll();

// Calculate HSN-wise tax breakdown
$hsn_summary = [];
foreach ($items as $item) {
    $hsn = $item['hsn_code'] ?: '30049099';
    $rate = (float)$item['gst_rate'];
    $taxable = ($item['selling_price'] * $item['quantity']) - $item['discount'];
    $cgst_rate = $rate / 2.0;
    $sgst_rate = $rate / 2.0;
    $cgst_val = $taxable * ($cgst_rate / 100.0);
    $sgst_val = $taxable * ($sgst_rate / 100.0);

    if (!isset($hsn_summary[$hsn])) {
        $hsn_summary[$hsn] = [
            'taxable' => 0.0,
            'cgst_rate' => $cgst_rate,
            'cgst_val' => 0.0,
            'sgst_rate' => $sgst_rate,
            'sgst_val' => 0.0,
            'total_tax' => 0.0
        ];
    }
    $hsn_summary[$hsn]['taxable'] += $taxable;
    $hsn_summary[$hsn]['cgst_val'] += $cgst_val;
    $hsn_summary[$hsn]['sgst_val'] += $sgst_val;
    $hsn_summary[$hsn]['total_tax'] += ($cgst_val + $sgst_val);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= e($sale['invoice_number']) ?> — <?= e(get_setting('hospital_short_name', APP_SHORT_NAME)) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; }
            .print-border { border: 1px solid #cbd5e1 !important; }
        }
    </style>
</head>
<body class="bg-slate-100 py-6 px-4 antialiased text-slate-800 text-xs">

    <!-- Action Bar (Hidden in Print) -->
    <div class="max-w-4xl mx-auto mb-4 flex items-center justify-between no-print">
        <a href="index.php" class="px-3 py-1.5 rounded-lg bg-white border border-slate-300 font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-1.5 shadow-xs">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
            <span>Back to POS Counter</span>
        </a>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-bold flex items-center gap-1.5 shadow-sm">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                <span>Print Tax Invoice</span>
            </button>
        </div>
    </div>

    <!-- Master Invoice Sheet -->
    <div class="max-w-4xl mx-auto bg-white border border-slate-300 rounded-xl shadow-lg p-6 sm:p-8 space-y-5">
        
        <!-- Header: Hospital Branding -->
        <div class="border-b border-slate-200 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <img src="../assets/images/logo.svg" alt="Logo" class="w-12 h-12 rounded-lg" onerror="this.src='../assets/images/favicon.svg'">
                <div>
                    <h1 class="text-base sm:text-lg font-extrabold text-slate-900 uppercase tracking-tight"><?= e(get_setting('hospital_legal_name', HOSPITAL_LEGAL_NAME)) ?></h1>
                    <div class="text-[11px] font-semibold text-teal-700"><?= e(get_setting('store_name', HOSPITAL_STORE_NAME)) ?></div>
                    <div class="text-[11px] text-slate-500 mt-0.5"><?= e(get_setting('address', HOSPITAL_ADDRESS)) ?>, <?= e(get_setting('city', HOSPITAL_CITY)) ?> - <?= e(get_setting('pincode', HOSPITAL_PINCODE)) ?></div>
                    <div class="text-[11px] text-slate-500">Phone: <?= e(get_setting('phone', HOSPITAL_PHONE)) ?> • Email: <?= e(get_setting('email', HOSPITAL_EMAIL)) ?></div>
                </div>
            </div>

            <div class="sm:text-right border-t sm:border-t-0 pt-2 sm:pt-0 border-slate-100">
                <span class="inline-block px-2.5 py-1 rounded bg-teal-50 text-teal-800 font-extrabold text-[11px] uppercase tracking-wider border border-teal-200">GST TAX INVOICE</span>
                <div class="text-[11px] text-slate-600 mt-1 font-mono"><strong>GSTIN:</strong> <?= e(get_setting('gstin', HOSPITAL_GSTIN)) ?></div>
                <div class="text-[11px] text-slate-600 font-mono"><strong>DL No:</strong> <?= e(get_setting('drug_license', HOSPITAL_DL_NUMBER)) ?></div>
                <div class="text-[11px] text-slate-600 font-mono"><strong>FSSAI:</strong> <?= e(get_setting('fssai_number', HOSPITAL_FSSAI)) ?></div>
            </div>
        </div>

        <!-- Meta Details: Invoice & Patient -->
        <div class="grid grid-cols-2 gap-4 bg-slate-50 p-3.5 rounded-lg border border-slate-200">
            <div>
                <div class="text-[11px] text-slate-400 uppercase font-semibold">Billed To (Patient / Customer)</div>
                <div class="text-sm font-bold text-slate-900 mt-0.5"><?= e($sale['customer_name']) ?></div>
                <?php if (!empty($sale['customer_mobile'])): ?>
                    <div class="text-[11px] text-slate-600">Mobile: <?= e($sale['customer_mobile']) ?></div>
                <?php endif; ?>
                <?php if (!empty($sale['customer_address'])): ?>
                    <div class="text-[11px] text-slate-500"><?= e($sale['customer_address']) ?></div>
                <?php endif; ?>
                <?php if (!empty($sale['doctor_name'])): ?>
                    <div class="text-[11px] text-slate-700 mt-1">Prescribed By: <strong><?= e($sale['doctor_name']) ?></strong></div>
                <?php endif; ?>
            </div>

            <div class="text-right">
                <div class="text-[11px] text-slate-400 uppercase font-semibold">Invoice Information</div>
                <div class="text-sm font-mono font-extrabold text-teal-800 mt-0.5"><?= e($sale['invoice_number']) ?></div>
                <div class="text-[11px] text-slate-600">Date: <strong><?= format_datetime($sale['created_at']) ?></strong></div>
                <div class="text-[11px] text-slate-600">Payment: <strong><?= e($sale['payment_method']) ?> (<?= e($sale['payment_status']) ?>)</strong></div>
                <div class="text-[11px] text-slate-500">Dispensed by: <?= e($sale['staff_name'] ?? 'Counter Staff') ?></div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-300 text-slate-600 font-bold uppercase text-[10px]">
                        <th class="py-2 px-2 w-8">#</th>
                        <th class="py-2 px-3">Description of Medicine</th>
                        <th class="py-2 px-2 font-mono">HSN</th>
                        <th class="py-2 px-2 font-mono">Batch</th>
                        <th class="py-2 px-2">Exp</th>
                        <th class="py-2 px-2 text-right">Qty</th>
                        <th class="py-2 px-2 text-right">MRP</th>
                        <th class="py-2 px-2 text-right">Rate</th>
                        <th class="py-2 px-2 text-right">Disc%</th>
                        <th class="py-2 px-2 text-right">GST%</th>
                        <th class="py-2 px-3 text-right">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php foreach ($items as $idx => $item): ?>
                        <tr>
                            <td class="py-2 px-2 text-slate-400"><?= $idx + 1 ?></td>
                            <td class="py-2 px-3 font-semibold text-slate-900"><?= e($item['medicine_name']) ?></td>
                            <td class="py-2 px-2 font-mono text-slate-600"><?= e($item['hsn_code'] ?: '30049099') ?></td>
                            <td class="py-2 px-2 font-mono text-slate-700"><?= e($item['batch_number']) ?></td>
                            <td class="py-2 px-2 text-slate-600"><?= date('m/y', strtotime($item['expiry_date'])) ?></td>
                            <td class="py-2 px-2 text-right font-bold text-slate-900"><?= (int)$item['quantity'] ?></td>
                            <td class="py-2 px-2 text-right text-slate-500"><?= format_currency($item['selling_price']) ?></td>
                            <td class="py-2 px-2 text-right"><?= format_currency($item['selling_price']) ?></td>
                            <td class="py-2 px-2 text-right text-slate-500"><?= (float)$item['discount'] > 0 ? (float)$item['discount'] : '0.00' ?></td>
                            <td class="py-2 px-2 text-right text-slate-600"><?= (float)$item['gst_rate'] ?>%</td>
                            <td class="py-2 px-3 text-right font-bold text-slate-900"><?= format_currency($item['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Financial Totals & Tax Breakdown Grid -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 pt-2 border-t border-slate-200">
            
            <!-- HSN Breakdown (7 Cols) -->
            <div class="md:col-span-7">
                <div class="text-[10px] font-bold uppercase text-slate-400 mb-1">GST Tax Slab Breakdown</div>
                <table class="w-full text-left border border-slate-200 text-[10px]">
                    <thead class="bg-slate-50 border-b border-slate-200 font-semibold text-slate-500">
                        <tr>
                            <th class="py-1 px-2 font-mono">HSN</th>
                            <th class="py-1 px-2 text-right">Taxable</th>
                            <th class="py-1 px-2 text-right">CGST</th>
                            <th class="py-1 px-2 text-right">SGST</th>
                            <th class="py-1 px-2 text-right">Total Tax</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($hsn_summary as $hsn => $h): ?>
                            <tr>
                                <td class="py-1 px-2 font-mono"><?= e($hsn) ?></td>
                                <td class="py-1 px-2 text-right"><?= format_currency($h['taxable']) ?></td>
                                <td class="py-1 px-2 text-right"><?= format_currency($h['cgst_val']) ?> (<?= $h['cgst_rate'] ?>%)</td>
                                <td class="py-1 px-2 text-right"><?= format_currency($h['sgst_val']) ?> (<?= $h['sgst_rate'] ?>%)</td>
                                <td class="py-1 px-2 text-right font-semibold"><?= format_currency($h['total_tax']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Grand Totals (5 Cols) -->
            <div class="md:col-span-5 bg-slate-50 p-3 rounded-lg border border-slate-200 space-y-1 text-xs">
                <div class="flex justify-between text-slate-600">
                    <span>Taxable Subtotal:</span>
                    <span class="font-bold text-slate-900"><?= format_currency($sale['subtotal']) ?></span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Total GST Amount:</span>
                    <span class="font-bold text-slate-900"><?= format_currency($sale['gst_amount']) ?></span>
                </div>
                <?php if ((float)$sale['discount'] > 0): ?>
                    <div class="flex justify-between text-emerald-700">
                        <span>Bill Discount:</span>
                        <span class="font-bold">-<?= format_currency($sale['discount']) ?></span>
                    </div>
                <?php endif; ?>
                <div class="flex justify-between text-slate-500">
                    <span>Round Off:</span>
                    <span class="font-mono"><?= (float)$sale['round_off'] >= 0 ? '+' : '' ?><?= format_currency($sale['round_off']) ?></span>
                </div>
                <div class="flex justify-between text-base font-extrabold text-teal-800 pt-2 border-t border-slate-300">
                    <span>Grand Total:</span>
                    <span><?= format_currency($sale['grand_total']) ?></span>
                </div>
                <div class="flex justify-between text-xs font-bold text-emerald-700 pt-1">
                    <span>Amount Paid:</span>
                    <span><?= format_currency($sale['paid_amount']) ?></span>
                </div>
                <?php if ((float)$sale['due_amount'] > 0): ?>
                    <div class="flex justify-between text-xs font-bold text-rose-600">
                        <span>Balance Due:</span>
                        <span><?= format_currency($sale['due_amount']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer Disclaimers & Signature -->
        <div class="pt-4 border-t border-slate-200 grid grid-cols-2 gap-4 text-[10px] text-slate-500">
            <div>
                <div class="font-bold text-slate-700 mb-0.5">Terms &amp; Conditions</div>
                <p><?= e(get_setting('invoice_footer_terms', 'Goods once sold can only be returned within 7 days against original tax invoice. Store medicines below 25°C away from direct sunlight.')) ?></p>
            </div>
            <div class="text-right flex flex-col justify-end">
                <div class="font-semibold text-slate-800">For <?= e(get_setting('hospital_name', APP_NAME)) ?></div>
                <div class="mt-8 border-t border-slate-300 pt-1 text-slate-500">Authorized Pharmacist Signatory</div>
            </div>
        </div>

    </div>

</body>
</html>
