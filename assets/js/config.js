/**
 * MY HOSPITAL PRO — Global Configuration
 * Centralized White-Label and Environment Settings
 */

const APP_CONFIG = {
    name: "My Hospital Pro",
    shortName: "MHP",
    tagline: "Manage. Track. Grow.",
    logo: "assets/images/logo.svg",
    favicon: "assets/images/favicon.svg",
    theme: "default", // Options: 'default', 'emerald', 'royal', 'warm'
    currency: "INR",
    currencySymbol: "₹",
    domain: "myhospitalpro.in",
    version: "1.0.0 (Phase 1)",
    
    // Hospital & Pharmacy Store Details (used in Billing, Invoices, Header)
    hospital: {
        legalName: "My Hospital Pro Super Specialty Hospital & Pharmacy",
        storeName: "MHP Central In-House Pharmacy",
        address: "Plot 42, Health City Boulevard, Sector 9, Rohini",
        city: "New Delhi",
        state: "Delhi",
        pincode: "110085",
        phone: "+91 98765 43210 / 011-27891234",
        email: "pharmacy@myhospitalpro.in",
        gstin: "07AAAAA0000A1Z5",
        dlNumber: "DL-20B-883491 / 21B-883492",
        fssaiNumber: "13322001000492"
    },

    // Demo Mode Configuration
    demoAuth: {
        username: "admin",
        password: "admin123",
        role: "Chief Pharmacist / Admin",
        displayName: "Dr. Arvind Sharma"
    },

    // Navigation configuration
    navItems: [
        { key: "dashboard", label: "Dashboard", path: "dashboard.html", icon: "layout-dashboard" },
        { key: "medicines", label: "Medicines", path: "medicines/index.html", icon: "pill", badgeKey: "medicinesCount" },
        { key: "inventory", label: "Inventory", path: "inventory/index.html", icon: "boxes", badgeKey: "lowStockCount" },
        { key: "purchases", label: "Purchases", path: "purchases/index.html", icon: "truck" },
        { key: "billing", label: "Billing / POS", path: "billing/index.html", icon: "receipt", highlight: true },
        { key: "customers", label: "Customers", path: "customers/index.html", icon: "users" },
        { key: "suppliers", label: "Suppliers", path: "suppliers/index.html", icon: "building-2" },
        { key: "reports", label: "Reports", path: "reports/index.html", icon: "bar-chart-3" }
    ]
};

// Compute relative path to root based on current location
function getRootPrefix() {
    const path = window.location.pathname.replace(/\\/g, '/');
    if (path.includes('/medicines/') || 
        path.includes('/purchases/') || 
        path.includes('/inventory/') || 
        path.includes('/billing/') || 
        path.includes('/customers/') || 
        path.includes('/suppliers/') || 
        path.includes('/reports/')) {
        return '../';
    }
    return './';
}
