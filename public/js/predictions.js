
/**
 * predictions.js
 * 
 * JavaScript functions for the lottery prediction pages
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    initPredictionsPageComponents();
});

/**
 * Initialize predictions page components
 */
function initPredictionsPageComponents() {
    initializePredictionForm();
    initializeDigitTypeToggle();
    initializePredictionCards();
    
    // If chart containers exist, initialize charts
    if (document.querySelector('.chart-container')) {
        loadPredictionCharts();
    }
}

/**
 * Initialize the prediction form
 */
function initializePredictionForm() {
    const predictionForm = document.getElementById('predictionForm');
    
    if (predictionForm) {
        // Set default draw date
        const drawDateInput = predictionForm.querySelector('input[name="draw_date"]');
        if (drawDateInput && !drawDateInput.value) {
            drawDateInput.value = getNextDrawDate();
        }
        
        // Handle form submission
        predictionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(predictionForm);
            const params = new URLSearchParams(formData);
            
            // Show loading indicator
            showPredictionLoading();
            
            // Generate predictions using AJAX
            fetch(`generate_predictions.php?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        updatePredictionsContent(data);
                    } else {
                        showPredictionError(data.message || 'เกิดข้อผิดพลาดในการทำนาย');
                    }
                })
                .catch(error => {
                    console.error('Error generating predictions:', error);
                    showPredictionError('เกิดข้อผิดพลาดในการทำนาย');
                })
                .finally(() => {
                    hidePredictionLoading();
                });
        });
    }
}

/**
 * Initialize digit type toggle
 */
function initializeDigitTypeToggle() {
    const digitTypeButtons = document.querySelectorAll('.digit-type-toggle .btn');
    
    digitTypeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const digitType = button.getAttribute('data-digit-type');
            
            // Update active button
            digitTypeButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            button.classList.add('active');
            
            // Update hidden input
            const digitTypeInput = document.querySelector('input[name="digit_type"]');
            if (digitTypeInput) {
                digitTypeInput.value = digitType;
            }
            
            // If we're on the results page, toggle visibility of prediction result sections
            const resultSections = document.querySelectorAll('.prediction-results');
            if (resultSections.length > 0) {
                resultSections.forEach(section => {
                    if (section.getAttribute('data-digit-type') === digitType) {
                        section.style.display = 'block';
                    } else {
                        section.style.display = 'none';
                    }
                });
                
                // Redraw charts in the visible section
                redrawPredictionCharts(digitType);
            } else {
                // If we're on the prediction form page and not viewing results,
                // submit the form automatically when digit type is changed
                const form = document.getElementById('predictionForm');
                if (form) {
                    form.submit();
                }
            }
        });
    });
}

/**
 * Initialize prediction cards
 */
function initializePredictionCards() {
    const predictionCards = document.querySelectorAll('.prediction-card');
    
    predictionCards.forEach(card => {
        card.addEventListener('click', function() {
            // Toggle selection
            card.classList.toggle('selected');
            
            // Update selection count
            updateSelectionCount();
        });
    });
}

/**
 * Update selection count
 */
function updateSelectionCount() {
    const selectedCards = document.querySelectorAll('.prediction-card.selected');
    const countDisplay = document.getElementById('selectedCount');
    
    if (countDisplay) {
        countDisplay.textContent = selectedCards.length;
    }
    
    // Enable/disable print selected button
    const printSelectedButton = document.getElementById('printSelected');
    if (printSelectedButton) {
        printSelectedButton.disabled = selectedCards.length === 0;
    }
}

/**
 * Show prediction loading indicator
 */
function showPredictionLoading() {
    const content = document.querySelector('.predictions-content');
    
    if (content) {
        // Create loading overlay if it doesn't exist
        let loadingOverlay = document.querySelector('.loading-overlay');
        
        if (!loadingOverlay) {
            loadingOverlay = document.createElement('div');
            loadingOverlay.classList.add('loading-overlay');
            loadingOverlay.innerHTML = `
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">กำลังทำนาย...</span>
                </div>
                <p class="mt-2">กำลังวิเคราะห์และทำนายผล...</p>
            `;
            document.body.appendChild(loadingOverlay);
        }
        
        loadingOverlay.style.display = 'flex';
    }
}

/**
 * Hide prediction loading indicator
 */
function hidePredictionLoading() {
    const loadingOverlay = document.querySelector('.loading-overlay');
    
    if (loadingOverlay) {
        loadingOverlay.style.display = 'none';
    }
}

/**
 * Show prediction error message
 * 
 * @param {string} message Error message
 */
function showPredictionError(message) {
    // Create alert element
    const alertElement = document.createElement('div');
    alertElement.classList.add('alert', 'alert-danger', 'alert-dismissible', 'fade', 'show');
    alertElement.setAttribute('role', 'alert');
    alertElement.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Add to the page
    const contentArea = document.querySelector('.predictions-content');
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
 * Update predictions content
 * 
 * @param {object} data Predictions data
 */
function updatePredictionsContent(data) {
    const contentArea = document.querySelector('.predictions-content');
    
    if (contentArea && data.html) {
        contentArea.innerHTML = data.html;
        
        // Reinitialize components
        initializeTooltips();
        initializePopovers();
        initializePredictionCards();
        
        // Reinitialize digit type toggle
        initializeDigitTypeToggle();
        
        // Reinitialize charts
        if (data.charts) {
            for (const chartId in data.charts) {
                createChart(chartId, data.charts[chartId]);
            }
        }
    }
}

/**
 * Load prediction charts
 */
function loadPredictionCharts() {
    const chartContainers = document.querySelectorAll('.chart-container');
    
    chartContainers.forEach(container => {
        const canvas = container.querySelector('canvas');
        
        if (canvas) {
            const chartId = canvas.id;
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
                    const chartType = canvas.getAttribute('data-chart-type') || 'bar';
                    
                    createSimpleChart(chartId, chartType, labels, values);
                }
            }
        });
    }
    
    /**
     * Redraw prediction charts for a specific digit type
     * 
     * @param {string} digitType Digit type 
     */
    function redrawPredictionCharts(digitType) {
        const section = document.querySelector(`.prediction-results[data-digit-type="${digitType}"]`);
        
        if (section) {
            const charts = section.querySelectorAll('canvas');
            
            charts.forEach(chart => {
                if (chart.chart) {
                    chart.chart.resize();
                }
            });
        }
    }
    
    /**
     * Print selected predictions
     */
    function printSelectedPredictions() {
        const selectedCards = document.querySelectorAll('.prediction-card.selected');
        
        if (selectedCards.length === 0) {
            return;
        }
        
        // Create print window
        const printWindow = window.open('', '_blank');
        
        // Get current draw date
        const drawDate = document.querySelector('input[name="draw_date"]')?.value || '';
        const formattedDate = drawDate ? formatThaiDate(drawDate) : '';
        
        // Get active digit type
        const activeTypeButton = document.querySelector('.digit-type-toggle .btn.active');
        const digitTypeLabel = activeTypeButton?.innerText || '';
        
        // Create print content
        let content = `
            <!DOCTYPE html>
            <html lang="th">
            <head>
                <meta charset="UTF-8">
                <title>การทำนายที่เลือก - งวดวันที่ ${formattedDate}</title>
                <style>
                    body { font-family: 'Sarabun', sans-serif; padding: 20px; }
                    h1 { font-size: 24px; text-align: center; margin-bottom: 20px; }
                    .predictions { display: flex; flex-wrap: wrap; justify-content: center; }
                    .prediction { border: 1px solid #ddd; border-radius: 10px; padding: 15px; 
                                 margin: 10px; width: 150px; text-align: center; }
                    .digit { font-size: 32px; font-weight: bold; margin: 10px 0; }
                    .info { font-size: 14px; color: #666; }
                    .confidence { font-size: 16px; margin: 10px 0; }
                    .date { font-size: 14px; margin-top: 30px; text-align: center; }
                    @media print {
                        @page { margin: 0.5cm; }
                        body { padding: 0; }
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <h1>การทำนายผลสลากกินแบ่งรัฐบาล ${digitTypeLabel}<br>งวดวันที่ ${formattedDate}</h1>
                
                <div class="predictions">
        `;
        
        // Add each selected prediction
        selectedCards.forEach(card => {
            const digit = card.querySelector('.prediction-digit')?.innerText || '';
            const confidence = card.querySelector('.progress-bar')?.style.width || '';
            
            content += `
                <div class="prediction">
                    <div class="digit">${digit}</div>
                    <div class="confidence">ความเชื่อมั่น: ${confidence}</div>
                </div>
            `;
        });
        
        content += `
                </div>
                
                <div class="date">
                    พิมพ์เมื่อ: ${new Date().toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}
                </div>
                
                <div class="no-print" style="text-align: center; margin-top: 20px;">
                    <button onclick="window.print()">พิมพ์</button>
                    <button onclick="window.close()">ปิด</button>
                </div>
            </body>
            </html>
        `;
        
        // Write to print window
        printWindow.document.open();
        printWindow.document.write(content);
        printWindow.document.close();
        
        // Auto print if browser supports it
        setTimeout(function() {
            printWindow.print();
        }, 500);
    }
