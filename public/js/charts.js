
/**
 * charts.js
 * 
 * JavaScript functions for chart creation and management
 */

/**
 * Create chart based on data
 * 
 * @param {string} chartId Chart canvas ID
 * @param {object} chartData Chart data
 */
function createChart(chartId, chartData) {
    // Get canvas context
    const canvas = document.getElementById(chartId);
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // If chart already exists, destroy it
    if (canvas.chart) {
        canvas.chart.destroy();
    }
    
    // Check chart type
    const chartType = chartData.type || 'bar';
    
    // Create chart options based on type
    let options = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: chartData.legend !== false,
                position: 'top',
                labels: {
                    font: {
                        family: '"Sarabun", sans-serif',
                        size: 12
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleFont: {
                    family: '"Sarabun", sans-serif',
                    size: 14
                },
                bodyFont: {
                    family: '"Sarabun", sans-serif',
                    size: 13
                },
                padding: 10
            }
        }
    };
    
    // Add specific options based on chart type
    switch (chartType) {
        case 'bar':
            options.scales = {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: '"Sarabun", sans-serif',
                            size: 12
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: {
                            family: '"Sarabun", sans-serif',
                            size: 12
                        }
                    }
                }
            };
            break;
            
        case 'line':
            options.elements = {
                line: {
                    tension: 0.3
                },
                point: {
                    radius: 4,
                    hitRadius: 10,
                    hoverRadius: 6
                }
            };
            options.scales = {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: '"Sarabun", sans-serif',
                            size: 12
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: {
                            family: '"Sarabun", sans-serif',
                            size: 12
                        }
                    }
                }
            };
            break;
            
        case 'pie':
        case 'doughnut':
            options.cutout = chartType === 'doughnut' ? '50%' : undefined;
            options.plugins.legend.position = 'right';
            break;
            
        case 'polarArea':
            options.scales = {
                r: {
                    beginAtZero: true
                }
            };
            break;
            
        case 'radar':
            options.scales = {
                r: {
                    beginAtZero: true,
                    ticks: {
                        font: {
                            family: '"Sarabun", sans-serif',
                            size: 12
                        }
                    },
                    pointLabels: {
                        font: {
                            family: '"Sarabun", sans-serif',
                            size: 12
                        }
                    }
                }
            };
            break;
            
        case 'horizontalBar':
            options.indexAxis = 'y';
            options.scales = {
                x: {
                    beginAtZero: true,
                    ticks: {
                        font: {
                            family: '"Sarabun", sans-serif',
                            size: 12
                        }
                    }
                },
                y: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: '"Sarabun", sans-serif',
                            size: 12
                        }
                    }
                }
            };
            chartData.type = 'bar'; // Set to bar with horizontal axis
            break;
    }
    
    // Merge options with provided options
    if (chartData.options) {
        options = deepMerge(options, chartData.options);
    }
    
    // Create the chart
    canvas.chart = new Chart(ctx, {
        type: chartData.type === 'horizontalBar' ? 'bar' : chartData.type,
        data: chartData.data,
        options: options
    });
    
    // Store reference to the chart
    canvas.chart = canvas.chart;
}

/**
 * Create frequency distribution chart
 * 
 * @param {string} chartId Chart canvas ID
 * @param {object} data Frequency distribution data
 */
function createFrequencyChart(chartId, data) {
    // Prepare data for Chart.js
    const labels = [];
    const frequencies = [];
    const percentages = [];
    
    data.distribution.forEach(item => {
        labels.push(item.digit);
        frequencies.push(item.count);
        percentages.push(item.percentage);
    });
    
    // Create chart data
    const chartData = {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    type: 'bar',
                    label: 'จำนวนครั้งที่ออก',
                    data: frequencies,
                    backgroundColor: 'rgba(78, 115, 223, 0.7)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 1,
                    order: 1
                },
                {
                    type: 'line',
                    label: 'เปอร์เซ็นต์',
                    data: percentages,
                    borderColor: 'rgba(246, 194, 62, 1)',
                    backgroundColor: 'rgba(246, 194, 62, 0.1)',
                    pointBackgroundColor: 'rgba(246, 194, 62, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(246, 194, 62, 1)',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: false,
                    tension: 0.3,
                    borderWidth: 3,
                    yAxisID: 'y1',
                    order: 0
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'จำนวนครั้ง',
                        font: {
                            family: '"Sarabun", sans-serif',
                            size: 14,
                            weight: 'bold'
                        }
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'เปอร์เซ็นต์ (%)',
                        font: {
                            family: '"Sarabun", sans-serif',
                            size: 14,
                            weight: 'bold'
                        }
                    },
                    grid: {
                        drawOnChartArea: false
                    },
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: data.field_label || 'เลข',
                        font: {
                            family: '"Sarabun", sans-serif',
                            size: 14,
                            weight: 'bold'
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.datasetIndex === 0) {
                                label += context.parsed.y;
                            } else {
                                label += context.parsed.y + '%';
                            }
                            return label;
                        }
                    }
                }
            }
        }
    };
    
    // Create the chart
    createChart(chartId, chartData);
}

