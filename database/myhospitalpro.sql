-- =====================================================================
-- MY HOSPITAL PRO (MHP) — PHASE 1 COMPLETE MYSQL DATABASE SCHEMA & SEED
-- Database Engine: InnoDB, Charset: utf8mb4, Collation: utf8mb4_unicode_ci
-- Compatible with MySQL 8.0+ and MariaDB 10.4+
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `myhospitalpro` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `myhospitalpro`;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 1. ROLES TABLE
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;

CREATE TABLE `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `display_name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `name`, `display_name`, `description`) VALUES
(1, 'admin', 'Super Administrator', 'Full unrestricted access to all modules, settings, and logs.'),
(2, 'manager', 'Pharmacy Manager', 'Manages medicines, inward purchases, inventory stock, billing, and reports.'),
(3, 'pharmacist', 'Dispensing Pharmacist', 'Manages medicine formulary, stock adjustments, expiry, and counter billing.'),
(4, 'billing_staff', 'Counter Billing Staff', 'Point of sale counter billing, customer registrations, and receipt printing.');

-- ---------------------------------------------------------------------
-- 2. PERMISSIONS TABLE
-- ---------------------------------------------------------------------
CREATE TABLE `permissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL UNIQUE,
  `module` VARCHAR(50) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`id`, `name`, `module`, `description`) VALUES
(1, 'dashboard.view', 'dashboard', 'View executive dashboard KPIs & charts'),
(2, 'medicines.view', 'medicines', 'View medicine list & batch details'),
(3, 'medicines.create', 'medicines', 'Register new medicines into formulary'),
(4, 'medicines.edit', 'medicines', 'Edit medicine details & rack numbers'),
(5, 'medicines.delete', 'medicines', 'Delete/deactivate medicines'),
(6, 'purchases.view', 'purchases', 'View vendor purchase bills'),
(7, 'purchases.create', 'purchases', 'Create inward consignment bills'),
(8, 'inventory.view', 'inventory', 'View inventory balances and valuation'),
(9, 'inventory.adjust', 'inventory', 'Perform stock adjustments & reconciliations'),
(10, 'billing.create', 'billing', 'Create sales and operate POS terminal'),
(11, 'customers.manage', 'customers', 'Manage patient registry & credit ledger'),
(12, 'suppliers.manage', 'suppliers', 'Manage distributor directory & payables'),
(13, 'reports.view', 'reports', 'View sales, procurement, and profit reports'),
(14, 'settings.manage', 'settings', 'Manage hospital branding and configuration');

-- ---------------------------------------------------------------------
-- 3. ROLE PERMISSIONS MAPPING
-- ---------------------------------------------------------------------
CREATE TABLE `role_permissions` (
  `role_id` INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin: all permissions (1-14)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions`;

-- Manager: 1-13
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM `permissions` WHERE id <= 13;

-- Pharmacist: 1, 2, 3, 4, 8, 9, 10, 11
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
VALUES (3, 1), (3, 2), (3, 3), (3, 4), (3, 8), (3, 9), (3, 10), (3, 11);

-- Billing Staff: 1, 8, 10, 11
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
VALUES (4, 1), (4, 8), (4, 10), (4, 11);

-- ---------------------------------------------------------------------
-- 4. USERS TABLE
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_users_role` (`role_id`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password for all seed users is 'admin123'
-- Hash generated via password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO `users` (`id`, `name`, `username`, `email`, `password`, `role_id`, `phone`, `status`, `last_login`) VALUES
(1, 'Dr. Arvind Sharma', 'admin', 'admin@myhospitalpro.in', '$2y$10$MM7XVKFItjoCoMfRvqGnb./hLPKDkga/9KehZ076wNe7G.cOrwJCy', 1, '+91 98765 43210', 'active', NOW()),
(2, 'Prakash Mehra', 'manager', 'manager@myhospitalpro.in', '$2y$10$MM7XVKFItjoCoMfRvqGnb./hLPKDkga/9KehZ076wNe7G.cOrwJCy', 2, '+91 98111 22233', 'active', NOW()),
(3, 'Dr. Sunita Rao', 'pharmacist', 'pharmacist@myhospitalpro.in', '$2y$10$MM7XVKFItjoCoMfRvqGnb./hLPKDkga/9KehZ076wNe7G.cOrwJCy', 3, '+91 98222 33344', 'active', NOW()),
(4, 'Kavita Verma', 'cashier', 'billing@myhospitalpro.in', '$2y$10$MM7XVKFItjoCoMfRvqGnb./hLPKDkga/9KehZ076wNe7G.cOrwJCy', 4, '+91 98333 44455', 'active', NOW());

-- ---------------------------------------------------------------------
-- 5. SETTINGS TABLE (White-Label & Hospital Configuration)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(80) NOT NULL UNIQUE,
  `setting_value` TEXT DEFAULT NULL,
  `group_name` VARCHAR(50) NOT NULL DEFAULT 'general',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`setting_key`, `setting_value`, `group_name`) VALUES
('hospital_name', 'My Hospital Pro', 'branding'),
('hospital_short_name', 'MHP', 'branding'),
('hospital_legal_name', 'My Hospital Pro Super Specialty Hospital & Pharmacy', 'hospital'),
('store_name', 'MHP Central In-House Pharmacy', 'hospital'),
('tagline', 'Manage. Track. Grow.', 'branding'),
('theme', 'default', 'branding'),
('logo_url', 'assets/images/logo.svg', 'branding'),
('favicon_url', 'assets/images/favicon.svg', 'branding'),
('currency', 'INR', 'financial'),
('currency_symbol', '₹', 'financial'),
('address', 'Plot 42, Health City Boulevard, Sector 9, Rohini', 'hospital'),
('city', 'New Delhi', 'hospital'),
('state', 'Delhi', 'hospital'),
('pincode', '110085', 'hospital'),
('phone', '+91 98765 43210 / 011-27891234', 'hospital'),
('email', 'pharmacy@myhospitalpro.in', 'hospital'),
('website', 'https://myhospitalpro.in', 'hospital'),
('gstin', '07AAAAA0000A1Z5', 'taxation'),
('drug_license', 'DL-20B-883491 / 21B-883492', 'regulatory'),
('fssai_number', '13322001000492', 'regulatory'),
('invoice_prefix', 'MHP', 'billing'),
('purchase_prefix', 'PUR', 'billing'),
('invoice_footer_terms', 'Goods once sold can only be returned within 7 days against original tax invoice. Store medicines below 25°C away from direct sunlight.', 'billing');

-- ---------------------------------------------------------------------
-- 6. MEDICINE CATEGORIES & MANUFACTURERS
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `medicines`;
DROP TABLE IF EXISTS `medicine_categories`;
DROP TABLE IF EXISTS `manufacturers`;

CREATE TABLE `medicine_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL UNIQUE,
  `description` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `medicine_categories` (`id`, `name`, `description`) VALUES
