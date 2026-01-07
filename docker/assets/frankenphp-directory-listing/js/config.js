/*------------------------------------*\
    FrankenPHP Directory Listing
    Based on Nginxy by @lfelipe1501
\*------------------------------------*/

// Theme configuration
const THEME_STORAGE_KEY = 'frankenphp-directory-theme';

// Pagination configuration
const filesPerPage = 10000;
let currentPage = 1;
let currentSortField = 'name';
let currentSortOrder = 'asc';

// Function to detect and apply theme according to system preferences
function applyThemePreference() {
    const savedTheme = localStorage.getItem(THEME_STORAGE_KEY);
    
    if (savedTheme) {
        document.documentElement.classList.remove('light-theme', 'dark-theme');
        document.documentElement.classList.add(savedTheme);
    } else {
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
    if (!localStorage.getItem(THEME_STORAGE_KEY)) {
        document.documentElement.classList.remove('light-theme', 'dark-theme');
        document.documentElement.classList.add(e.matches ? 'dark-theme' : 'light-theme');
    }
});

// Function to check if a row is a directory
function isDirectory(row) {
    const link = row.querySelector('td:first-child a');
    if (link && link.getAttribute('href')) {
        return link.getAttribute('href').endsWith('/');
    }
    return false;
}

// Function to convert file sizes to bytes for comparison
function parseFileSize(sizeStr) {
    if (!sizeStr || sizeStr === '-') return 0;
    
    sizeStr = sizeStr.trim().toLowerCase();
    
    const units = {
        'b': 1,
        'bytes': 1,
        'k': 1024,
        'kb': 1024,
        'kib': 1024,
        'm': 1024 * 1024,
        'mb': 1024 * 1024,
        'mib': 1024 * 1024,
        'g': 1024 * 1024 * 1024,
        'gb': 1024 * 1024 * 1024,
        'gib': 1024 * 1024 * 1024,
        't': 1024 * 1024 * 1024 * 1024,
        'tb': 1024 * 1024 * 1024 * 1024,
        'tib': 1024 * 1024 * 1024 * 1024
    };
    
    const kiPattern = /^(\d+(?:\.\d+)?)\s*([kmgt]i?b)$/i;
    const kiMatch = sizeStr.match(kiPattern);
    
    if (kiMatch) {
        const size = parseFloat(kiMatch[1]);
        const unit = kiMatch[2].toLowerCase();
        return size * (units[unit] || 1);
    }
    
    const simplePattern = /^(\d+(?:\.\d+)?)\s*([bkmgt])?$/i;
    const simpleMatch = sizeStr.match(simplePattern);
    
    if (simpleMatch) {
        const size = parseFloat(simpleMatch[1]);
        const unit = (simpleMatch[2] || 'b').toLowerCase();
        return size * (units[unit] || 1);
    }
    
    return 0;
}

// Function to convert dates to timestamps for comparison
function parseDate(dateStr) {
    if (!dateStr || dateStr === '-') return 0;
    
    try {
        // Try to parse as ISO date or common formats
        const date = new Date(dateStr);
        if (!isNaN(date.getTime())) {
            return date.getTime();
        }
        
        // Try format like "07/01/2026 16:47:55"
        const parts = dateStr.split(' ');
        if (parts.length >= 2) {
            const dateParts = parts[0].split('/');
            const timeParts = parts[1].split(':');
            
            if (dateParts.length >= 3 && timeParts.length >= 2) {
                const day = parseInt(dateParts[0], 10);
                const month = parseInt(dateParts[1], 10) - 1;
                const year = parseInt(dateParts[2], 10);
                const hours = parseInt(timeParts[0], 10);
                const minutes = parseInt(timeParts[1], 10);
                const seconds = timeParts.length >= 3 ? parseInt(timeParts[2], 10) : 0;
                
                return new Date(year, month, day, hours, minutes, seconds).getTime();
            }
        }
    } catch (e) {
        console.error('Error parsing date:', e);
    }
    
    return 0;
}

// Function to initialize table headers and add sorting events
function initializeTableHeaders() {
    const headers = document.querySelectorAll('th');
    
    headers.forEach((header, index) => {
        const headerText = header.textContent.trim();
        header.innerHTML = '';
        
        const textSpan = document.createElement('span');
        textSpan.textContent = headerText;
        header.appendChild(textSpan);
        
        header.appendChild(document.createTextNode(' '));
        
        const iconSpan = document.createElement('span');
        iconSpan.className = 'sort-icon';
        iconSpan.innerHTML = '⇕';
        iconSpan.style.opacity = '0.5';
        header.appendChild(iconSpan);
    });
    
    // Name column (first)
    if (headers[0]) {
        headers[0].setAttribute('data-sort', 'name');
        headers[0].style.cursor = 'pointer';
        headers[0].addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            sortTable('name');
            return false;
        });
    }
    
    // Size column (second)
    if (headers.length > 1 && headers[1]) {
        headers[1].setAttribute('data-sort', 'size');
        headers[1].style.cursor = 'pointer';
        headers[1].addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            sortTable('size');
            return false;
        });
    }
    
    // Date column (third)
    if (headers.length > 2 && headers[2]) {
        headers[2].setAttribute('data-sort', 'date');
        headers[2].style.cursor = 'pointer';
        headers[2].addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            sortTable('date');
            return false;
        });
    }
}

