/**
 * main.js
 * 
 * Main JavaScript file for the Thai Lottery Analysis system
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeTooltips();
    initializePopovers();
    setActiveNavItem();
    handleFlashMessages();
    initializeCollapsibleCards();
    initializeDatePickers();
    initializeBackToTop();
});

/**
 * Initialize Bootstrap Tooltips
 */
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Initialize Bootstrap Popovers
 */
function initializePopovers() {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

/**
 * Set active navigation item based on current URL
 */
function setActiveNavItem() {
    const currentLocation = window.location.pathname;
    
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && currentLocation.includes(href) && href !== '#' && href !== '/') {
            link.classList.add('active');
        } else if (href === '/' && currentLocation === '/') {
            link.classList.add('active');
        }
    });
}

/**
 * Handle auto-dismissing flash messages
 */
function handleFlashMessages() {
    const flashMessages = document.querySelectorAll('.alert.alert-dismissible');
    
    flashMessages.forEach(message => {
        setTimeout(() => {
            const closeButton = message.querySelector('.btn-close');
            if (closeButton) {
                closeButton.click();
            } else {
                message.style.display = 'none';
            }
        }, 5000);
    });
}

/**
 * Initialize collapsible cards
 */
function initializeCollapsibleCards() {
    const collapseButtons = document.querySelectorAll('[data-bs-toggle="collapse"]');
    
    collapseButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(button.getAttribute('data-bs-target'));
            
            if (target) {
                const isCollapsed = target.classList.contains('show');
                const icon = button.querySelector('i');
                
                if (icon) {
                    if (isCollapsed) {
                        icon.classList.remove('fa-compress');
                        icon.classList.add('fa-expand');
                    } else {
                        icon.classList.remove('fa-expand');
                        icon.classList.add('fa-compress');
                    }
                }
            }
        });
    });
}

/**
 * Initialize Bootstrap Datepickers
 */
function initializeDatePickers() {
    const datepickerInputs = document.querySelectorAll('.datepicker');
    
    datepickerInputs.forEach(input => {
        const options = {
            format: 'yyyy-mm-dd',
            todayBtn: 'linked',
            clearBtn: true,
            autoclose: true,
            language: 'th',
            todayHighlight: true
        };
        
        // If we're using a datepicker library that requires manual initialization
        if (typeof $(input).datepicker === 'function') {
            $(input).datepicker(options);
        }
    });
}

/**
 * Initialize Back to Top button
 */
function initializeBackToTop() {
    // Create back to top button if it doesn't exist
    if (!document.querySelector('.back-to-top')) {
        const button = document.createElement('button');
        button.classList.add('back-to-top');
        button.innerHTML = '<i class="fas fa-arrow-up"></i>';
        document.body.appendChild(button);
        
        // Show/hide based on scroll position
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                button.classList.add('show');
            } else {
                button.classList.remove('show');
            }
        });
        
        // Scroll to top when clicked
        button.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
}

/**
 * Format number with thousand separator
 * 
 * @param {number} number Number to format
 * @param {number} decimals Number of decimals
 * @return {string} Formatted number
 */
function formatNumber(number, decimals = 0) {
    return parseFloat(number).toLocaleString('th-TH', {
        minimumFractionDigits: decimals, 
        maximumFractionDigits: decimals
    });
}

/**
 * Format date to Thai format
 * 
 * @param {string} dateString Date string in YYYY-MM-DD format
 * @return {string} Formatted date
 */
function formatThaiDate(dateString) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    const thaiMonths = [
        'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
        'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
    ];
    
    const day = date.getDate();
    const month = thaiMonths[date.getMonth()];
    // Convert to Buddhist era (CE + 543)
    const year = date.getFullYear() + 543;
    
    return `${day} ${month} ${year}`;
}

/**
 * Get color based on value range
 * 
 * @param {number} value Value to determine color
 * @param {number} max Maximum value in the range
 * @param {boolean} reverse Reverse color range (high = blue, low = red)
 * @return {string} CSS color string
 */
function getColor(value, max = 100, reverse = false) {
    // Convert value to percentage
    const percentage = Math.min(100, Math.max(0, (value / max) * 100));
    
    let r, g, b;
    
    if (reverse) {
        // Blue (low) to Red (high)
        r = Math.floor(percentage * 2.55);
        g = Math.floor((100 - percentage) * 1.5);
        b = Math.floor((100 - percentage) * 2.55);
    } else {
        // Red (low) to Blue (high)
        r = Math.floor((100 - percentage) * 2.55);
        g = Math.floor((percentage < 50) ? percentage * 1.5 : (100 - percentage) * 3);
        b = Math.floor(percentage * 2.55);
    }
    
    return `rgb(${r}, ${g}, ${b})`;
}

/**
 * Download a chart as PNG image
 * 
 * @param {string} chartId ID of the chart canvas
 */
function downloadChart(chartId) {
    const canvas = document.getElementById(chartId);
    
    if (canvas) {
        // Get chart title
        let title = '';
        const parent = canvas.closest('.card');
        if (parent) {
            const titleElement = parent.querySelector('.card-header h6');
            if (titleElement) {
                title = titleElement.textContent;
            }
        }
        
        // Create a filename
        let filename = title ? `${title.replace(/[^\w\s]/gi, '').replace(/\s+/g, '_')}.png` : 'chart.png';
        
        // Create a link element
        const link = document.createElement('a');
        link.download = filename;
        link.href = canvas.toDataURL('image/png');
        link.click();
    }
}

/**
 * Get next lottery draw date
 * 
 * @return {string} Next draw date in YYYY-MM-DD format
 */
function getNextDrawDate() {
    const today = new Date();
    const day = today.getDate();
    const month = today.getMonth();
    const year = today.getFullYear();
    
    // Thai lottery is drawn on the 1st and 16th of each month
    let nextDraw;
    
    if (day < 16) {
        // Next draw is on the 16th of current month
        nextDraw = new Date(year, month, 16);
    } else {
        // Next draw is on the 1st of next month
        nextDraw = new Date(year, month + 1, 1);
    }
    
    // Format date to YYYY-MM-DD
    return nextDraw.toISOString().split('T')[0];
}

/**
 * Get Thai day of week
 * 
 * @param {Date} date Date object
 * @return {string} Thai day of week
 */
function getThaiDayOfWeek(date) {
    const dayOfWeek = date.getDay();
    const thaiDays = [
        'อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'
    ];
    
    return thaiDays[dayOfWeek];
}

/**
 * Generate a unique ID
 * 
 * @param {string} prefix Optional ID prefix
 * @return {string} Unique ID
 */
function generateUniqueId(prefix = 'id') {
    return `${prefix}_${Math.random().toString(36).substr(2, 9)}`;
}

/**
 * Check if a string is a valid date
 * 
 * @param {string} dateString Date string to check
 * @return {boolean} True if valid date
 */
function isValidDate(dateString) {
    if (!dateString) return false;
    
    // First check for the pattern
    if (!/^\d{4}-\d{2}-\d{2}$/.test(dateString)) return false;
    
    // Parse the date parts to integers
    const parts = dateString.split('-');
    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10);
    const day = parseInt(parts[2], 10);
    
    // Check the ranges of month and day
    if (month < 1 || month > 12) return false;
    
    const daysInMonth = new Date(year, month, 0).getDate();
    if (day < 1 || day > daysInMonth) return false;
    
    return true;
}