(1, 'Tablet', 'Solid oral dosage compressed tablets'),
(2, 'Capsule', 'Gelatin encased capsules'),
(3, 'Syrup / Liquid', 'Oral liquid suspensions and syrups'),
(4, 'Powder / Sachet', 'Oral soluble electrolyte rehydration powders'),
(5, 'Injection', 'Parenteral intravenous or intramuscular injectables'),
(6, 'Ointment / Gel', 'Topical analgesic and anti-inflammatory ointments'),
(7, 'Inhaler', 'Respiratory bronchodilator metered dose inhalers'),
(8, 'Eye / Ear Drops', 'Sterile ophthalmic and otic solution drops');

CREATE TABLE `manufacturers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL UNIQUE,
  `contact_person` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `manufacturers` (`id`, `name`, `contact_person`, `phone`, `email`, `address`) VALUES
(1, 'GlaxoSmithKline Pharmaceuticals', 'Vivek Taneja', '+91 22 2495 9000', 'india.orders@gsk.com', 'Dr. Annie Besant Road, Worli, Mumbai'),
(2, 'Cipla Ltd', 'Anand Kulkarni', '+91 22 2482 6000', 'contactus@cipla.com', 'Cipla House, Peninsula Business Park, Lower Parel, Mumbai'),
(3, 'Alkem Laboratories', 'S. N. Singh', '+91 22 3982 9999', 'orders@alkem.com', 'Alkem House, Senapati Bapat Marg, Lower Parel, Mumbai'),
(4, 'Dr. Reddy\'s Laboratories', 'Raghavan Pillai', '+91 40 4900 2900', 'support@drreddys.com', 'Ameerpet, Hyderabad, Telangana'),
(5, 'FDC Limited', 'B. M. Sharma', '+91 22 2673 9100', 'fdc@fdcindia.com', '142-48 S.V. Road, Jogeshwari West, Mumbai'),
(6, 'Cadila Pharmaceuticals', 'Manoj Patel', '+91 2717 666 555', 'cadila@cadilapharma.co.in', 'Sarkhej-Dholka Road, Bhat, Ahmedabad'),
(7, 'USV Private Limited', 'D. K. Jain', '+91 22 2556 4061', 'usv@usvindia.com', 'B.S.D. Marg, Govandi, Mumbai'),
(8, 'Zydus Lifesciences', 'Hiren Vora', '+91 79 7180 0000', 'zydus@zyduslife.com', 'Zydus Corporate Park, SG Highway, Ahmedabad'),
(9, 'Glenmark Pharmaceuticals', 'Rajiv Mehta', '+91 22 4018 9999', 'glenmark@glenmarkpharma.com', 'Bandra Kurla Complex, Mumbai');

-- ---------------------------------------------------------------------
-- 7. MEDICINE MASTER TABLE
-- ---------------------------------------------------------------------
CREATE TABLE `medicines` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `medicine_code` VARCHAR(30) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `generic_name` VARCHAR(255) DEFAULT NULL,
  `brand_name` VARCHAR(100) DEFAULT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `manufacturer_id` INT UNSIGNED DEFAULT NULL,
  `dosage_form` VARCHAR(50) DEFAULT 'Tablet',
  `strength` VARCHAR(50) DEFAULT NULL,
  `pack_size` VARCHAR(50) DEFAULT '10 Tablets / Strip',
  `unit` VARCHAR(30) DEFAULT 'Strip',
  `hsn_code` VARCHAR(20) DEFAULT '30049099',
  `gst_rate` DECIMAL(5,2) NOT NULL DEFAULT 12.00,
  `mrp` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `selling_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `purchase_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `reorder_level` INT NOT NULL DEFAULT 20,
  `minimum_stock` INT NOT NULL DEFAULT 10,
  `maximum_stock` INT NOT NULL DEFAULT 500,
  `rack_number` VARCHAR(30) DEFAULT NULL,
  `barcode` VARCHAR(50) DEFAULT NULL,
  `prescription_required` TINYINT(1) NOT NULL DEFAULT 0,
  `schedule_type` ENUM('General (OTC)', 'Schedule H', 'Schedule H1', 'Schedule X', 'Schedule G') NOT NULL DEFAULT 'General (OTC)',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_med_name` (`name`),
  KEY `idx_med_generic` (`generic_name`),
  KEY `idx_med_barcode` (`barcode`),
  KEY `idx_med_category` (`category_id`),
  KEY `idx_med_manufacturer` (`manufacturer_id`),
  CONSTRAINT `fk_med_category` FOREIGN KEY (`category_id`) REFERENCES `medicine_categories` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_med_manufacturer` FOREIGN KEY (`manufacturer_id`) REFERENCES `manufacturers` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `medicines` (`id`, `medicine_code`, `name`, `generic_name`, `brand_name`, `category_id`, `manufacturer_id`, `dosage_form`, `strength`, `pack_size`, `unit`, `hsn_code`, `gst_rate`, `mrp`, `selling_price`, `purchase_price`, `reorder_level`, `minimum_stock`, `maximum_stock`, `rack_number`, `barcode`, `prescription_required`, `schedule_type`, `status`) VALUES
(1, 'MED-0001', 'Paracetamol 500mg', 'Paracetamol / Acetaminophen', 'Crocin Advance', 1, 1, 'Tablet', '500 mg', '15 Tablets / Strip', 'Strip', '30049060', 12.00, 32.50, 30.00, 21.00, 50, 20, 500, 'A-12-04', '8901234567890', 0, 'General (OTC)', 'active'),
(2, 'MED-0002', 'Azithromycin 500mg', 'Azithromycin Dihydrate', 'Azee 500', 1, 2, 'Tablet', '500 mg', '5 Tablets / Strip', 'Strip', '30042010', 12.00, 119.50, 110.00, 78.00, 25, 10, 200, 'B-03-01', '8901234567891', 1, 'Schedule H1', 'active'),
(3, 'MED-0003', 'Amoxicillin & Potassium Clavulanate 625mg', 'Amoxicillin (500mg) + Clavulanic Acid (125mg)', 'Augmentin 625 Duo', 1, 1, 'Tablet', '625 mg', '10 Tablets / Strip', 'Strip', '30041090', 12.00, 205.00, 195.00, 142.00, 30, 15, 300, 'B-03-02', '8901234567892', 1, 'Schedule H1', 'active'),
(4, 'MED-0004', 'Pantoprazole Gastro-Resistant 40mg', 'Pantoprazole Sodium', 'Pan 40', 1, 3, 'Tablet', '40 mg', '15 Tablets / Strip', 'Strip', '30049099', 12.00, 155.00, 145.00, 98.00, 40, 25, 400, 'A-08-02', '8901234567893', 0, 'Schedule H', 'active'),
(5, 'MED-0005', 'Cetirizine Hydrochloride 10mg', 'Cetirizine HCl', 'Cetzine 10', 1, 4, 'Tablet', '10 mg', '10 Tablets / Strip', 'Strip', '30049099', 12.00, 22.00, 20.00, 13.50, 50, 30, 500, 'A-05-01', '8901234567894', 0, 'General (OTC)', 'active'),
(6, 'MED-0006', 'ORS Electrolyte Powder 21.8g', 'Oral Rehydration Salts (WHO Formula)', 'Electral Powder', 4, 5, 'Sachet', '21.8 g', '1 Sachet', 'Sachet', '30049011', 5.00, 23.50, 22.00, 16.00, 80, 50, 1000, 'C-01-01', '8901234567895', 0, 'General (OTC)', 'active'),
(7, 'MED-0007', 'Cholecalciferol (Vitamin D3) 60,000 IU', 'Cholecalciferol', 'Calcirol 60K Granules', 4, 6, 'Sachet', '60000 IU / 1g', '4 Sachets / Pack', 'Pack', '30045020', 12.00, 180.00, 170.00, 115.00, 35, 20, 250, 'A-02-04', '8901234567896', 0, 'General (OTC)', 'active'),
(8, 'MED-0008', 'Metformin Hydrochloride 500mg', 'Metformin HCl', 'Glycomet 500', 1, 7, 'Tablet', '500 mg', '20 Tablets / Strip', 'Strip', '30049099', 12.00, 44.00, 40.00, 28.00, 60, 40, 600, 'D-04-01', '8901234567897', 1, 'Schedule H', 'active'),
(9, 'MED-0009', 'Atorvastatin 10mg', 'Atorvastatin Calcium', 'Atorva 10', 1, 8, 'Tablet', '10 mg', '15 Tablets / Strip', 'Strip', '30049099', 12.00, 98.00, 90.00, 62.00, 30, 20, 300, 'D-04-02', '8901234567898', 1, 'Schedule H', 'active'),
(10, 'MED-0010', 'Telmisartan 40mg', 'Telmisartan', 'Telma 40', 1, 9, 'Tablet', '40 mg', '30 Tablets / Strip', 'Strip', '30049099', 12.00, 220.00, 205.00, 145.00, 25, 15, 250, 'D-05-01', '8901234567899', 1, 'Schedule H', 'active'),
(11, 'MED-0011', 'Montelukast 10mg & Levocetirizine 5mg', 'Montelukast + Levocetirizine', 'Montair LC', 1, 2, 'Tablet', '10mg + 5mg', '15 Tablets / Strip', 'Strip', '30049099', 12.00, 310.00, 290.00, 210.00, 30, 20, 300, 'A-06-03', '8901234567900', 1, 'Schedule H', 'active'),
(12, 'MED-0012', 'Ascoril LS Syrup 100ml', 'Ambroxol + Levosalbutamol + Guaiphenesin', 'Ascoril LS', 3, 9, 'Syrup', '100 ml', '1 Bottle', 'Bottle', '30049099', 12.00, 124.00, 118.00, 84.00, 25, 15, 200, 'S-02-01', '8901234567901', 0, 'Schedule H', 'active');

-- ---------------------------------------------------------------------
-- 8. MEDICINE BATCHES TABLE (Physical Inventory Entity)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `medicine_batches`;
CREATE TABLE `medicine_batches` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `medicine_id` INT UNSIGNED NOT NULL,
  `batch_number` VARCHAR(50) NOT NULL,
  `expiry_date` DATE NOT NULL,
  `purchase_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `mrp` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `selling_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `quantity` INT NOT NULL DEFAULT 0,
  `free_quantity` INT NOT NULL DEFAULT 0,
  `status` ENUM('active', 'quarantined', 'depleted') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_med_batch` (`medicine_id`, `batch_number`),
  KEY `idx_batch_expiry` (`expiry_date`),
  KEY `idx_batch_qty` (`quantity`),
  CONSTRAINT `fk_batch_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `medicine_batches` (`id`, `medicine_id`, `batch_number`, `expiry_date`, `purchase_price`, `mrp`, `selling_price`, `quantity`, `free_quantity`, `status`) VALUES
(101, 1, 'PCM26A', '2027-04-30', 21.00, 32.50, 30.00, 180, 0, 'active'),
(102, 1, 'PCM25X', '2026-10-15', 20.50, 32.00, 30.00, 24, 0, 'active'), -- Near expiry ~18d
(103, 2, 'AZI2609', '2027-08-31', 78.00, 119.50, 110.00, 65, 0, 'active'),
(104, 2, 'AZI2511', '2026-11-20', 76.50, 118.00, 110.00, 12, 0, 'active'), -- Near expiry ~54d
(105, 3, 'AUG26D', '2027-06-30', 142.00, 205.00, 195.00, 90, 0, 'active'),
(106, 4, 'PAN26E', '2027-11-30', 98.00, 155.00, 145.00, 120, 0, 'active'),
(107, 5, 'CET26A', '2028-02-28', 13.50, 22.00, 20.00, 210, 0, 'active'),
(108, 6, 'ELE2602', '2027-10-31', 16.00, 23.50, 22.00, 15, 0, 'active'), -- Low Stock (15 < 80)
(109, 7, 'CAL26G', '2027-05-31', 115.00, 180.00, 170.00, 45, 0, 'active'),
(110, 8, 'GLY26K', '2027-09-30', 28.00, 44.00, 40.00, 8, 0, 'active'),  -- Low Stock (8 < 60)
(111, 9, 'ATO26M', '2027-12-31', 62.00, 98.00, 90.00, 50, 0, 'active'),
(112, 9, 'ATO24Old', '2026-08-30', 60.00, 95.00, 90.00, 6, 0, 'quarantined'), -- Expired
(113, 10, 'TEL26P', '2027-07-31', 145.00, 220.00, 205.00, 42, 0, 'active'),
(114, 11, 'MNT26Q', '2028-01-31', 210.00, 310.00, 290.00, 0, 0, 'depleted'), -- Out of stock
(115, 12, 'ASC26B', '2027-03-31', 84.00, 124.00, 118.00, 38, 0, 'active');

-- ---------------------------------------------------------------------
-- 9. SUPPLIERS & DISTRIBUTORS TABLE
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `supplier_payments`;
DROP TABLE IF EXISTS `purchase_items`;
DROP TABLE IF EXISTS `purchases`;
DROP TABLE IF EXISTS `suppliers`;

CREATE TABLE `suppliers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_code` VARCHAR(30) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `company_name` VARCHAR(150) DEFAULT NULL,
  `contact_person` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(80) DEFAULT 'Delhi',
  `state` VARCHAR(80) DEFAULT 'Delhi',
  `pincode` VARCHAR(20) DEFAULT '110006',
  `gstin` VARCHAR(30) NOT NULL,
  `drug_license` VARCHAR(80) DEFAULT NULL,
  `payment_terms` VARCHAR(50) DEFAULT '30 Days Net',
  `opening_balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `current_balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_supp_name` (`name`),
  KEY `idx_supp_phone` (`phone`),
  KEY `idx_supp_gstin` (`gstin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `suppliers` (`id`, `supplier_code`, `name`, `company_name`, `contact_person`, `phone`, `email`, `address`, `city`, `state`, `pincode`, `gstin`, `drug_license`, `payment_terms`, `opening_balance`, `current_balance`, `status`) VALUES
(1, 'SUPP-0001', 'Apollo Healthcare Distributors Pvt Ltd', 'Apollo Health Logistics Division', 'Sanjay Singhal', '+91 98112 34567', 'orders@apollodist.in', 'Building 14, Okhla Industrial Area Phase 3', 'New Delhi', 'Delhi', '110020', '07AAACA1234F1Z8', 'DL-20B-112233 / 21B-112234', '30 Days Net', 0.00, 14200.00, 'active'),
(2, 'SUPP-0002', 'MedPlus Logistics & Supply', 'MedPlus Regional Depot', 'Rameshwar Rao', '+91 97234 56789', 'delhi.depot@medpluslog.com', 'Plot 88, Udyog Vihar Phase 4', 'Gurugram', 'Haryana', '122015', '06AABCM5678K1Z2', 'HR-20B-445566 / 21B-445567', '15 Days Net', 0.00, 0.00, 'active'),
(3, 'SUPP-0003', 'Cipla Trade Distribution Agency', 'Cipla Authorized Stockist', 'Harish Chawla', '+91 98990 11223', 'cipla.stockist@chawlamedical.in', '24, Bhagirath Palace, Chandni Chowk', 'Delhi', 'Delhi', '110006', '07AAAC7890D1Z1', 'DL-20B-778899 / 21B-778800', '21 Days Net', 0.00, 18650.00, 'active'),
(4, 'SUPP-0004', 'Vardhman Medical Agency', 'Vardhman Pharma Network', 'Vikas Jain', '+91 93122 88441', 'vikas@vardhmanpharma.com', 'Shop 12-14, Dawa Bazaar, Karol Bagh', 'New Delhi', 'Delhi', '110005', '07AABFV4512H1ZQ', 'DL-20B-998811 / 21B-998822', 'Immediate / Cash', 0.00, 0.00, 'active');

-- ---------------------------------------------------------------------
-- 10. PURCHASES & INWARD CONSIGNMENTS
-- ---------------------------------------------------------------------
CREATE TABLE `purchases` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_number` VARCHAR(50) NOT NULL UNIQUE,
  `invoice_number` VARCHAR(80) NOT NULL,
  `supplier_id` INT UNSIGNED NOT NULL,
  `invoice_date` DATE NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `gst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `round_off` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `grand_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `due_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_type` ENUM('Credit', 'Cash', 'Bank Transfer / NEFT', 'Cheque', 'UPI') NOT NULL DEFAULT 'Credit',
  `payment_status` ENUM('Paid', 'Pending', 'Partial') NOT NULL DEFAULT 'Pending',
  `notes` TEXT DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_purchases_supplier` (`supplier_id`),
  KEY `idx_purchases_date` (`invoice_date`),
  KEY `idx_purchases_inv` (`invoice_number`),
  CONSTRAINT `fk_purchases_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_purchases_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `purchases` (`id`, `purchase_number`, `invoice_number`, `supplier_id`, `invoice_date`, `subtotal`, `discount`, `gst_amount`, `round_off`, `grand_total`, `paid_amount`, `due_amount`, `payment_type`, `payment_status`, `notes`, `created_by`) VALUES
(1, 'PUR-2026-0001', 'PUR-2026-0891', 1, '2026-09-22', 16400.00, 500.00, 1908.00, 0.00, 17808.00, 3608.00, 14200.00, 'Credit', 'Pending', 'Consignment received in verified sealed boxes.', 1),
(2, 'PUR-2026-0002', 'PUR-2026-0870', 3, '2026-09-18', 21450.00, 800.00, 2478.00, 0.00, 23128.00, 4478.00, 18650.00, 'Bank Transfer / NEFT', 'Pending', 'Cipla direct supply inward consignments.', 1),
(3, 'PUR-2026-0003', 'PUR-2026-0842', 2, '2026-09-10', 18420.00, 420.00, 2160.00, 0.00, 20160.00, 20160.00, 0.00, 'Cheque', 'Paid', 'Cleared via HDFC Bank Cheque #881290.', 1);

CREATE TABLE `purchase_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_id` INT UNSIGNED NOT NULL,
  `medicine_id` INT UNSIGNED NOT NULL,
  `batch_id` INT UNSIGNED DEFAULT NULL,
  `batch_number` VARCHAR(50) NOT NULL,
  `expiry_date` DATE NOT NULL,
  `quantity` INT NOT NULL,
  `free_quantity` INT NOT NULL DEFAULT 0,
  `purchase_price` DECIMAL(10,2) NOT NULL,
  `mrp` DECIMAL(10,2) NOT NULL,
  `gst_rate` DECIMAL(5,2) NOT NULL DEFAULT 12.00,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pi_purchase` (`purchase_id`),
  KEY `idx_pi_medicine` (`medicine_id`),
  KEY `idx_pi_batch` (`batch_id`),
  CONSTRAINT `fk_pi_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pi_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `purchase_items` (`id`, `purchase_id`, `medicine_id`, `batch_id`, `batch_number`, `expiry_date`, `quantity`, `free_quantity`, `purchase_price`, `mrp`, `gst_rate`, `discount`, `total`) VALUES
(1, 1, 1, 101, 'PCM26A', '2027-04-30', 200, 20, 21.00, 32.50, 12.00, 0.00, 4200.00),
(2, 1, 4, 106, 'PAN26E', '2027-11-30', 100, 0, 98.00, 155.00, 12.00, 5.00, 9310.00),
(3, 2, 2, 103, 'AZI2609', '2027-08-31', 80, 5, 78.00, 119.50, 12.00, 0.00, 6240.00),
(4, 2, 3, 105, 'AUG26D', '2027-06-30', 100, 0, 142.00, 205.00, 12.00, 0.00, 14200.00),
(5, 3, 5, 107, 'CET26A', '2028-02-28', 250, 25, 13.50, 22.00, 12.00, 0.00, 3375.00);

-- ---------------------------------------------------------------------
-- 11. SUPPLIER PAYMENTS TABLE
-- ---------------------------------------------------------------------
CREATE TABLE `supplier_payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_id` INT UNSIGNED NOT NULL,
  `payment_number` VARCHAR(50) NOT NULL UNIQUE,
  `payment_date` DATE NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('Cash', 'Bank Transfer / NEFT', 'Cheque', 'UPI') NOT NULL DEFAULT 'Bank Transfer / NEFT',
  `reference_number` VARCHAR(100) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sp_supplier` (`supplier_id`),
  CONSTRAINT `fk_sp_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sp_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `supplier_payments` (`id`, `supplier_id`, `payment_number`, `payment_date`, `amount`, `payment_method`, `reference_number`, `notes`, `created_by`) VALUES
(1, 1, 'SPAY-2026-0001', '2026-09-22', 3608.00, 'Bank Transfer / NEFT', 'NEFT-AXIS-99120', 'Part payment on invoice PUR-2026-0891', 1),
(2, 3, 'SPAY-2026-0002', '2026-09-18', 4478.00, 'Bank Transfer / NEFT', 'RTGS-ICICI-44129', 'Advance settlement for antibiotics shipment', 1),
(3, 2, 'SPAY-2026-0003', '2026-09-10', 20160.00, 'Cheque', 'CHQ-881290', 'Full clearance of consignment PUR-2026-0842', 1);

-- ---------------------------------------------------------------------
-- 12. CUSTOMERS TABLE (Patients & Institutional Buyers)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `customer_payments`;
DROP TABLE IF EXISTS `sale_items`;
DROP TABLE IF EXISTS `sales`;
DROP TABLE IF EXISTS `customers`;

CREATE TABLE `customers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_code` VARCHAR(30) NOT NULL UNIQUE,
  `name` VARCHAR(120) NOT NULL,
  `mobile` VARCHAR(30) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(80) DEFAULT 'Delhi',
  `state` VARCHAR(80) DEFAULT 'Delhi',
  `pincode` VARCHAR(20) DEFAULT '110085',
  `customer_type` ENUM('Regular Patient', 'VIP / Hospital Staff', 'Chronic Care Patient', 'Corporate / Institutional') NOT NULL DEFAULT 'Regular Patient',
  `credit_limit` DECIMAL(10,2) NOT NULL DEFAULT 5000.00,
  `opening_balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `current_balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `notes` TEXT DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cust_name` (`name`),
  KEY `idx_cust_mobile` (`mobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customers` (`id`, `customer_code`, `name`, `mobile`, `email`, `address`, `city`, `state`, `pincode`, `customer_type`, `credit_limit`, `opening_balance`, `current_balance`, `status`) VALUES
(1, 'CUST-0001', 'Rajesh Sharma', '+91 98101 23456', 'rajesh.sharma@gmail.com', 'B-4/102, Sector 14', 'Rohini, Delhi', 'Delhi', '110085', 'Regular Patient', 5000.00, 0.00, 850.00, 'active'),
(2, 'CUST-0002', 'Priya Patel', '+91 98250 87654', 'priya.patel@yahoo.com', 'Flat 302, Palm Heights', 'Pitampura, Delhi', 'Delhi', '110034', 'Regular Patient', 3000.00, 0.00, 0.00, 'active'),
(3, 'CUST-0003', 'Amit Verma', '+91 99112 33445', 'amit.verma@outlook.com', 'House 54, Block C', 'Prashant Vihar, Delhi', 'Delhi', '110085', 'VIP / Hospital Staff', 10000.00, 0.00, 1450.00, 'active'),
(4, 'CUST-0004', 'Sunita Devi', '+91 98711 00998', NULL, 'Village Rithala', 'Delhi', 'Delhi', '110085', 'Chronic Care Patient', 2000.00, 0.00, 400.00, 'active'),
(5, 'CUST-0005', 'Dr. Vikram Singh (Clinic OPD)', '+91 98188 55443', 'drvikram@hospitalopd.in', 'OPD Chamber 4, Hospital Wing B', 'Rohini, Delhi', 'Delhi', '110085', 'Corporate / Institutional', 50000.00, 0.00, 12000.00, 'active');

-- ---------------------------------------------------------------------
-- 13. SALES & POINT OF SALE (POS) TABLE
-- ---------------------------------------------------------------------
CREATE TABLE `sales` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
  `customer_id` INT UNSIGNED DEFAULT NULL,
  `customer_name` VARCHAR(120) NOT NULL DEFAULT 'Walk-in Customer',
  `customer_mobile` VARCHAR(30) DEFAULT NULL,
  `doctor_name` VARCHAR(120) DEFAULT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `gst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `round_off` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `grand_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `due_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('Cash', 'UPI', 'Card', 'Credit') NOT NULL DEFAULT 'Cash',
  `payment_status` ENUM('Paid', 'Pending', 'Partial') NOT NULL DEFAULT 'Paid',
  `notes` TEXT DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sales_inv` (`invoice_number`),
  KEY `idx_sales_customer` (`customer_id`),
  KEY `idx_sales_date` (`created_at`),
  CONSTRAINT `fk_sales_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_sales_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sales` (`id`, `invoice_number`, `customer_id`, `customer_name`, `customer_mobile`, `doctor_name`, `subtotal`, `discount`, `gst_amount`, `round_off`, `grand_total`, `paid_amount`, `due_amount`, `payment_method`, `payment_status`, `created_by`, `created_at`) VALUES
(1, 'INV-2026-0042', 1, 'Rajesh Sharma', '+91 98101 23456', 'Dr. K. Singhal (Cardiology)', 610.00, 30.00, 69.60, 0.40, 650.00, 650.00, 0.00, 'UPI', 'Paid', 1, '2026-09-27 10:15:00'),
(2, 'INV-2026-0041', 2, 'Priya Patel', '+91 98250 87654', 'Dr. M. Iyer (Medicine)', 330.00, 10.00, 38.40, 0.00, 358.40, 358.40, 0.00, 'Cash', 'Paid', 1, '2026-09-27 09:42:00'),
(3, 'INV-2026-0040', NULL, 'Walk-in Customer', NULL, NULL, 44.00, 0.00, 2.20, -0.20, 46.00, 46.00, 0.00, 'Cash', 'Paid', 1, '2026-09-27 09:10:00'),
(4, 'INV-2026-0039', 3, 'Amit Verma', '+91 99112 33445', 'Dr. Arvind Sharma', 820.00, 40.00, 93.60, 0.00, 873.60, 873.60, 0.00, 'Card', 'Paid', 1, '2026-09-26 18:20:00'),
(5, 'INV-2026-0038', 5, 'Dr. Vikram Singh (Clinic OPD)', '+91 98188 55443', 'Self / Clinic OPD Use', 4200.00, 200.00, 480.00, 0.00, 4480.00, 0.00, 4480.00, 'Credit', 'Pending', 1, '2026-09-26 14:10:00');

CREATE TABLE `sale_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sale_id` INT UNSIGNED NOT NULL,
  `medicine_id` INT UNSIGNED NOT NULL,
  `batch_id` INT UNSIGNED NOT NULL,
  `medicine_name` VARCHAR(150) NOT NULL,
  `batch_number` VARCHAR(50) NOT NULL,
  `expiry_date` DATE NOT NULL,
  `quantity` INT NOT NULL,
  `selling_price` DECIMAL(10,2) NOT NULL,
  `cost_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00, -- Actual batch procurement cost for precise profit reporting
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `gst_rate` DECIMAL(5,2) NOT NULL DEFAULT 12.00,
  `gst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_si_sale` (`sale_id`),
  KEY `idx_si_medicine` (`medicine_id`),
  KEY `idx_si_batch` (`batch_id`),
  CONSTRAINT `fk_si_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_si_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_si_batch` FOREIGN KEY (`batch_id`) REFERENCES `medicine_batches` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sale_items` (`id`, `sale_id`, `medicine_id`, `batch_id`, `medicine_name`, `batch_number`, `expiry_date`, `quantity`, `selling_price`, `cost_price`, `discount`, `gst_rate`, `gst_amount`, `total`) VALUES
(1, 1, 1, 101, 'Paracetamol 500mg', 'PCM26A', '2027-04-30', 2, 30.00, 21.00, 0.00, 12.00, 7.20, 60.00),
(2, 1, 4, 106, 'Pantoprazole Gastro-Resistant 40mg', 'PAN26E', '2027-11-30', 2, 145.00, 98.00, 10.00, 12.00, 31.32, 290.00),
(3, 1, 3, 105, 'Amoxicillin & Clavulanate 625mg', 'AUG26D', '2027-06-30', 1, 195.00, 142.00, 0.00, 12.00, 23.40, 195.00),
(4, 2, 2, 103, 'Azithromycin 500mg', 'AZI2609', '2027-08-31', 1, 110.00, 78.00, 0.00, 12.00, 13.20, 110.00),
(5, 2, 5, 107, 'Cetirizine Hydrochloride 10mg', 'CET26A', '2028-02-28', 2, 20.00, 13.50, 0.00, 12.00, 4.80, 40.00),
(6, 3, 6, 108, 'ORS Electrolyte Powder 21.8g', 'ELE2602', '2027-10-31', 2, 22.00, 16.00, 0.00, 5.00, 2.20, 44.00),
(7, 4, 7, 109, 'Cholecalciferol 60,000 IU', 'CAL26G', '2027-05-31', 2, 170.00, 115.00, 0.00, 12.00, 40.80, 340.00),
(8, 4, 10, 113, 'Telmisartan 40mg', 'TEL26P', '2027-07-31', 2, 205.00, 145.00, 0.00, 12.00, 49.20, 410.00),
(9, 5, 1, 101, 'Paracetamol 500mg', 'PCM26A', '2027-04-30', 20, 30.00, 21.00, 0.00, 12.00, 72.00, 600.00),
(10, 5, 3, 105, 'Amoxicillin & Clavulanate 625mg', 'AUG26D', '2027-06-30', 10, 195.00, 142.00, 0.00, 12.00, 234.00, 1950.00);

-- ---------------------------------------------------------------------
-- 14. CUSTOMER PAYMENTS TABLE
-- ---------------------------------------------------------------------
CREATE TABLE `customer_payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` INT UNSIGNED NOT NULL,
  `receipt_number` VARCHAR(50) NOT NULL UNIQUE,
  `payment_date` DATE NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('Cash', 'UPI', 'Card', 'Bank Transfer') NOT NULL DEFAULT 'Cash',
  `reference_number` VARCHAR(100) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cp_customer` (`customer_id`),
  CONSTRAINT `fk_cp_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_cp_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customer_payments` (`id`, `customer_id`, `receipt_number`, `payment_date`, `amount`, `payment_method`, `reference_number`, `notes`, `created_by`) VALUES
(1, 1, 'CPAY-2026-0001', '2026-09-25', 1000.00, 'UPI', 'UPI-REF-9921', 'On-account patient advance payment', 1),
(2, 3, 'CPAY-2026-0002', '2026-09-26', 2000.00, 'Cash', NULL, 'Monthly credit clearance', 1),
(3, 5, 'CPAY-2026-0003', '2026-09-20', 15000.00, 'Bank Transfer', 'NEFT-OPD-7712', 'Fortnightly OPD credit settlement', 1);

-- ---------------------------------------------------------------------
-- 15. STOCK MOVEMENTS AUDIT LEDGER
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `stock_movements`;
CREATE TABLE `stock_movements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `movement_type` ENUM('PURCHASE', 'SALE', 'SALE_RETURN', 'PURCHASE_RETURN', 'ADJUSTMENT_IN', 'ADJUSTMENT_OUT', 'EXPIRED', 'DAMAGED') NOT NULL,
  `medicine_id` INT UNSIGNED NOT NULL,
  `batch_id` INT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL, -- Positive for inward additions, negative for outward deductions
  `reference_id` INT UNSIGNED DEFAULT NULL,
  `reference_number` VARCHAR(80) DEFAULT NULL,
  `notes` VARCHAR(255) DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sm_type` (`movement_type`),
  KEY `idx_sm_med` (`medicine_id`),
  KEY `idx_sm_batch` (`batch_id`),
  KEY `idx_sm_created` (`created_at`),
  CONSTRAINT `fk_sm_med` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sm_batch` FOREIGN KEY (`batch_id`) REFERENCES `medicine_batches` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sm_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `stock_movements` (`id`, `movement_type`, `medicine_id`, `batch_id`, `quantity`, `reference_id`, `reference_number`, `notes`, `created_by`, `created_at`) VALUES
(1, 'PURCHASE', 1, 101, 220, 1, 'PUR-2026-0891', 'Inward consignment receipt', 1, '2026-09-22 11:30:00'),
(2, 'PURCHASE', 4, 106, 100, 1, 'PUR-2026-0891', 'Inward consignment receipt', 1, '2026-09-22 11:30:00'),
(3, 'SALE', 1, 101, -2, 1, 'INV-2026-0042', 'POS Counter sale', 1, '2026-09-27 10:15:00'),
(4, 'SALE', 4, 106, -2, 1, 'INV-2026-0042', 'POS Counter sale', 1, '2026-09-27 10:15:00'),
(5, 'SALE', 3, 105, -1, 1, 'INV-2026-0042', 'POS Counter sale', 1, '2026-09-27 10:15:00'),
(6, 'DAMAGED', 12, 115, -2, NULL, 'ADJ-2026-0001', 'Bottle seal cracked during shelf stocking', 1, '2026-09-24 16:20:00'),
(7, 'EXPIRED', 9, 112, -6, NULL, 'ADJ-2026-0002', 'Moved to quarantine bin for supplier return', 1, '2026-09-25 15:40:00');

-- ---------------------------------------------------------------------
-- 16. AUDIT LOGS TABLE
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(80) NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `record_id` VARCHAR(50) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_module` (`module`),
  KEY `idx_audit_created` (`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `module`, `record_id`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'LOGIN', 'auth', '1', 'User admin logged in from terminal', '127.0.0.1', '2026-09-27 08:30:00'),
(2, 1, 'PURCHASE_CREATED', 'purchases', '1', 'Recorded purchase PUR-2026-0891 from Apollo Healthcare', '127.0.0.1', '2026-09-22 11:30:00'),
(3, 1, 'SALE_CREATED', 'billing', '1', 'Dispensed invoice INV-2026-0042 to Rajesh Sharma', '127.0.0.1', '2026-09-27 10:15:00'),
(4, 1, 'STOCK_ADJUSTED', 'inventory', '12', 'Adjusted -2 units damage for Ascoril LS Syrup', '127.0.0.1', '2026-09-24 16:20:00');

-- ---------------------------------------------------------------------
-- 17. EXPENSES TABLE (For Profit & Loss Reporting)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(150) NOT NULL,
  `category` VARCHAR(80) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `expense_date` DATE NOT NULL,
  `payment_method` ENUM('Cash', 'Bank Transfer', 'UPI', 'Cheque') NOT NULL DEFAULT 'Cash',
  `notes` TEXT DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_expenses_date` (`expense_date`),
  CONSTRAINT `fk_expenses_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `expenses` (`id`, `title`, `category`, `amount`, `expense_date`, `payment_method`, `notes`, `created_by`) VALUES
(1, 'Thermal Paper Rolls & POS Stationery', 'Office Supplies', 1250.00, '2026-09-20', 'Cash', 'Purchased 20 thermal rolls', 1),
(2, 'Cold Chain Refrigerator Maintenance', 'Equipment Maintenance', 2400.00, '2026-09-15', 'UPI', 'Annual temperature sensor calibration', 1);

SET FOREIGN_KEY_CHECKS = 1;