/**
 * Create position frequency heatmap
 * 
 * @param {string} chartId Chart canvas ID
 * @param {object} data Position frequency data
 */
function createPositionHeatmap(chartId, data) {
    // Get canvas element
    const canvas = document.getElementById(chartId);
    if (!canvas) return;
    
    // If chart already exists, destroy it
    if (canvas.chart) {
        canvas.chart.destroy();
    }
    
    // Create container for heatmap
    const container = canvas.closest('.chart-area');
    if (!container) return;
    
    // Clear container
    container.innerHTML = '';
    
    // Create heatmap
    const heatmap = document.createElement('div');
    heatmap.classList.add('heat-map-container');
    
    // Create position headers
    const headerRow = document.createElement('div');
    headerRow.classList.add('heat-map-row');
    
    // Add empty cell for top-left corner
    const cornerCell = document.createElement('div');
    cornerCell.classList.add('heat-map-header');
    cornerCell.textContent = 'ตำแหน่ง / เลข';
    headerRow.appendChild(cornerCell);
    
    // Add digit headers (0-9)
    for (let digit = 0; digit <= 9; digit++) {
        const digitHeader = document.createElement('div');
        digitHeader.classList.add('heat-map-header');
        digitHeader.textContent = digit;
        headerRow.appendChild(digitHeader);
    }
    
    heatmap.appendChild(headerRow);
    
    // Add position rows
    const positions = data.positions;
    for (let i = 0; i < positions; i++) {
        const positionRow = document.createElement('div');
        positionRow.classList.add('heat-map-row');
        
        // Add position label
        const positionLabel = document.createElement('div');
        positionLabel.classList.add('heat-map-header');
        positionLabel.textContent = `ตำแหน่งที่ ${i + 1}`;
        positionRow.appendChild(positionLabel);
        
        // Add cells for each digit (0-9)
        for (let digit = 0; digit <= 9; digit++) {
            const cell = document.createElement('div');
            cell.classList.add('heat-map-cell');
            
            // Get percentage for this position and digit
            const percentage = data.percentages[i][digit];
            
            // Set background color based on percentage
            const hue = 220; // Blue hue
            const saturation = 100;
            const lightness = 100 - (percentage);
            cell.style.backgroundColor = `hsl(${hue}, ${saturation}%, ${Math.max(50, lightness)}%)`;
            
            // Set text color based on lightness
            cell.style.color = lightness < 70 ? 'white' : 'black';
            
            // Set percentage
            cell.textContent = `${percentage.toFixed(1)}%`;
            
            // Add tooltip data
            cell.setAttribute('title', `ตำแหน่งที่ ${i + 1}, เลข ${digit}: ${percentage.toFixed(1)}%`);
            
            // Add to row
            positionRow.appendChild(cell);
        }
        
        heatmap.appendChild(positionRow);
    }
    
    // Add legend
    const legend = document.createElement('div');
    legend.classList.add('heat-map-legend');
    legend.innerHTML = `
        <div class="heat-map-legend-item">
            <div class="heat-map-legend-color" style="background-color: hsl(220, 100%, 95%);"></div>
            <span>น้อย (0%)</span>
        </div>
        <div class="heat-map-legend-item">
            <div class="heat-map-legend-color" style="background-color: hsl(220, 100%, 80%);"></div>
            <span>ปานกลาง (20%)</span>
        </div>
        <div class="heat-map-legend-item">
            <div class="heat-map-legend-color" style="background-color: hsl(220, 100%, 65%);"></div>
            <span>สูง (35%)</span>
        </div>
        <div class="heat-map-legend-item">
            <div class="heat-map-legend-color" style="background-color: hsl(220, 100%, 50%);"></div>
            <span>สูงมาก (>50%)</span>
        </div>
    `;
    
    heatmap.appendChild(legend);
    container.appendChild(heatmap);
}

