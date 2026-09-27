# MY HOSPITAL PRO (MHP)
### Hospital Pharmacy & Medical Store Management System — Phase 1

**Domain**: `myhospitalpro.in`  
**Stack**: PHP 8+ | MySQL 8+ | Tailwind CSS | Vanilla JavaScript | Chart.js

---

## 1. System Overview
My Hospital Pro (MHP) is a full-featured, responsive pharmacy and medical store management application engineered with strict Indian GST compliance, FEFO (First-Expiry-First-Out) batch inventory tracking, split-screen POS billing with rapid keyboard shortcuts, and accounts receivable/payable ledgers.

---

## 2. Directory Structure

```text
myhospitalpro.in/
├── config.php                  # Database credentials & hospital settings
├── index.php                   # Root application entry & login router
├── login.php                   # User login authentication handler
├── logout.php                  # Secure session logout & audit logger
├── dashboard.php               # Analytical KPI dashboard & Chart.js sales feed
├── database/
│   └── mhp_database.sql        # Complete MySQL database schema & seed dataset
├── includes/
│   ├── app.php                 # Core bootstrap loader
│   ├── database.php            # Singleton PDO database connector
│   ├── auth.php                # Authentication & role-based permission gates
│   ├── session.php             # Hardened session security & timeout
│   ├── csrf.php                # CSRF token generation & validation
│   ├── flash.php               # Toast notification message queue
│   ├── functions.php           # Formatting, sequence generators & audit logs
│   ├── header.php              # Global HTML head & theme engine
│   ├── sidebar.php             # Module navigation & badge counters
│   ├── navbar.php              # Quick actions, search & user profile dropdown
│   └── footer.php              # Global scripts & modal wrappers
├── auth/
│   ├── login.php               # Login redirect handler
│   └── logout.php              # Logout redirect handler
├── medicines/
│   ├── index.php               # Medicine catalogue with filter & pagination
│   ├── add.php                 # Add new medicine form
│   ├── edit.php                # Edit medicine details
│   ├── view.php                # Medicine detail view & batch inventory
│   └── delete.php              # Soft delete medicine handler
├── purchases/
│   ├── index.php               # Purchase order & invoice records
│   ├── create.php              # Multi-item procurement entry with batch creation
│   ├── view.php                # Detailed purchase invoice view
│   └── cancel.php              # Safe cancellation with stock validation
├── inventory/
│   ├── index.php               # Master inventory overview & stock health
│   ├── batches.php             # Batch-level stock tracker
│   ├── low-stock.php           # Low stock warnings & reorder quantities
│   ├── expiry.php              # Expired & near-expiry quarantine tracker
│   └── stock-adjustment.php    # Physical count reconciliation & audit
├── billing/
│   ├── index.php               # High-speed POS split-screen counter
│   ├── create.php              # New bill router
│   ├── save-sale.php           # Atomic transactional sale processor (FEFO + Stock Lock)
│   └── invoice.php             # Printable GST tax invoice with thermal switch
├── customers/
│   ├── index.php               # Patient / Customer directory & outstanding balances
│   ├── add.php                 # Register customer form
│   ├── edit.php                # Update customer profile
│   ├── view.php                # Customer ledger & purchase history
│   └── payment.php             # Credit settlement & receipt voucher
├── suppliers/
│   ├── index.php               # Distributor & vendor directory
│   ├── add.php                 # Register supplier form
│   ├── edit.php                # Update supplier profile
│   ├── view.php                # Vendor purchase history & statement
│   └── payment.php             # Accounts payable payment voucher
├── reports/
│   ├── index.php               # Financial reports hub & executive KPI summary
│   ├── sales.php               # Sales ledger with payment mode filter & CSV export
│   ├── purchases.php           # Procurement ledger & vendor liability breakdown
│   ├── inventory.php           # Batch-level asset valuation (Cost vs MRP)
│   └── profit.php              # True batch-level gross profit & margin analysis
├── api/
│   ├── medicines.php           # FEFO medicine search & batch detail API
│   ├── customers.php           # Customer lookup API
│   ├── suppliers.php           # Supplier lookup API
│   ├── inventory.php           # Live stock & alert counts API
│   ├── purchases.php           # Purchase order query API
│   ├── sales.php               # Recent sales & 7-day revenue chart API
│   └── dashboard.php           # Real-time dashboard KPI endpoint
└── install/
    ├── index.php               # Web-based installation wizard
    └── README.md               # Quick installer instructions
```

---

## 3. Quick Run & Credentials

### Start Application
```bash
php -S localhost:8000
```

### Access Application
- **URL**: [http://localhost:8000](http://localhost:8000)
- **Default Username**: `admin`
- **Default Password**: `admin123`

---

## 4. Key Business Logic Features
- **FEFO Batch Management**: Batches sorted automatically by earliest expiration date (`expiry_date ASC`).
- **Concurrent Stock Locking**: POS billing employs `SELECT ... FOR UPDATE` row locks inside atomic database transactions to eliminate race conditions and negative inventory.
- **GST Tax Compliance**: Built-in 0%, 5%, 12%, 18%, 28% tax rates with automatic split CGST + SGST calculations.
- **Credit Limit & Balances**: Automated customer credit verification prevents sales exceeding assigned credit limits.
- **Keyboard Shortcuts**: POS supports `/` (focus search), `F2` (new bill), `F4` (checkout), `F8` (print invoice), `Esc` (close modals).
- **Theme Customization**: 4 production themes supported natively (`default`, `emerald`, `royal`, `warm`).
