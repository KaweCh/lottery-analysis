
/**
 * statistics.js
 * 
 * JavaScript functions for the statistical analysis and visualization pages
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    initStatisticsPageComponents();
});

/**
 * Initialize statistics page components
 */
function initStatisticsPageComponents() {
    initializeFilterForm();
    initializeTabSwitching();
    setupDateRangePickers();
    handlePrintButton();
    
    // If chart containers exist, initialize charts
    if (document.querySelector('.chart-container')) {
        loadChartData();
    }
}

/**
 * Initialize the filter form
 */
function initializeFilterForm() {
    const filterForm = document.getElementById('filterForm');
    
    if (filterForm) {
        // Handle form submission
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(filterForm);
            const params = new URLSearchParams(formData);
            
            // Show loading indicator
            showLoading();
            
            // Update the statistics content using AJAX
            fetch(`${window.location.pathname}?${params.toString()}&ajax=1`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        updateStatisticsContent(data);
                    } else {
                        showError(data.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูล');
                    }
                })
                .catch(error => {
                    console.error('Error fetching statistics data:', error);
                    showError('เกิดข้อผิดพลาดในการโหลดข้อมูล');
                })
                .finally(() => {
                    hideLoading();
                });
        });
        
        // Handle reset button
        const resetButton = filterForm.querySelector('button[type="reset"]');
        if (resetButton) {
            resetButton.addEventListener('click', function() {
                setTimeout(() => {
                    filterForm.dispatchEvent(new Event('submit'));
                }, 10);
            });
        }
    }
}

/**
 * Initialize tab switching
 */
function initializeTabSwitching() {
    const tabButtons = document.querySelectorAll('.statistics-tabs .nav-link');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Add active class to clicked tab
            button.classList.add('active');
            
            // Hide all tab content
            const tabContents = document.querySelectorAll('.tab-content .tab-pane');
            tabContents.forEach(content => {
                content.classList.remove('show', 'active');
            });
            
            // Show selected tab content
            const targetId = button.getAttribute('href');
            const targetContent = document.querySelector(targetId);
            if (targetContent) {
                targetContent.classList.add('show', 'active');
            }
            
            // Update URL hash
            history.pushState({}, '', targetId);
            
            // Redraw charts in the active tab
            redrawChartsInTab(targetId);
        });
    });
    
    // Set active tab based on URL hash
    const hash = window.location.hash;
    if (hash) {
        const activeTab = document.querySelector(`.statistics-tabs .nav-link[href="${hash}"]`);
        if (activeTab) {
            activeTab.click();
        }
    }
}

/**
 * Redraw charts in a specific tab
 * 
 * @param {string} tabId Tab ID
 */
function redrawChartsInTab(tabId) {
    const tabContent = document.querySelector(tabId);
    
    if (tabContent) {
        const charts = tabContent.querySelectorAll('canvas');
        
        charts.forEach(chart => {
            if (chart.chart) {
                chart.chart.resize();
            }
        });
    }
}

/**
 * Setup date range pickers
 */
function setupDateRangePickers() {
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    
    if (startDateInput && endDateInput) {
        // If we're using a datepicker library that requires manual initialization
        if (typeof $(startDateInput).datepicker === 'function') {
            $(startDateInput).datepicker({
                format: 'yyyy-mm-dd',
                todayBtn: 'linked',
                clearBtn: true,
                autoclose: true,
                language: 'th',
                todayHighlight: true
            }).on('changeDate', function(e) {
                $(endDateInput).datepicker('setStartDate', e.date);
            });
            
            $(endDateInput).datepicker({
                format: 'yyyy-mm-dd',
                todayBtn: 'linked',
                clearBtn: true,
                autoclose: true,
                language: 'th',
                todayHighlight: true
            }).on('changeDate', function(e) {
                $(startDateInput).datepicker('setEndDate', e.date);
            });
        }
    }
}

/**
 * Handle print button click
 */
function handlePrintButton() {
    const printButton = document.getElementById('printStatistics');
    
    if (printButton) {
        printButton.addEventListener('click', function() {
            window.print();
        });
    }
}

/**
 * Show loading indicator
 */