/**
 * Create pattern chart
 * 
 * @param {string} chartId Chart canvas ID
 * @param {object} data Pattern data
 */
function createPatternChart(chartId, data) {
    // Prepare data for Chart.js
    const patterns = Object.keys(data.patterns).slice(0, 10);
    const occurrences = patterns.map(p => data.patterns[p].occurrences);
    const patternLabels = patterns.map(p => data.patterns[p].pattern.join('-'));
    
    // Create chart data
    const chartData = {
        type: 'horizontalBar',
        data: {
            labels: patternLabels,
            datasets: [{
                label: 'จำนวนครั้งที่พบ',
                data: occurrences,
                backgroundColor: 'rgba(54, 185, 204, 0.7)',
                borderColor: 'rgba(54, 185, 204, 1)',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'จำนวนครั้งที่พบ',
                        font: {
                            family: '"Sarabun", sans-serif',
                            size: 14,
                            weight: 'bold'
                        }
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'รูปแบบ',
                        font: {
                            family: '"Sarabun", sans-serif',
                            size: 14,
                            weight: 'bold'
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        title: function(tooltipItem) {
                            const pattern = patterns[tooltipItem[0].dataIndex];
                            const patternData = data.patterns[pattern];
                            return `รูปแบบ: ${patternData.pattern.join('-')}`;
                        },
                        label: function(context) {
                            const pattern = patterns[context.dataIndex];
                            const patternData = data.patterns[pattern];
                            return [
                                `จำนวนครั้งที่พบ: ${patternData.occurrences}`,
                                `พบล่าสุด: ${patternData.dates[patternData.dates.length - 1] || 'ไม่มีข้อมูล'}`
                            ];
                        }
                    }
                }
            }
        }
    };
    
    // Create the chart
    createChart(chartId, chartData);
}

/**
 * Create trend line chart
 * 
 * @param {string} chartId Chart canvas ID
 * @param {object} data Trend data
 */
function createTrendLineChart(chartId, data) {
    // Prepare data for Chart.js
    const labels = data.trend_points.map(point => point.date);
    const values = data.trend_points.map(point => point.value);
    
    // Create highlighted areas for trends
    const annotations = [];
    let index = 0;
    
    // Add increasing trends
    data.trends.increasing.forEach(trend => {
        if (trend.start_date && trend.end_date) {
            annotations.push({
                type: 'box',
                xMin: labels.indexOf(trend.start_date),
                xMax: labels.indexOf(trend.end_date),
                backgroundColor: 'rgba(28, 200, 138, 0.2)',
                borderColor: 'rgba(28, 200, 138, 0.8)',
                borderWidth: 1,
                label: {
                    enabled: true,
                    content: 'เพิ่มขึ้น',
                    position: 'start',
                    backgroundColor: 'rgba(28, 200, 138, 0.8)',
                    font: {
                        size: 12
                    }
                }
            });
        }
        index++;
    });
    
    // Add decreasing trends
    index = 0;
    data.trends.decreasing.forEach(trend => {
        if (trend.start_date && trend.end_date) {
            annotations.push({
                type: 'box',
                xMin: labels.indexOf(trend.start_date),
                xMax: labels.indexOf(trend.end_date),
                backgroundColor: 'rgba(231, 74, 59, 0.2)',
                borderColor: 'rgba(231, 74, 59, 0.8)',
                borderWidth: 1,
                label: {
                    enabled: true,
                    content: 'ลดลง',
                    position: 'start',
                    backgroundColor: 'rgba(231, 74, 59, 0.8)',
                    font: {
                        size: 12
                    }
                }
            });
        }
        index++;
    });
    
    // Create chart data
    const chartData = {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: data.field_label || 'ค่า',
                data: values,
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                borderColor: 'rgba(78, 115, 223, 1)',
                pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                pointRadius: 5,
                pointHoverRadius: 7,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                },
                y: {
                    beginAtZero: false,
                    title: {
                        display: true,
                        text: 'ค่า',
                        font: {
                            family: '"Sarabun", sans-serif',
                            size: 14,
                            weight: 'bold'
                        }
                    }
                }
            },
            plugins: {
                annotation: {
                    annotations: annotations
                },
                tooltip: {
                    callbacks: {
                        title: function(tooltipItem) {
                            return `วันที่: ${tooltipItem[0].label}`;
                        },
                        label: function(context) {
                            return `ค่า: ${context.raw}`;
                        }
                    }
                }
            }
        }
    };
    
    // Create the chart
    createChart(chartId, chartData);
}

