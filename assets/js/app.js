/**
 * MY HOSPITAL PRO — Core Application Bootstrap & Interaction Controller
 */

const App = {
    init() {
        // Apply Tailwind custom color bindings matching CSS variables if Tailwind CDN exists
        if (window.tailwind) {
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            primary: {
                                DEFAULT: 'var(--primary)',
                                dark: 'var(--primary-dark)',
                                light: 'var(--primary-light)',
                                hover: 'var(--primary-hover)'
                            },
                            accent: 'var(--accent)',
                            surface: {
                                DEFAULT: 'var(--surface)',
                                alt: 'var(--surface-alt)',
                                hover: 'var(--surface-hover)'
                            },
                            appbg: 'var(--background)',
                            apptext: 'var(--text)',
                            appmuted: 'var(--muted)',
                            appborder: 'var(--border)',
                            success: {
                                DEFAULT: 'var(--success)',
                                light: 'var(--success-light)'
                            },
                            warning: {
                                DEFAULT: 'var(--warning)',
                                light: 'var(--warning-light)'
                            },
                            danger: {
                                DEFAULT: 'var(--danger)',
                                light: 'var(--danger-light)'
                            },
                            info: {
                                DEFAULT: 'var(--info)',
                                light: 'var(--info-light)'
                            }
                        }
                    }
                }
            };
        }

        // Check login state for authenticated pages
        const isLoginPage = window.location.pathname.endsWith('index.html') && 
                           !window.location.pathname.includes('/medicines/') && 
                           !window.location.pathname.includes('/purchases/') && 
                           !window.location.pathname.includes('/inventory/') && 
                           !window.location.pathname.includes('/billing/') && 
                           !window.location.pathname.includes('/customers/') && 
                           !window.location.pathname.includes('/suppliers/') && 
                           !window.location.pathname.includes('/reports/');

        if (!isLoginPage && !sessionStorage.getItem('mhp_auth_token')) {
            // In demo mode we can auto-login or redirect. Let's auto-set demo session if not set so user never gets stuck!
            sessionStorage.setItem('mhp_auth_token', 'demo-session-token');
            sessionStorage.setItem('mhp_user', JSON.stringify(APP_CONFIG.demoAuth));
        }

        // Global keyboard shortcuts (F2 new bill, etc.)
        window.addEventListener('keydown', (e) => {
            // F2: New Bill
            if (e.key === 'F2') {
                e.preventDefault();
                const root = getRootPrefix();
                window.location.href = root + 'billing/index.html';
            }
        });
    },

    logout() {
        sessionStorage.removeItem('mhp_auth_token');
        sessionStorage.removeItem('mhp_user');
        const root = getRootPrefix();
        window.location.href = root + 'index.html';
    }
};

document.addEventListener('DOMContentLoaded', () => {
    App.init();
});
