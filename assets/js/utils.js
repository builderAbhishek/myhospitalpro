/**
 * MY HOSPITAL PRO — Utility Helpers
 */

const Utils = {
    // Format number to Indian Rupee standard (₹42,580.00)
    formatINR(value, showDecimals = true) {
        const num = Number(value) || 0;
        return new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: 'INR',
            minimumFractionDigits: showDecimals ? 2 : 0,
            maximumFractionDigits: showDecimals ? 2 : 0
        }).format(num);
    },

    // Format plain number with Indian commas
    formatNumber(value) {
        const num = Number(value) || 0;
        return new Intl.NumberFormat('en-IN').format(num);
    },

    // Format date string to readable display
    formatDate(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    },

    // Format date & time
    formatDateTime(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    // Today's date as YYYY-MM-DD
    getTodayISO() {
        return new Date().toISOString().split('T')[0];
    },

    // Generate unique ID
    generateId(prefix = 'ID') {
        const randomNum = Math.floor(1000 + Math.random() * 9000);
        const year = new Date().getFullYear();
        return `${prefix}-${year}-${randomNum}`;
    },

    // Get URL query parameter
    getUrlParam(paramName) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(paramName);
    },

    // Debounce function
    debounce(func, wait = 300) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Export tabular data to CSV download
    exportToCSV(filename, rows) {
        if (!rows || !rows.length) {
            if (typeof showToast === 'function') showToast("No data to export", "warning");
            return;
        }
        const separator = ',';
        const keys = Object.keys(rows[0]);
        const csvContent =
            keys.join(separator) +
            '\n' +
            rows.map(row => {
                return keys.map(k => {
                    let cell = row[k] === null || row[k] === undefined ? '' : row[k];
                    cell = cell instanceof Date ? cell.toLocaleString() : cell.toString();
                    cell = cell.replace(/"/g, '""');
                    if (cell.search(/("|,|\n)/g) >= 0) {
                        cell = `"${cell}"`;
                    }
                    return cell;
                }).join(separator);
            }).join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename.endsWith('.csv') ? filename : `${filename}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            if (typeof showToast === 'function') showToast(`Exported ${filename} successfully!`, "success");
        }
    },

    // Check expiry status: expired, within30, within60, within90, good
    getExpiryStatus(expiryDateStr) {
        if (!expiryDateStr) return 'good';
        const today = new Date();
        const expiry = new Date(expiryDateStr);
        const diffTime = expiry - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays <= 0) return { status: 'expired', label: 'Expired', days: diffDays, badgeClass: 'badge-danger' };
        if (diffDays <= 30) return { status: '30days', label: `Expiring in ${diffDays}d`, days: diffDays, badgeClass: 'badge-danger' };
        if (diffDays <= 60) return { status: '60days', label: `Expiring in ${diffDays}d`, days: diffDays, badgeClass: 'badge-warning' };
        if (diffDays <= 90) return { status: '90days', label: `Expiring in ${diffDays}d`, days: diffDays, badgeClass: 'badge-info' };
        return { status: 'good', label: 'Good', days: diffDays, badgeClass: 'badge-success' };
    }
};
