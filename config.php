<?php
/**
 * MY HOSPITAL PRO (MHP) — Central Configuration
 * Supports White-Label Branding, Dynamic Theme Engine, and MySQL DB Settings
 */

// Application Constants
if (!defined('APP_NAME')) define('APP_NAME', 'My Hospital Pro');
if (!defined('APP_SHORT_NAME')) define('APP_SHORT_NAME', 'MHP');
if (!defined('APP_TAGLINE')) define('APP_TAGLINE', 'Smart Healthcare Management');
if (!defined('APP_VERSION')) define('APP_VERSION', '1.0.0 (Phase 1)');
if (!defined('APP_DOMAIN')) define('APP_DOMAIN', 'myhospitalpro.in');

// Database Credentials (MySQL)
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', getenv('DB_PORT') ?: '3306');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'mhp_database');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// Hospital & Pharmacy Store Details
if (!defined('HOSPITAL_LEGAL_NAME')) define('HOSPITAL_LEGAL_NAME', 'My Hospital Pro Super Specialty Hospital & Pharmacy');
if (!defined('HOSPITAL_STORE_NAME')) define('HOSPITAL_STORE_NAME', 'MHP Central In-House Pharmacy');
if (!defined('HOSPITAL_ADDRESS')) define('HOSPITAL_ADDRESS', 'Plot 42, Health City Boulevard, Sector 9, Rohini');
if (!defined('HOSPITAL_CITY')) define('HOSPITAL_CITY', 'New Delhi');
if (!defined('HOSPITAL_STATE')) define('HOSPITAL_STATE', 'Delhi');
if (!defined('HOSPITAL_PINCODE')) define('HOSPITAL_PINCODE', '110085');
if (!defined('HOSPITAL_PHONE')) define('HOSPITAL_PHONE', '+91 98765 43210 / 011-27891234');
if (!defined('HOSPITAL_EMAIL')) define('HOSPITAL_EMAIL', 'pharmacy@myhospitalpro.in');
if (!defined('HOSPITAL_GSTIN')) define('HOSPITAL_GSTIN', '07AAAAA0000A1Z5');
if (!defined('HOSPITAL_DL_NUMBER')) define('HOSPITAL_DL_NUMBER', 'DL-20B-883491 / 21B-883492');
if (!defined('HOSPITAL_FSSAI')) define('HOSPITAL_FSSAI', '13322001000492');

// White Label & UI Configuration
$APP_CONFIG = [
    'name' => APP_NAME,
    'shortName' => APP_SHORT_NAME,
    'tagline' => APP_TAGLINE,
    'currency' => '₹',
    'theme' => 'default', // Options: 'default', 'emerald', 'royal', 'warm'
    'logo' => 'assets/images/logo.svg',
    'favicon' => 'assets/images/favicon.svg'
];

// Timezone & Error Handling
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
