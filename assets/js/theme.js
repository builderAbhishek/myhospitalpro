/**
 * MY HOSPITAL PRO — Multi-Theme Manager
 * Controls application visual themes via CSS variables & data-theme attribute
 */

const ThemeManager = {
    themes: [
        { id: "default", name: "MHP Default", desc: "Healthcare Teal", color: "#0f766e" },
        { id: "emerald", name: "Emerald", desc: "Green Healthcare", color: "#15803d" },
        { id: "royal", name: "Royal", desc: "Dark Blue / Indigo", color: "#1d4ed8" },
        { id: "warm", name: "Warm", desc: "Saffron / Business", color: "#c2410c" }
    ],

    init() {
        // Read theme from localStorage or fallback to APP_CONFIG
        const savedTheme = localStorage.getItem("mhp_theme") || (typeof APP_CONFIG !== "undefined" ? APP_CONFIG.theme : "default");
        this.applyTheme(savedTheme);
    },

    applyTheme(themeId) {
        const validTheme = this.themes.some(t => t.id === themeId) ? themeId : "default";
        document.documentElement.setAttribute("data-theme", validTheme);
        localStorage.setItem("mhp_theme", validTheme);
        
        // Dispatch event for components or charts that listen to theme changes
        window.dispatchEvent(new CustomEvent("mhp:themechanged", { detail: { theme: validTheme } }));

        // Update active class in theme switcher if present
        const buttons = document.querySelectorAll(".theme-switch-btn");
        buttons.forEach(btn => {
            if (btn.dataset.themeId === validTheme) {
                btn.classList.add("ring-2", "ring-offset-2", "ring-primary");
            } else {
                btn.classList.remove("ring-2", "ring-offset-2", "ring-primary");
            }
        });
    },

    getCurrentTheme() {
        return document.documentElement.getAttribute("data-theme") || "default";
    },

    renderDevSwitcher() {
        // Only render floating development theme switcher if not already present
        if (document.getElementById("theme-switcher-widget")) return;

        const widget = document.createElement("div");
        widget.id = "theme-switcher-widget";
        widget.className = "no-print fixed bottom-4 left-4 z-40 bg-surface border border-appborder shadow-lg rounded-xl p-2.5 flex items-center gap-2 backdrop-blur-sm bg-opacity-95 transition-all text-xs";
        
        const current = this.getCurrentTheme();
        let optionsHtml = this.themes.map(t => `
            <button type="button" 
                    data-theme-id="${t.id}"
                    onclick="ThemeManager.applyTheme('${t.id}')"
                    title="${t.name} (${t.desc})"
                    class="theme-switch-btn w-6 h-6 rounded-full flex items-center justify-center transition-transform hover:scale-110 ${t.id === current ? 'ring-2 ring-offset-2 ring-slate-800' : ''}"
                    style="background-color: ${t.color}">
            </button>
        `).join("");

        widget.innerHTML = `
            <div class="flex items-center gap-1.5 font-semibold text-appmuted pr-1">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m4.93 4.93 4.24 4.24"/><path d="m14.83 9.17 4.24-4.24"/><path d="m14.83 14.83 4.24 4.24"/><path d="m9.17 14.83-4.24 4.24"/></svg>
                <span>Theme:</span>
            </div>
            <div class="flex items-center gap-1.5">
                ${optionsHtml}
            </div>
        `;

        document.body.appendChild(widget);
    }
};

// Immediate initialization to avoid flash
ThemeManager.init();
document.addEventListener("DOMContentLoaded", () => {
    ThemeManager.renderDevSwitcher();
});
