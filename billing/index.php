<?php
/**
 * Modern Point of Sale (POS) Counter Billing Terminal
 * FEFO Automated Batch Selection, Barcode Scanner, Instant Cart, GST calculations
 */
$page_title = "Billing / POS Terminal";
$current_page = "billing";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

require_login();
$db = get_db();

// Fetch active customers
$customers = $db->query("SELECT id, name, mobile, current_balance, credit_limit FROM customers WHERE status = 'active' ORDER BY name ASC")->fetchAll();

// Fetch active medicines with active batches sorted by FEFO
$med_stmt = $db->query("
    SELECT m.id, m.name, m.generic_name, m.brand_name, m.selling_price, m.mrp, 
           m.gst_rate, m.unit, m.rack_number, m.barcode, m.prescription_required,
           mc.name as category_name
    FROM medicines m
    LEFT JOIN medicine_categories mc ON m.category_id = mc.id
    WHERE m.status = 'active'
    ORDER BY m.name ASC
");
$catalog_medicines = $med_stmt->fetchAll();

// Pre-fetch active batches per medicine (FEFO sorted)
$batches_by_med = [];
$b_stmt = $db->query("
    SELECT b.id, b.medicine_id, b.batch_number, b.expiry_date, b.quantity, 
           b.selling_price, b.mrp, b.purchase_price
    FROM medicine_batches b
    WHERE b.status = 'active' AND b.quantity > 0 AND b.expiry_date >= CURDATE()
    ORDER BY b.expiry_date ASC
");
while ($b = $b_stmt->fetch()) {
    $batches_by_med[$b['medicine_id']][] = $b;
}

$next_inv = generate_invoice_number();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="flex-1 overflow-hidden p-3 md:p-4 lg:p-6 flex flex-col gap-3">
        
        <!-- POS Split Screen Grid -->
        <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-3 min-h-0">
            
            <!-- LEFT PANEL: Medicine Catalog & FEFO Quick Add (7 Cols) -->
            <div class="lg:col-span-7 xl:col-span-7 flex flex-col h-full bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
                
                <!-- Search & Filter Header -->
                <div class="p-3 border-b border-slate-200 space-y-2 bg-slate-50/50">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </span>
                        <input type="text" id="pos-search" autofocus
                               oninput="filterCatalog(this.value)"
                               placeholder="Search medicine name, generic salt, barcode, or press '/'..." 
                               class="w-full border border-slate-300 rounded-lg pl-9 pr-10 py-2 text-xs sm:text-sm font-medium focus:ring-2 focus:ring-teal-600 focus:outline-none">
                        <span class="absolute right-3 top-2.5 text-[10px] bg-white border border-slate-200 text-slate-400 px-1.5 py-0.5 rounded font-mono font-bold">/</span>
                    </div>

                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
                        <button type="button" onclick="filterCat('', this)" class="cat-chip px-2.5 py-1 rounded-full font-bold bg-teal-600 text-white shrink-0">All</button>
                        <button type="button" onclick="filterCat('Tablet', this)" class="cat-chip px-2.5 py-1 rounded-full font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 shrink-0">Tablets</button>
                        <button type="button" onclick="filterCat('Syrup', this)" class="cat-chip px-2.5 py-1 rounded-full font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 shrink-0">Syrups</button>
                        <button type="button" onclick="filterCat('Capsule', this)" class="cat-chip px-2.5 py-1 rounded-full font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 shrink-0">Capsules</button>
                        <button type="button" onclick="filterCat('Powder', this)" class="cat-chip px-2.5 py-1 rounded-full font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 shrink-0">Powders</button>
                        <button type="button" onclick="filterCat('Injection', this)" class="cat-chip px-2.5 py-1 rounded-full font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 shrink-0">Injections</button>
                    </div>
                </div>

                <!-- Catalog Items List Container -->
                <div class="flex-1 overflow-y-auto p-2 divide-y divide-slate-100" id="catalog-list">
                    <!-- Injected via JavaScript -->
                </div>

                <!-- Catalog Footer -->
                <div class="p-2.5 border-t border-slate-200 bg-slate-50 flex items-center justify-between text-xs text-slate-500">
                    <span id="catalog-count-text">Loading catalog...</span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-slate-600 font-semibold">FEFO Batch Prioritized</span>
                    </span>
                </div>
            </div>

            <!-- RIGHT PANEL: POS Cart & Instant Checkout (5 Cols) -->
            <div class="lg:col-span-5 xl:col-span-5 flex flex-col h-full bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
                
                <!-- Cart Header & Customer Selector -->
                <div class="p-3 border-b border-slate-200 bg-slate-50 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 bg-teal-100 text-teal-800 rounded-lg">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6"/><path d="M16 12h-6"/><path d="M14 16h-4"/></svg>
                            </span>
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm">Active Sale Counter</h3>
                                <span class="text-[11px] text-teal-700 font-mono font-bold"><?= $next_inv ?></span>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <button type="button" onclick="clearCart()" class="text-xs px-2 py-1 rounded bg-slate-200 hover:bg-rose-100 hover:text-rose-700 font-semibold text-slate-600 transition-colors">
                                Clear Cart (Esc)
                            </button>
                        </div>
                    </div>

                    <!-- Customer & Doctor -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <select id="pos-customer" class="w-full border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-semibold focus:ring-2 focus:ring-teal-600">
                                <option value="0" data-name="Walk-in Customer" data-mobile="">👤 Walk-in Customer</option>
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?= $c['id'] ?>" data-name="<?= e($c['name']) ?>" data-mobile="<?= e($c['mobile']) ?>">
                                        <?= e($c['name']) ?> (<?= e($c['mobile'] ?: 'No Phone') ?>) • Due: <?= format_currency($c['current_balance']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <input type="text" id="pos-doctor" placeholder="Doctor / Clinic Name" class="w-full border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-teal-600">
                        </div>
                    </div>
                </div>

                <!-- Cart Items Table Scroll Area -->
                <div class="flex-1 overflow-y-auto" id="cart-container">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="sticky top-0 bg-slate-100 border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px] z-10">
                            <tr>
                                <th class="py-2 px-2.5">Medicine &amp; Batch</th>
                                <th class="py-2 px-2 text-center w-20">Qty</th>
                                <th class="py-2 px-2 text-right w-16">Price</th>
                                <th class="py-2 px-2 text-right w-14">Disc%</th>
                                <th class="py-2 px-2 text-right w-20">Total</th>
                                <th class="py-2 px-1 text-center w-6"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="cart-tbody">
                            <!-- Populated with Cart Rows -->
                        </tbody>
                    </table>

                    <div id="cart-empty" class="py-16 text-center text-slate-400">
                        <svg class="w-10 h-10 mx-auto mb-2 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                        <p class="font-bold text-slate-600 text-xs">Cart is Empty</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Click any item on the left to add it under FEFO queue.</p>
                    </div>
                </div>

                <!-- Cart Summary & Checkout -->
                <div class="p-3 bg-slate-50 border-t border-slate-200 space-y-2.5">
                    <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-slate-600">
                        <div class="flex justify-between">
                            <span>Taxable Subtotal:</span>
                            <span class="font-bold text-slate-800" id="disp-subtotal">₹0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span>GST Tax Total:</span>
                            <span class="font-bold text-slate-800" id="disp-gst">₹0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Discount Total:</span>
                            <span class="font-bold text-emerald-700" id="disp-discount">₹0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Round Off:</span>
                            <span class="font-mono text-slate-500" id="disp-round">₹0.00</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-2.5 rounded-lg bg-teal-50 border border-teal-200">
                        <div class="text-xs font-bold text-teal-900 uppercase">Payable Total:</div>
                        <div class="text-xl font-extrabold text-teal-800" id="disp-grand">₹0.00</div>
                    </div>

                    <!-- Payment Mode & Checkout Button -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                        <div class="sm:col-span-5">
                            <select id="pos-payment-mode" class="w-full border border-slate-300 rounded-lg px-2.5 py-2 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-teal-600">
                                <option value="Cash">💵 Cash</option>
                                <option value="UPI">📱 UPI / QR</option>
                                <option value="Card">💳 Credit/Debit Card</option>
                                <option value="Credit">📑 Credit (Patient Ledger)</option>
                            </select>
                        </div>
                        <div class="sm:col-span-7">
                            <button type="button" id="btn-submit-sale" onclick="submitSale()" 
                                    class="w-full py-2 px-4 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-extrabold text-sm flex items-center justify-center gap-1.5 shadow-sm transition-all disabled:opacity-50">
                                <span>Complete Sale (F8)</span>
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </main>
</div>

<!-- POS Interactive Engine -->
<script>
const CATALOG = <?= json_encode($catalog_medicines) ?>;
const BATCHES = <?= json_encode($batches_by_med) ?>;
const CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;

let cart = [];
let activeCategory = '';

function renderCatalog(items) {
    const container = document.getElementById('catalog-list');
    container.innerHTML = '';

    if (items.length === 0) {
        container.innerHTML = '<div class="py-12 text-center text-slate-400 text-xs">No medicines found matching search.</div>';
        document.getElementById('catalog-count-text').textContent = '0 medicines found';
        return;
    }

    document.getElementById('catalog-count-text').textContent = `${items.length} medicines available`;

    items.forEach(med => {
        const medBatches = BATCHES[med.id] || [];
        const totalStock = medBatches.reduce((acc, b) => acc + parseInt(b.quantity || 0), 0);
        const earliestBatch = medBatches[0]; // FEFO: first batch

        const row = document.createElement('div');
        row.className = `p-2.5 rounded-lg flex items-center justify-between hover:bg-slate-50 transition-colors cursor-pointer ${totalStock <= 0 ? 'opacity-40 pointer-events-none' : ''}`;
        
        let batchInfo = '<span class="text-rose-500 font-bold text-[10px]">Out of Stock</span>';
        if (earliestBatch) {
            batchInfo = `<span class="text-slate-500 text-[10px]">Batch: <strong class="text-slate-700">${earliestBatch.batch_number}</strong> (Exp: ${earliestBatch.expiry_date}) • Stock: <strong class="text-teal-700">${totalStock}</strong> ${med.unit}</span>`;
        }

        row.innerHTML = `
            <div class="min-w-0 flex-1 pr-3" onclick="addToCart(${med.id})">
                <div class="flex items-center gap-1.5">
                    <span class="font-bold text-slate-900 text-xs">${med.name}</span>
                    ${med.prescription_required == 1 ? '<span class="px-1 py-0.2 rounded bg-rose-50 text-rose-600 font-bold text-[9px] border border-rose-200">Rx</span>' : ''}
                </div>
                <div class="text-[11px] text-slate-500">${med.generic_name} • ${med.category_name || 'Medicine'}</div>
                <div class="mt-0.5">${batchInfo}</div>
            </div>
            <div class="text-right shrink-0" onclick="addToCart(${med.id})">
                <div class="font-extrabold text-teal-800 text-xs">₹${parseFloat(med.selling_price).toFixed(2)}</div>
                <div class="text-[10px] text-slate-400">MRP: ₹${parseFloat(med.mrp).toFixed(2)}</div>
                <button type="button" class="mt-1 px-2 py-0.5 rounded bg-teal-50 hover:bg-teal-600 hover:text-white text-teal-700 text-[10px] font-bold transition-colors">
                    + Add (FEFO)
                </button>
            </div>
        `;
        container.appendChild(row);
    });
}

function filterCatalog(term) {
    const q = term.toLowerCase().trim();
    const filtered = CATALOG.filter(m => {
        const matchesTerm = !q || m.name.toLowerCase().includes(q) || m.generic_name.toLowerCase().includes(q) || (m.barcode && m.barcode.includes(q));
        const matchesCat = !activeCategory || (m.category_name && m.category_name.toLowerCase().includes(activeCategory.toLowerCase()));
        return matchesTerm && matchesCat;
    });
    renderCatalog(filtered);
}

function filterCat(cat, btn) {
    activeCategory = cat;
    document.querySelectorAll('.cat-chip').forEach(b => {
        b.className = 'cat-chip px-2.5 py-1 rounded-full font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 shrink-0';
    });
    btn.className = 'cat-chip px-2.5 py-1 rounded-full font-bold bg-teal-600 text-white shrink-0';
    filterCatalog(document.getElementById('pos-search').value);
}

function addToCart(medId) {
    const med = CATALOG.find(m => m.id == medId);
    if (!med) return;

    const medBatches = BATCHES[med.id] || [];
    if (medBatches.length === 0) {
        showToast('No active in-stock batches available for this medicine.', 'error');
        return;
    }

    // FEFO Selection: first batch with stock
    const batch = medBatches[0];
    const existing = cart.find(c => c.medicine_id == med.id && c.batch_id == batch.id);

    if (existing) {
        if (existing.quantity + 1 > parseInt(batch.quantity)) {
            showToast(`Cannot add more! Only ${batch.quantity} units available in Batch ${batch.batch_number}.`, 'warning');
            return;
        }
        existing.quantity += 1;
    } else {
        cart.push({
            medicine_id: med.id,
            medicine_name: med.name,
            unit: med.unit,
            batch_id: batch.id,
            batch_number: batch.batch_number,
            expiry_date: batch.expiry_date,
            max_qty: parseInt(batch.quantity),
            quantity: 1,
            price: parseFloat(batch.selling_price || med.selling_price),
            gst_rate: parseFloat(med.gst_rate || 12),
            discount: 0
        });
    }

    renderCart();
}

function renderCart() {
    const tbody = document.getElementById('cart-tbody');
    const emptyState = document.getElementById('cart-empty');
    tbody.innerHTML = '';

    if (cart.length === 0) {
        emptyState.classList.remove('hidden');
        updateTotals();
        return;
    }

    emptyState.classList.add('hidden');

    cart.forEach((item, idx) => {
        const itemBase = item.price * item.quantity;
        const discAmt = itemBase * (item.discount / 100);
        const taxable = itemBase - discAmt;
        const gstAmt = taxable * (item.gst_rate / 100);
        const lineTotal = taxable + gstAmt;

        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-50/70';
        tr.innerHTML = `
            <td class="py-2 px-2.5">
                <div class="font-bold text-slate-900 text-xs">${item.medicine_name}</div>
                <div class="text-[10px] text-slate-500 font-mono">B: ${item.batch_number} (Exp: ${item.expiry_date})</div>
            </td>
            <td class="py-2 px-2 text-center">
                <div class="inline-flex items-center border border-slate-300 rounded">
                    <button type="button" onclick="updateQty(${idx}, -1)" class="px-1.5 py-0.5 text-slate-600 hover:bg-slate-100 font-bold">-</button>
                    <input type="number" min="1" max="${item.max_qty}" value="${item.quantity}" 
                           onchange="setQty(${idx}, this.value)" 
                           class="w-8 text-center text-xs font-bold border-x border-slate-300 py-0.5 focus:outline-none">
                    <button type="button" onclick="updateQty(${idx}, 1)" class="px-1.5 py-0.5 text-slate-600 hover:bg-slate-100 font-bold">+</button>
                </div>
            </td>
            <td class="py-2 px-2 text-right font-medium">₹${item.price.toFixed(2)}</td>
            <td class="py-2 px-2 text-right">
                <input type="number" min="0" max="100" value="${item.discount}" 
                       onchange="setDiscount(${idx}, this.value)" 
                       class="w-12 text-right text-xs border border-slate-300 rounded p-0.5 focus:outline-none">
            </td>
            <td class="py-2 px-2 text-right font-extrabold text-slate-900">₹${lineTotal.toFixed(2)}</td>
            <td class="py-2 px-1 text-center">
                <button type="button" onclick="removeFromCart(${idx})" class="text-slate-400 hover:text-rose-600 p-1">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    updateTotals();
}

function updateQty(idx, delta) {
    if (!cart[idx]) return;
    const newQty = cart[idx].quantity + delta;
    if (newQty <= 0) {
        removeFromCart(idx);
    } else if (newQty > cart[idx].max_qty) {
        showToast(`Stock limit reached (${cart[idx].max_qty} units available).`, 'warning');
    } else {
        cart[idx].quantity = newQty;
        renderCart();
    }
}

function setQty(idx, val) {
    const qty = parseInt(val) || 1;
    if (qty > cart[idx].max_qty) {
        showToast(`Stock limit reached (${cart[idx].max_qty} units available).`, 'warning');
        cart[idx].quantity = cart[idx].max_qty;
    } else {
        cart[idx].quantity = Math.max(1, qty);
    }
    renderCart();
}

function setDiscount(idx, val) {
    cart[idx].discount = Math.min(100, Math.max(0, parseFloat(val) || 0));
    renderCart();
}

function removeFromCart(idx) {
    cart.splice(idx, 1);
    renderCart();
}

function clearCart() {
    if (cart.length > 0 && confirm('Clear all items from current cart?')) {
        cart = [];
        renderCart();
    }
}

function updateTotals() {
    let subtotal = 0;
    let totalGst = 0;
    let totalDiscount = 0;

    cart.forEach(item => {
        const itemBase = item.price * item.quantity;
        const discAmt = itemBase * (item.discount / 100);
        const taxable = itemBase - discAmt;
        const gstAmt = taxable * (item.gst_rate / 100);

        subtotal += taxable;
        totalGst += gstAmt;
        totalDiscount += discAmt;
    });

    const rawGrand = subtotal + totalGst;
    const roundOff = Math.round(rawGrand) - rawGrand;
    const grandTotal = Math.round(rawGrand);

    document.getElementById('disp-subtotal').textContent = '₹' + subtotal.toFixed(2);
    document.getElementById('disp-gst').textContent = '₹' + totalGst.toFixed(2);
    document.getElementById('disp-discount').textContent = '₹' + totalDiscount.toFixed(2);
    document.getElementById('disp-round').textContent = (roundOff >= 0 ? '+' : '') + '₹' + roundOff.toFixed(2);
    document.getElementById('disp-grand').textContent = '₹' + grandTotal.toFixed(2);
}

async function submitSale() {
    if (cart.length === 0) {
        showToast('Cart is empty. Please add items to checkout.', 'error');
        return;
    }

    const btn = document.getElementById('btn-submit-sale');
    btn.disabled = true;
    btn.innerHTML = '<span>Processing Transaction...</span>';

    const custSelect = document.getElementById('pos-customer');
    const selectedCustOpt = custSelect.selectedOptions[0];
    const customerId = custSelect.value != '0' ? custSelect.value : null;
    const customerName = selectedCustOpt.dataset.name || 'Walk-in Customer';
    const customerMobile = selectedCustOpt.dataset.mobile || '';
    const doctorName = document.getElementById('pos-doctor').value;
    const paymentMethod = document.getElementById('pos-payment-mode').value;

    const payload = {
        csrf_token: CSRF_TOKEN,
        customer_id: customerId,
        customer_name: customerName,
        customer_mobile: customerMobile,
        doctor_name: doctorName,
        payment_method: paymentMethod,
        items: cart
    };

    try {
        const response = await fetch('save-sale.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();
        if (result.success) {
            showToast('Sale completed! Redirecting to tax invoice...', 'success');
            setTimeout(() => {
                window.location.href = result.invoice_url;
            }, 500);
        } else {
            showToast('Sale failed: ' + result.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<span>Complete Sale (F8)</span>';
        }
    } catch (err) {
        showToast('Network error processing sale: ' + err.message, 'error');
        btn.disabled = false;
        btn.innerHTML = '<span>Complete Sale (F8)</span>';
    }
}

// Global Keyboard Shortcuts
document.addEventListener('keydown', (e) => {
    if (e.key === 'F8') {
        e.preventDefault();
        submitSale();
    }
    if (e.key === 'Escape') {
        clearCart();
    }
    if (e.key === 'F4') {
        e.preventDefault();
        document.getElementById('pos-customer').focus();
    }
});

// Initial boot
document.addEventListener('DOMContentLoaded', () => {
    renderCatalog(CATALOG);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
