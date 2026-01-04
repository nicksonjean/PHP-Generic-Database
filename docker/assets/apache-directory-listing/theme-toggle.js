// Theme Toggle for Apache Directory Listing
// Same approach as Nginx - uses CSS classes instead of switching CSS files

const THEME_STORAGE_KEY = 'apache-directory-theme';

// Function to detect and apply theme according to system preferences
function applyThemePreference() {
    // Check if there's a saved preference
    const savedTheme = localStorage.getItem(THEME_STORAGE_KEY);
    
    if (savedTheme) {
        // Apply saved theme
        document.documentElement.classList.remove('light-theme', 'dark-theme');
        document.documentElement.classList.add(savedTheme);
    } else {
        // Apply theme according to system preference
        const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.classList.remove('light-theme', 'dark-theme');
        
        if (prefersDarkScheme) {
            document.documentElement.classList.add('dark-theme');
            localStorage.setItem(THEME_STORAGE_KEY, 'dark-theme');
        } else {
            document.documentElement.classList.add('light-theme');
            localStorage.setItem(THEME_STORAGE_KEY, 'light-theme');
        }
    }
}

// Function to manually toggle theme
function toggleTheme() {
    const isDarkTheme = document.documentElement.classList.contains('dark-theme');
    document.documentElement.classList.remove('light-theme', 'dark-theme');
    
    if (isDarkTheme) {
        document.documentElement.classList.add('light-theme');
        localStorage.setItem(THEME_STORAGE_KEY, 'light-theme');
        updateThemeButton('light');
    } else {
        document.documentElement.classList.add('dark-theme');
        localStorage.setItem(THEME_STORAGE_KEY, 'dark-theme');
        updateThemeButton('dark');
    }
}

// Function to update theme button icon
function updateThemeButton(theme) {
    const btn = document.getElementById('theme-toggle');
    if (btn) {
        // Extract theme from class name if needed
        if (theme === 'light-theme' || theme === 'light') {
            btn.innerHTML = '☀️';
            btn.title = 'Switch to Dark Theme';
        } else {
            btn.innerHTML = '🌙';
            btn.title = 'Switch to Light Theme';
        }
    }
}

// Listen for changes in system preference
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
    // Only apply if there's no saved preference
    if (!localStorage.getItem(THEME_STORAGE_KEY)) {
        document.documentElement.classList.remove('light-theme', 'dark-theme');
        document.documentElement.classList.add(e.matches ? 'dark-theme' : 'light-theme');
    }
});

// Initialize theme on page load (for button creation)
window.addEventListener('load', function() {
    // Apply theme preference (will use saved or system preference)
    applyThemePreference();
    
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
        const currentTheme = document.documentElement.classList.contains('dark-theme') ? 'dark' : 'light';
        updateThemeButton(currentTheme);
    }
});