function showLoading() {
    const content = document.querySelector('.statistics-content');
    
    if (content) {
        // Create loading overlay if it doesn't exist
        let loadingOverlay = document.querySelector('.loading-overlay');
        
        if (!loadingOverlay) {
            loadingOverlay = document.createElement('div');
            loadingOverlay.classList.add('loading-overlay');
            loadingOverlay.innerHTML = `
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">กำลังโหลด...</span>
                </div>
                <p class="mt-2">กำลังโหลดข้อมูล...</p>
            `;
            document.body.appendChild(loadingOverlay);
        }
        
        loadingOverlay.style.display = 'flex';
    }
}

/**
 * Hide loading indicator
 */
function hideLoading() {
    const loadingOverlay = document.querySelector('.loading-overlay');
    
    if (loadingOverlay) {
        loadingOverlay.style.display = 'none';
    }
}

/**
 * Show error message
 * 
 * @param {string} message Error message
 */
function showError(message) {
    // Create alert element
    const alertElement = document.createElement('div');
    alertElement.classList.add('alert', 'alert-danger', 'alert-dismissible', 'fade', 'show');
    alertElement.setAttribute('role', 'alert');
    alertElement.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Add to the page
    const contentArea = document.querySelector('.statistics-content');
    if (contentArea) {
        contentArea.prepend(alertElement);
    } else {
        document.querySelector('.container').prepend(alertElement);
    }
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        const dismissButton = alertElement.querySelector('.btn-close');
        if (dismissButton) {
            dismissButton.click();
        }
    }, 5000);
}

/**
 * Update statistics content
 * 
 * @param {object} data Statistics data
 */
function updateStatisticsContent(data) {
    const contentArea = document.querySelector('.statistics-content');
    
    if (contentArea && data.html) {
        contentArea.innerHTML = data.html;
        
        // Reinitialize components
        initializeTooltips();
        initializePopovers();
        
        // Reinitialize charts
        if (data.charts) {
            for (const chartId in data.charts) {
                createChart(chartId, data.charts[chartId]);
            }
        }
    }
}

/**
 * Load chart data
 */
function loadChartData() {
    const chartContainers = document.querySelectorAll('.chart-container');
    
    chartContainers.forEach(container => {
        const canvas = container.querySelector('canvas');
        
        if (canvas) {
            const chartId = canvas.id;
            const chartType = canvas.getAttribute('data-chart-type') || 'bar';
            const dataUrl = canvas.getAttribute('data-url');
            
            if (dataUrl) {
                // Show loading indicator
                const loadingIndicator = document.createElement('div');
                loadingIndicator.classList.add('chart-loader');
                loadingIndicator.innerHTML = '<div class="chart-loader-spinner"></div>';
                container.appendChild(loadingIndicator);
                
                // Fetch chart data
                fetch(dataUrl)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            createChart(chartId, data.chart);
                        } else {
                            showChartError(container, data.message || 'ไม่สามารถโหลดข้อมูลกราฟได้');
                        }
                    })
                    .catch(error => {
                        console.error('Error loading chart data:', error);
                        showChartError(container, 'เกิดข้อผิดพลาดในการโหลดข้อมูลกราฟ');
                    })
                    .finally(() => {
                        // Remove loading indicator
                        container.removeChild(loadingIndicator);
                    });
            } else if (canvas.getAttribute('data-labels') && canvas.getAttribute('data-values')) {
                // Create chart from data attributes
                const labels = JSON.parse(canvas.getAttribute('data-labels'));
                const values = JSON.parse(canvas.getAttribute('data-values'));
                
                createSimpleChart(chartId, chartType, labels, values);
            }
        }
    });
}

/**
 * Show chart error
 * 
 * @param {HTMLElement} container Chart container
 * @param {string} message Error message
 */
function showChartError(container, message) {
    const errorIndicator = document.createElement('div');
    errorIndicator.classList.add('chart-no-data');
    errorIndicator.innerHTML = `
        <div class="chart-no-data-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="chart-no-data-text">${message}</div>
    `;
    container.appendChild(errorIndicator);
}

/**
 * Create simple chart from provided data
 * 
 * @param {string} chartId Chart canvas ID
 * @param {string} chartType Chart type (bar, line, etc.)
 * @param {array} labels Chart labels
 * @param {array} values Chart values
 */
function createSimpleChart(chartId, chartType, labels, values) {
    const ctx = document.getElementById(chartId).getContext('2d');
    
    new Chart(ctx, {
        type: chartType,
        data: {
            labels: labels,
            datasets: [{
                label: 'ค่า',
                data: values,
                backgroundColor: 'rgba(78, 115, 223, 0.5)',
                borderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}