// Function to sort table elements
function sortTable(field) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    if (currentSortField === field) {
        currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
    } else {
        currentSortField = field;
        currentSortOrder = field === 'date' ? 'desc' : 'asc';
    }
    
    const table = document.querySelector('table');
    const tbody = table.querySelector('tbody');
    
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    const parentRow = rows.find(row => {
        const link = row.querySelector('td:first-child a');
        return link && (link.textContent.includes('Parent') || link.textContent.includes('↑'));
    });
    
    const rowsWithoutParent = parentRow ? rows.filter(row => row !== parentRow) : rows;
    
    const dirRows = rowsWithoutParent.filter(row => isDirectory(row));
    const fileRows = rowsWithoutParent.filter(row => !isDirectory(row));
    
    fileRows.sort((a, b) => {
        let aValue, bValue;
        
        if (field === 'name') {
            const aLink = a.querySelector('td:first-child a');
            const bLink = b.querySelector('td:first-child a');
            aValue = aLink ? aLink.textContent.trim().toLowerCase() : '';
            bValue = bLink ? bLink.textContent.trim().toLowerCase() : '';
        } else if (field === 'size') {
            const aSizeText = a.querySelector('td:nth-child(2)').textContent.trim();
            const bSizeText = b.querySelector('td:nth-child(2)').textContent.trim();
            aValue = parseFileSize(aSizeText);
            bValue = parseFileSize(bSizeText);
        } else if (field === 'date') {
            const aDateText = a.querySelector('td:last-child').textContent.trim();
            const bDateText = b.querySelector('td:last-child').textContent.trim();
            aValue = parseDate(aDateText);
            bValue = parseDate(bDateText);
        }
        
        const direction = currentSortOrder === 'asc' ? 1 : -1;
        
        if (aValue === undefined || aValue === null) return 1 * direction;
        if (bValue === undefined || bValue === null) return -1 * direction;
        
        if (aValue < bValue) return -1 * direction;
        if (aValue > bValue) return 1 * direction;
        return 0;
    });
    
    if (field === 'name') {
        dirRows.sort((a, b) => {
            const aLink = a.querySelector('td:first-child a');
            const bLink = b.querySelector('td:first-child a');
            const aValue = aLink ? aLink.textContent.trim().toLowerCase() : '';
            const bValue = bLink ? bLink.textContent.trim().toLowerCase() : '';
            const direction = currentSortOrder === 'asc' ? 1 : -1;
            if (aValue < bValue) return -1 * direction;
            if (aValue > bValue) return 1 * direction;
            return 0;
        });
    }
    
    updateSortIcons();
    
    while (tbody.firstChild) {
        tbody.removeChild(tbody.firstChild);
    }
    
    if (parentRow) {
        tbody.appendChild(parentRow);
    }
    
    dirRows.forEach(row => tbody.appendChild(row));
    fileRows.forEach(row => tbody.appendChild(row));
    
    updatePagination();
}

// Function to update sorting icons
function updateSortIcons() {
    const headers = document.querySelectorAll('th');
    
    headers.forEach(header => {
        const existingIcon = header.querySelector('.sort-icon');
        if (existingIcon) {
            const field = header.getAttribute('data-sort');
            if (field && field === currentSortField) {
                existingIcon.innerHTML = currentSortOrder === 'asc' ? '↑' : '↓';
                existingIcon.style.opacity = '1';
            } else {
                existingIcon.innerHTML = '⇕';
                existingIcon.style.opacity = '0.5';
            }
        }
    });
}

