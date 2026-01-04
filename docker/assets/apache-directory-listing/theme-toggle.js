// Theme Toggle for Apache Directory Listing
// Allows switching between table.css (light) and table-darkmode.css (dark)

// Theme CSS file names
const LIGHT_THEME_CSS = 'table.css';
const DARK_THEME_CSS = 'table-darkmode.css';
const THEME_STORAGE_KEY = 'apache-directory-theme';

// Function to get the base path where CSS files are located
function getCSSBasePath() {
    // Try to find any stylesheet to determine base path
    const links = document.querySelectorAll('link[rel="stylesheet"]');
    for (let link of links) {
        const href = link.getAttribute('href') || '';
        // Look for common.css, table.css, or any apache directory listing CSS
        if (href.includes('common.css') || href.includes('table') || href.includes('darkmode') || href.includes('apache-directory-listing')) {
            const lastSlash = href.lastIndexOf('/');
            if (lastSlash !== -1) {
                return href.substring(0, lastSlash + 1);
            }
            // If no slash, CSS is in root, return empty string
            return '';
        }
    }
    
    // Fallback: try to determine from script location
    const scripts = document.querySelectorAll('script[src]');
    for (let script of scripts) {
        const src = script.getAttribute('src') || '';
        if (src.includes('theme-toggle.js')) {
            const lastSlash = src.lastIndexOf('/');
            if (lastSlash !== -1) {
                return src.substring(0, lastSlash + 1);
            }
        }
    }
    
    // Default fallback - assume CSS files are in same directory as this script
    return '';
}

// Function to get the current theme CSS link element
function getCurrentThemeLink() {
    const links = document.querySelectorAll('link[rel="stylesheet"]');
    for (let link of links) {
        const href = link.getAttribute('href') || '';
        if (href.includes(LIGHT_THEME_CSS) || href.includes(DARK_THEME_CSS)) {
            return link;
        }
    }
    return null;
}

// Function to remove old theme CSS
function removeThemeCSS() {
    const links = document.querySelectorAll('link[rel="stylesheet"]');
    links.forEach(function(link) {
        const href = link.getAttribute('href') || '';
        if (href.includes(LIGHT_THEME_CSS) || href.includes(DARK_THEME_CSS)) {
            link.remove();
        }
    });
}

// Function to add theme CSS
function addThemeCSS(cssFile) {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.type = 'text/css';
    link.id = 'directory-theme';
    link.href = getCSSBasePath() + cssFile;
    document.head.appendChild(link);
}

// Function to get current theme from localStorage or detect from CSS
function getCurrentTheme() {
    const savedTheme = localStorage.getItem(THEME_STORAGE_KEY);
    if (savedTheme) {
        return savedTheme;
    }
    
    // Try to detect from current CSS
    const themeLink = getCurrentThemeLink();
    if (themeLink) {
        const href = themeLink.getAttribute('href') || '';
        if (href.includes(DARK_THEME_CSS)) {
            return 'dark';
        }
    }
    
    // Default to light
    return 'light';
}

// Function to apply theme
function applyTheme(theme) {
    // Remove existing theme CSS
    removeThemeCSS();
    
    // Add new theme CSS
    const cssFile = theme === 'dark' ? DARK_THEME_CSS : LIGHT_THEME_CSS;
    addThemeCSS(cssFile);
    
    // Save preference
    localStorage.setItem(THEME_STORAGE_KEY, theme);
    
    // Update button icon
    updateThemeButton(theme);
}

// Function to toggle theme
function toggleTheme() {
    const currentTheme = getCurrentTheme();
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    applyTheme(newTheme);
}

// Function to update theme button icon
function updateThemeButton(theme) {
    const btn = document.getElementById('theme-toggle');
    if (btn) {
        btn.innerHTML = theme === 'dark' ? '🌙' : '☀️';
        btn.title = theme === 'dark' ? 'Switch to Light Theme' : 'Switch to Dark Theme';
    }
}

// Function to apply saved theme immediately (before page is fully visible)
function applySavedThemeImmediate() {
    const savedTheme = localStorage.getItem(THEME_STORAGE_KEY);
    if (savedTheme && (savedTheme === 'dark' || savedTheme === 'light')) {
        // Find the CSS link created by Apache's IndexStyleSheet
        const links = document.querySelectorAll('link[rel="stylesheet"]');
        for (let link of links) {
            const href = link.getAttribute('href') || '';
            // Check if this is the IndexStyleSheet CSS (table.css or table-darkmode.css)
            if (href.includes('table.css') || href.includes('table-darkmode.css')) {
                // Get base path from current href
                const lastSlash = href.lastIndexOf('/');
                const basePath = lastSlash !== -1 ? href.substring(0, lastSlash + 1) : '';
                
                // Determine new CSS file
                const newCSS = savedTheme === 'dark' ? DARK_THEME_CSS : LIGHT_THEME_CSS;
                const newHref = basePath + newCSS;
                
                // Replace with saved theme if different
                const currentTheme = href.includes(DARK_THEME_CSS) ? 'dark' : 'light';
                if (currentTheme !== savedTheme || !href.includes(newCSS)) {
                    link.href = newHref;
                }
                return true; // Theme applied
            }
        }
    }
    return false; // Theme not applied
}

// Apply theme as early as possible (even before DOMContentLoaded)
// Try multiple times to catch the CSS link when it's available
var applyAttempts = 0;
var maxAttempts = 10;

function tryApplyTheme() {
    applyAttempts++;
    if (applySavedThemeImmediate()) {
        // Theme applied successfully
        return;
    }
    
    // If not applied yet and we haven't exceeded max attempts, try again
    if (applyAttempts < maxAttempts) {
        setTimeout(tryApplyTheme, 50);
    }
}

// Start trying immediately
if (document.readyState === 'loading') {
    // If document is still loading, try immediately and on DOMContentLoaded
    tryApplyTheme();
    document.addEventListener('DOMContentLoaded', function() {
        applySavedThemeImmediate();
    });
} else {
    // If document is already loaded, apply immediately
    applySavedThemeImmediate();
}

// Initialize theme on page load (for button creation and final sync)
window.addEventListener('load', function() {
    // Get current theme from localStorage or detect from CSS
    let theme = getCurrentTheme();
    
    // Verify and sync theme
    const themeLink = getCurrentThemeLink();
    if (themeLink) {
        const href = themeLink.getAttribute('href') || '';
        const detectedTheme = href.includes(DARK_THEME_CSS) ? 'dark' : 'light';
        
        // If detected theme differs from saved, use saved preference
        const savedTheme = localStorage.getItem(THEME_STORAGE_KEY);
        if (savedTheme && detectedTheme !== savedTheme) {
            applyTheme(savedTheme);
            theme = savedTheme;
        } else {
            theme = detectedTheme;
            // Save detected theme if no preference saved
            if (!savedTheme) {
                localStorage.setItem(THEME_STORAGE_KEY, theme);
            }
        }
    } else {
        // No theme CSS found, apply saved theme or default
        applyTheme(theme);
    }
    
    // Create theme toggle button in footer
    const footer = document.querySelector('footer');
    if (footer) {
        // Check if button already exists
        let themeToggleBtn = document.getElementById('theme-toggle');
        
        if (!themeToggleBtn) {
            themeToggleBtn = document.createElement('button');
            themeToggleBtn.id = 'theme-toggle';
            themeToggleBtn.className = 'theme-toggle-btn';
            themeToggleBtn.onclick = toggleTheme;
            footer.appendChild(themeToggleBtn);
        }
        
        // Update button with current theme
        updateThemeButton(theme);
    }
});