/**
 * Create prediction confidence chart
 * 
 * @param {string} chartId Chart canvas ID
 * @param {object} data Prediction data
 */
function createPredictionChart(chartId, data) {
    // Prepare data for Chart.js
    const labels = data.predictions.map(p => p.digit);
    const confidences = data.predictions.map(p => p.confidence);
    
    // Determine colors based on confidence
    const backgroundColors = confidences.map(confidence => {
        if (confidence >= 80) {
            return 'rgba(28, 200, 138, 0.8)';  // High confidence (green)
        } else if (confidence >= 60) {
            return 'rgba(54, 185, 204, 0.8)';  // Medium confidence (blue)
        } else if (confidence >= 40) {
            return 'rgba(246, 194, 62, 0.8)';  // Low confidence (yellow) 
        } else {
            return 'rgba(231, 74, 59, 0.8)';   // Very low confidence (red)
        }
    });
    
    // Create chart data
    const chartData = {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'ความเชื่อมั่น (%)',
                data: confidences,
                backgroundColor: backgroundColors,
                borderColor: 'rgba(0, 0, 0, 0.1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: data.digit_type_label || 'เลข',
                        font: {
                            family: '"Sarabun", sans-serif',
                            size: 14,
                            weight: 'bold'
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'ความเชื่อมั่น (%)',
                        font: {
                            family: '"Sarabun", sans-serif',
                            size: 14,
                            weight: 'bold'
                        }
                    },
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        title: function(tooltipItem) {
                            return `เลข: ${tooltipItem[0].label}`;
                        },
                        label: function(context) {
                            return `ความเชื่อมั่น: ${context.raw.toFixed(2)}%`;
                        }
                    }
                }
            }
        }
    };
    
    // Create the chart
    createChart(chartId, chartData);
}

/**
 * Create accuracy history chart
 * 
 * @param {string} chartId Chart canvas ID
 * @param {object} data Accuracy history data
 */
function createAccuracyChart(chartId, data) {
    // Prepare data for Chart.js
    const labels = data.history.map(item => `${item.period_start} ถึง ${item.period_end}`);
    const accuracies = data.history.map(item => item.accuracy_percentage);
    
    // Create threshold value
    const thresholdData = new Array(labels.length).fill(50);
    
    // Create chart data
    const chartData = {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'ความแม่นยำ (%)',
                    data: accuracies,
                    backgroundColor: 'rgba(28, 200, 138, 0.2)',
                    borderColor: 'rgba(28, 200, 138, 1)',
                    pointBackgroundColor: 'rgba(28, 200, 138, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(28, 200, 138, 1)',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'เกณฑ์ขั้นต่ำ (50%)',
                    data: thresholdData,
                    borderColor: 'rgba(231, 74, 59, 0.5)',
                    borderDash: [5, 5],
                    borderWidth: 2,
                    pointRadius: 0,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'ความแม่นยำ (%)',
                        font: {
                            family: '"Sarabun", sans-serif',
                            size: 14,
                            weight: 'bold'
                        }
                    },
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        title: function(tooltipItem) {
                            return `ช่วงเวลา: ${tooltipItem[0].label}`;
                        },
                        label: function(context) {
                            if (context.datasetIndex === 0) {
                                return `ความแม่นยำ: ${context.raw.toFixed(2)}%`;
                            } else {
                                return `เกณฑ์ขั้นต่ำ: ${context.raw}%`;
                            }
                        }
                    }
                }
            }
        }
    };
    
    // Create the chart
    createChart(chartId, chartData);
}

/**
 * Deep merge two objects
 * 
 * @param {object} target Target object
 * @param {object} source Source object
 * @return {object} Merged object
 */
function deepMerge(target, source) {
    // Create a new object to avoid modifying the originals
    const output = Object.assign({}, target);
    
    // Handle case when source is not an object
    if (isObject(source) && isObject(target)) {
        Object.keys(source).forEach(key => {
            if (isObject(source[key])) {
                if (!(key in target)) {
                    Object.assign(output, { [key]: source[key] });
                } else {
                    output[key] = deepMerge(target[key], source[key]);
                }
            } else {
                Object.assign(output, { [key]: source[key] });
            }
        });
    }
    
    return output;
}

/**
 * Check if value is an object
 * 
 * @param {any} item Item to check
 * @return {boolean} True if object
 */
function isObject(item) {
    return (item && typeof item === 'object' && !Array.isArray(item));
}