// Function to update pagination
function updatePagination() {
    const table = document.querySelector('table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const totalPages = Math.ceil(rows.length / filesPerPage);
    
    if (currentPage > totalPages) {
        currentPage = totalPages || 1;
    }
    
    rows.forEach((row, index) => {
        const startIndex = (currentPage - 1) * filesPerPage;
        const endIndex = startIndex + filesPerPage - 1;
        
        if (index >= startIndex && index <= endIndex) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    
    updatePaginationControls(totalPages);
}

// Function to update pagination controls
function updatePaginationControls(totalPages) {
    let paginationContainer = document.getElementById('pagination-container');
    if (paginationContainer) {
        paginationContainer.remove();
    }
    
    if (totalPages <= 1) {
        return;
    }
    
    paginationContainer = document.createElement('div');
    paginationContainer.id = 'pagination-container';
    
    const firstPageButton = document.createElement('button');
    firstPageButton.textContent = '«';
    firstPageButton.title = 'First page';
    firstPageButton.className = 'pagination-btn';
    firstPageButton.disabled = currentPage === 1;
    firstPageButton.addEventListener('click', () => {
        if (currentPage !== 1) {
            currentPage = 1;
            updatePagination();
        }
    });
    paginationContainer.appendChild(firstPageButton);
    
    const prevButton = document.createElement('button');
    prevButton.textContent = '‹';
    prevButton.title = 'Previous page';
    prevButton.className = 'pagination-btn';
    prevButton.disabled = currentPage === 1;
    prevButton.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            updatePagination();
        }
    });
    paginationContainer.appendChild(prevButton);
    
    const maxPageButtons = 3;
    let startPage = Math.max(1, currentPage - Math.floor(maxPageButtons / 2));
    let endPage = Math.min(totalPages, startPage + maxPageButtons - 1);
    
    if (endPage - startPage + 1 < maxPageButtons && startPage > 1) {
        startPage = Math.max(1, endPage - maxPageButtons + 1);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const pageButton = document.createElement('button');
        pageButton.textContent = i.toString();
        pageButton.className = i === currentPage ? 'pagination-btn pagination-btn-active' : 'pagination-btn pagination-btn-number';
        pageButton.disabled = i === currentPage;
        pageButton.addEventListener('click', () => {
            currentPage = i;
            updatePagination();
        });
        paginationContainer.appendChild(pageButton);
    }
    
    const nextButton = document.createElement('button');
    nextButton.textContent = '›';
    nextButton.title = 'Next page';
    nextButton.className = 'pagination-btn';
    nextButton.disabled = currentPage === totalPages;
    nextButton.addEventListener('click', () => {
        if (currentPage < totalPages) {
            currentPage++;
            updatePagination();
        }
    });
    paginationContainer.appendChild(nextButton);
    
    const lastPageButton = document.createElement('button');
    lastPageButton.textContent = '»';
    lastPageButton.title = 'Last page';
    lastPageButton.className = 'pagination-btn';
    lastPageButton.disabled = currentPage === totalPages;
    lastPageButton.addEventListener('click', () => {
        if (currentPage !== totalPages) {
            currentPage = totalPages;
            updatePagination();
        }
    });
    paginationContainer.appendChild(lastPageButton);
    
    const pageInfo = document.createElement('span');
    pageInfo.textContent = `${currentPage} of ${totalPages}`;
    pageInfo.className = 'pagination-info';
    paginationContainer.appendChild(pageInfo);
    
    const table = document.querySelector('table');
    table.parentNode.insertBefore(paginationContainer, table.nextSibling);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    applyThemePreference();
    
    // Create theme toggle button
    const footer = document.getElementById('footer');
    if (footer) {
        let themeToggleBtn = document.getElementById('theme-toggle');
        
        if (!themeToggleBtn) {
            themeToggleBtn = document.createElement('button');
            themeToggleBtn.id = 'theme-toggle';
            themeToggleBtn.className = 'theme-toggle-btn';
            themeToggleBtn.onclick = toggleTheme;
            footer.appendChild(themeToggleBtn);
        }
        
        const currentTheme = document.documentElement.classList.contains('dark-theme') ? 'dark' : 'light';
        updateThemeButton(currentTheme);
    }
    
    // Generate breadcrumb path
    function generateBreadcrumb() {
        function joinUntil(array, index, separator) {
            var result = [];
            for (var i = 0; i <= index; i++) {
                result.push(array[i]);
            }
            return result.join(separator);
        }
        
        var path = document.querySelector('.js-path');
        if (!path) return;
        
        var pathParts = location.pathname.split('/');
        
        for (var i = 0; i < pathParts.length;) {
            if (pathParts[i]) {
                i++;
            } else {
                pathParts.splice(i, 1);
            }
        }
        
        var pathContents = ['<a href="/">/</a>'];
        Array.prototype.forEach.call(pathParts, function(part, index) {
            pathContents.push('<a href="/' + joinUntil(pathParts, index, '/') + '">' + decodeURI(part) + '</a>');
        });
        
        path.innerHTML = pathContents.join('&rsaquo;');
        
        var breadcrumbTextParts = ['/'];
        Array.prototype.forEach.call(pathParts, function(part) {
            breadcrumbTextParts.push(decodeURI(part));
        });
        var breadcrumbText = breadcrumbTextParts.join(' › ');
        document.title = 'Index of ' + breadcrumbText.replace(/\/ › /g, '/').replace(/ › /g,'/');
    }
    
    generateBreadcrumb();
    
    // Initialize table headers
    initializeTableHeaders();
    
    // Initialize pagination
    updatePagination();
});
