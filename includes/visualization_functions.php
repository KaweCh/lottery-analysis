<?php
/**
 * visualization_functions.php
 * 
 * Functions for generating data visualizations for the lottery analysis application
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/statistical_functions.php';

/**
 * Generate HTML for a frequency distribution chart
 * 
 * @param array $distribution Digit distribution data
 * @param string $chartId Chart element ID
 * @param string $title Chart title
 * @param int $maxItems Maximum number of items to display
 * @return string HTML and JavaScript for chart
 */
function generateFrequencyChart($distribution, $chartId, $title, $maxItems = 10) {
    if ($distribution['status'] !== 'success') {
        return '<div class="alert alert-warning">ไม่สามารถสร้างกราฟได้: ' . $distribution['message'] . '</div>';
    }
    
    // Limit number of items
    $items = array_slice($distribution['distribution'], 0, $maxItems);
    
    // Prepare data arrays for chart
    $labels = [];
    $data = [];
    $percentages = [];
    
    foreach ($items as $item) {
        $labels[] = $item['digit'];
        $data[] = $item['count'];
        $percentages[] = $item['percentage'];
    }
    
    // Generate unique ID if not provided
    if (empty($chartId)) {
        $chartId = 'chart-' . md5(uniqid());
    }
    
    $html = '<div class="chart-container">';
    $html .= '<canvas id="' . $chartId . '"></canvas>';
    $html .= '<script>';
    $html .= 'document.addEventListener("DOMContentLoaded", function() {';
    $html .= '  var ctx = document.getElementById("' . $chartId . '").getContext("2d");';
    $html .= '  var myChart = new Chart(ctx, {';
    $html .= '    type: "bar",';
    $html .= '    data: {';
    $html .= '      labels: ' . json_encode($labels) . ',';
    $html .= '      datasets: [{';
    $html .= '        label: "จำนวนครั้งที่ออก",';
    $html .= '        data: ' . json_encode($data) . ',';
    $html .= '        backgroundColor: "' . getConfig('chart_primary_color') . '",';
    $html .= '        borderColor: "' . getConfig('chart_secondary_color') . '",';
    $html .= '        borderWidth: 1';
    $html .= '      }, {';
    $html .= '        label: "เปอร์เซ็นต์",';
    $html .= '        data: ' . json_encode($percentages) . ',';
    $html .= '        type: "line",';
    $html .= '        fill: false,';
    $html .= '        backgroundColor: "' . getConfig('chart_accent_color') . '",';
    $html .= '        borderColor: "' . getConfig('chart_accent_color') . '",';
    $html .= '        yAxisID: "percentage"';
    $html .= '      }]';
    $html .= '    },';
    $html .= '    options: {';
    $html .= '      responsive: true,';
    $html .= '      title: {';
    $html .= '        display: true,';
    $html .= '        text: "' . $title . '"';
    $html .= '      },';
    $html .= '      scales: {';
    $html .= '        yAxes: [{';
    $html .= '          id: "frequency",';
    $html .= '          position: "left",';
    $html .= '          ticks: {';
    $html .= '            beginAtZero: true';
    $html .= '          },';
    $html .= '          scaleLabel: {';
    $html .= '            display: true,';
    $html .= '            labelString: "จำนวนครั้ง"';
    $html .= '          }';
    $html .= '        }, {';
    $html .= '          id: "percentage",';
    $html .= '          position: "right",';
    $html .= '          ticks: {';
    $html .= '            beginAtZero: true,';
    $html .= '            callback: function(value) {';
    $html .= '              return value + "%";';
    $html .= '            }';
    $html .= '          },';
    $html .= '          scaleLabel: {';
    $html .= '            display: true,';
    $html .= '            labelString: "เปอร์เซ็นต์"';
    $html .= '          },';
    $html .= '          gridLines: {';
    $html .= '            display: false';
    $html .= '          }';
    $html .= '        }]';
    $html .= '      }';
    $html .= '    }';
    $html .= '  });';
    $html .= '});';
    $html .= '</script>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate HTML for a heat map of digit positions
 * 
 * @param array $positionFrequency Position frequency data
 * @param string $chartId Chart element ID
 * @param string $title Chart title
 * @return string HTML and JavaScript for heat map
 */
function generatePositionHeatMap($positionFrequency, $chartId, $title) {
    if ($positionFrequency['status'] !== 'success') {
        return '<div class="alert alert-warning">ไม่สามารถสร้างฮีทแมพได้: ' . $positionFrequency['message'] . '</div>';
    }
    
    // Generate unique ID if not provided
    if (empty($chartId)) {
        $chartId = 'heatmap-' . md5(uniqid());
    }
    
    // Prepare position labels based on field
    $positions = $positionFrequency['positions'];
    $positionLabels = [];
    
    for ($i = 0; $i < $positions; $i++) {
        $positionLabels[] = "ตำแหน่งที่ " . ($i + 1);
    }
    
    // Prepare data for heatmap
    $datasets = [];
    $digitLabels = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    
    for ($i = 0; $i < 10; $i++) {
        $data = [];
        for ($j = 0; $j < $positions; $j++) {
            $data[] = $positionFrequency['percentages'][$j][$i];
        }
        
        $datasets[] = [
            'label' => $digitLabels[$i],
            'data' => $data
        ];
    }
    
    $html = '<div class="chart-container">';
    $html .= '<div id="' . $chartId . '" style="height: 400px;"></div>';
    $html .= '<script>';
    $html .= 'document.addEventListener("DOMContentLoaded", function() {';
    $html .= '  var data = {';
    $html .= '    labels: ' . json_encode($positionLabels) . ',';
    $html .= '    datasets: ' . json_encode($datasets);
    $html .= '  };';
    $html .= '  var options = {';
    $html .= '    title: {';
    $html .= '      display: true,';
    $html .= '      text: "' . $title . '"';
    $html .= '    },';
    $html .= '    tooltip: {';
    $html .= '      callbacks: {';
    $html .= '        title: function(tooltipItem, data) {';
    $html .= '          return data.datasets[tooltipItem[0].datasetIndex].label + " - " + data.labels[tooltipItem[0].index];';
    $html .= '        },';
    $html .= '        label: function(tooltipItem, data) {';
    $html .= '          return "ความถี่: " + tooltipItem.value + "%";';
    $html .= '        }';
    $html .= '      }';
    $html .= '    }';
    $html .= '  };';
    $html .= '  var ctx = document.getElementById("' . $chartId . '");';
    $html .= '  var heatmapChart = new Chart(ctx, {';
    $html .= '    type: "heatmap",';
    $html .= '    data: data,';
    $html .= '    options: options';
    $html .= '  });';
    $html .= '});';
    $html .= '</script>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate HTML for a trend line chart
 * 
 * @param array $trendData Trend analysis data
 * @param string $chartId Chart element ID
 * @param string $title Chart title
 * @return string HTML and JavaScript for chart
 */
function generateTrendLineChart($trendData, $chartId, $title) {
    if ($trendData['status'] !== 'success') {
        return '<div class="alert alert-warning">ไม่สามารถสร้างกราฟเทรนด์ได้: ' . $trendData['message'] . '</div>';
    }
    
    // Generate unique ID if not provided
    if (empty($chartId)) {
        $chartId = 'trend-' . md5(uniqid());
    }
    
    // Prepare data for chart
    $labels = [];
    $data = [];
    
    foreach ($trendData['trend_points'] as $point) {
        $labels[] = $point['date'];
        $data[] = $point['value'];
    }
    
    $html = '<div class="chart-container">';
    $html .= '<canvas id="' . $chartId . '"></canvas>';
    $html .= '<script>';
    $html .= 'document.addEventListener("DOMContentLoaded", function() {';
    $html .= '  var ctx = document.getElementById("' . $chartId . '").getContext("2d");';
    $html .= '  var myChart = new Chart(ctx, {';
    $html .= '    type: "line",';
    $html .= '    data: {';
    $html .= '      labels: ' . json_encode($labels) . ',';
    $html .= '      datasets: [{';
    $html .= '        label: "ค่า ' . $trendData['field'] . '",';
    $html .= '        data: ' . json_encode($data) . ',';
    $html .= '        backgroundColor: "rgba(78, 115, 223, 0.05)",';
    $html .= '        borderColor: "' . getConfig('chart_primary_color') . '",';
    $html .= '        pointBackgroundColor: "' . getConfig('chart_secondary_color') . '",';
    $html .= '        pointBorderColor: "#fff",';
    $html .= '        pointRadius: 5,';
    $html .= '        pointHoverRadius: 7,';
    $html .= '        lineTension: 0.3,';
    $html .= '        fill: true';
    $html .= '      }]';
    $html .= '    },';
    $html .= '    options: {';
    $html .= '      responsive: true,';
    $html .= '      title: {';
    $html .= '        display: true,';
    $html .= '        text: "' . $title . '"';
    $html .= '      },';
    $html .= '      scales: {';
    $html .= '        xAxes: [{';
    $html .= '          gridLines: {';
    $html .= '            display: false';
    $html .= '          },';
    $html .= '          ticks: {';
    $html .= '            maxTicksLimit: 10';
    $html .= '          }';
    $html .= '        }],';
    $html .= '        yAxes: [{';
    $html .= '          ticks: {';
    $html .= '            beginAtZero: false';
    $html .= '          },';
    $html .= '          scaleLabel: {';
    $html .= '            display: true,';
    $html .= '            labelString: "ค่า"';
    $html .= '          }';
    $html .= '        }]';
    $html .= '      },';
    $html .= '      tooltips: {';
    $html .= '        callbacks: {';
    $html .= '          title: function(tooltipItem, data) {';
    $html .= '            return "วันที่: " + tooltipItem[0].xLabel;';
    $html .= '          },';
    $html .= '          label: function(tooltipItem, data) {';
    $html .= '            return "ค่า: " + tooltipItem.yLabel;';
    $html .= '          }';
    $html .= '        }';
    $html .= '      }';
    $html .= '    }';
    $html .= '  });';
    $html .= '});';
    $html .= '</script>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate HTML for a pattern detection chart
 * 
 * @param array $patternData Pattern analysis data
 * @param string $chartId Chart element ID
 * @param string $title Chart title
 * @param int $maxPatterns Maximum number of patterns to display
 * @return string HTML and JavaScript for chart
 */
function generatePatternChart($patternData, $chartId, $title, $maxPatterns = 5) {
    if ($patternData['status'] !== 'success' || empty($patternData['patterns'])) {
        return '<div class="alert alert-warning">ไม่พบรูปแบบที่น่าสนใจ หรือข้อมูลไม่เพียงพอ</div>';
    }
    
    // Generate unique ID if not provided
    if (empty($chartId)) {
        $chartId = 'pattern-' . md5(uniqid());
    }
    
    // Limit number of patterns
    $patterns = array_slice($patternData['patterns'], 0, $maxPatterns, true);
    
    // Prepare data for chart
    $labels = [];
    $data = [];
    $patternDescriptions = [];
    
    foreach ($patterns as $patternStr => $pattern) {
        $labels[] = implode('-', $pattern['pattern']);
        $data[] = $pattern['occurrences'];
        $patternDescriptions[] = 'พบเห็นล่าสุด: ' . $patternData['dates'][$pattern['last_seen']];
    }
    
    $html = '<div class="chart-container">';
    $html .= '<canvas id="' . $chartId . '"></canvas>';
    $html .= '<script>';
    $html .= 'document.addEventListener("DOMContentLoaded", function() {';
    $html .= '  var ctx = document.getElementById("' . $chartId . '").getContext("2d");';
    $html .= '  var patternChart = new Chart(ctx, {';
    $html .= '    type: "horizontalBar",';
    $html .= '    data: {';
    $html .= '      labels: ' . json_encode($labels) . ',';
    $html .= '      datasets: [{';
    $html .= '        label: "รูปแบบที่พบบ่อย",';
    $html .= '        data: ' . json_encode($data) . ',';
    $html .= '        backgroundColor: "' . getConfig('chart_secondary_color') . '",';
    $html .= '        borderColor: "' . getConfig('chart_primary_color') . '",';
    $html .= '        borderWidth: 1';
    $html .= '      }]';
    $html .= '    },';
    $html .= '    options: {';
    $html .= '      responsive: true,';
    $html .= '      title: {';
    $html .= '        display: true,';
    $html .= '        text: "' . $title . '"';
    $html .= '      },';
    $html .= '      scales: {';
    $html .= '          xAxes: [{';
    $html .= '            ticks: {';
    $html .= '              beginAtZero: true';
    $html .= '            },';
    $html .= '            scaleLabel: {';
    $html .= '              display: true,';
    $html .= '              labelString: "จำนวนครั้งที่พบ"';
    $html .= '            }';
    $html .= '          }],';
    $html .= '          yAxes: [{';
    $html .= '            gridLines: {';
    $html .= '              display: true';
    $html .= '            }';
    $html .= '          }]';
    $html .= '        },';
    $html .= '        tooltips: {';
    $html .= '          callbacks: {';
    $html .= '            title: function(tooltipItem, data) {';
    $html .= '              return "รูปแบบ: " + data.labels[tooltipItem[0].index];';
    $html .= '            },';
    $html .= '            label: function(tooltipItem, data) {';
    $html .= '              return "จำนวนที่พบ: " + tooltipItem.xLabel;';
    $html .= '            },';
    $html .= '            afterLabel: function(tooltipItem, data) {';
    $html .= '              return ' . json_encode($patternDescriptions) . '[tooltipItem.index];';
    $html .= '            }';
    $html .= '          }';
    $html .= '        }';
    $html .= '      }';
    $html .= '    });';
    $html .= '  });';
    $html .= '</script>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate HTML for a prediction confidence chart
 * 
 * @param array $predictions Prediction data
 * @param string $chartId Chart element ID
 * @param string $title Chart title
 * @return string HTML and JavaScript for chart
 */
function generatePredictionChart($predictions, $chartId, $title) {
    if (empty($predictions) || empty($predictions['predictions'])) {
        return '<div class="alert alert-warning">ไม่มีข้อมูลการทำนายที่จะแสดง</div>';
    }
    
    // Generate unique ID if not provided
    if (empty($chartId)) {
        $chartId = 'prediction-' . md5(uniqid());
    }
    
    // Prepare data for chart
    $labels = [];
    $data = [];
    $backgroundColors = [];
    $confidenceThreshold = getConfig('confidence_threshold');
    $primaryColor = getConfig('chart_primary_color');
    $secondaryColor = getConfig('chart_secondary_color');
    $accentColor = getConfig('chart_accent_color');
    
    foreach ($predictions['predictions'] as $prediction) {
        $labels[] = $prediction['digit'];
        $data[] = $prediction['confidence'];
        
        // Set color based on confidence
        if ($prediction['confidence'] >= $confidenceThreshold) {
            $backgroundColors[] = $accentColor;
        } elseif ($prediction['confidence'] >= ($confidenceThreshold * 0.8)) {
            $backgroundColors[] = $secondaryColor;
        } else {
            $backgroundColors[] = $primaryColor;
        }
    }
    
    $html = '<div class="chart-container">';
    $html .= '<canvas id="' . $chartId . '"></canvas>';
    $html .= '<script>';
    $html .= 'document.addEventListener("DOMContentLoaded", function() {';
    $html .= '  var ctx = document.getElementById("' . $chartId . '").getContext("2d");';
    $html .= '  var predictionChart = new Chart(ctx, {';
    $html .= '    type: "bar",';
    $html .= '    data: {';
    $html .= '      labels: ' . json_encode($labels) . ',';
    $html .= '      datasets: [{';
    $html .= '        label: "ระดับความเชื่อมั่น (%)",';
    $html .= '        data: ' . json_encode($data) . ',';
    $html .= '        backgroundColor: ' . json_encode($backgroundColors) . ',';
    $html .= '        borderColor: "rgba(0, 0, 0, 0.1)",';
    $html .= '        borderWidth: 1';
    $html .= '      }]';
    $html .= '    },';
    $html .= '    options: {';
    $html .= '      responsive: true,';
    $html .= '      title: {';
    $html .= '        display: true,';
    $html .= '        text: "' . $title . '"';
    $html .= '      },';
    $html .= '      scales: {';
    $html .= '        xAxes: [{';
    $html .= '          gridLines: {';
    $html .= '            display: false';
    $html .= '          }';
    $html .= '        }],';
    $html .= '        yAxes: [{';
    $html .= '          ticks: {';
    $html .= '            beginAtZero: true,';
    $html .= '            max: 100,';
    $html .= '            callback: function(value) {';
    $html .= '              return value + "%";';
    $html .= '            }';
    $html .= '          },';
    $html .= '          scaleLabel: {';
    $html .= '            display: true,';
    $html .= '            labelString: "ความเชื่อมั่น (%)"';
    $html .= '          }';
    $html .= '        }]';
    $html .= '      },';
    $html .= '      tooltips: {';
    $html .= '        callbacks: {';
    $html .= '          title: function(tooltipItem, data) {';
    $html .= '            return "เลข: " + data.labels[tooltipItem[0].index];';
    $html .= '          },';
    $html .= '          label: function(tooltipItem, data) {';
    $html .= '            return "ความเชื่อมั่น: " + tooltipItem.yLabel.toFixed(2) + "%";';
    $html .= '          }';
    $html .= '        }';
    $html .= '      }';
    $html .= '    }';
    $html .= '  });';
    $html .= '});';
    $html .= '</script>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate HTML for prediction accuracy history chart
 * 
 * @param array $accuracyData Accuracy history data
 * @param string $chartId Chart element ID
 * @param string $title Chart title
 * @return string HTML and JavaScript for chart
 */
function generateAccuracyChart($accuracyData, $chartId, $title) {
    if (empty($accuracyData) || empty($accuracyData['history'])) {
        return '<div class="alert alert-warning">ไม่มีข้อมูลประวัติความแม่นยำที่จะแสดง</div>';
    }
    
    // Generate unique ID if not provided
    if (empty($chartId)) {
        $chartId = 'accuracy-' . md5(uniqid());
    }
    
    // Prepare data for chart
    $labels = [];
    $data = [];
    
    foreach ($accuracyData['history'] as $period) {
        $labels[] = $period['period_start'] . ' ถึง ' . $period['period_end'];
        $data[] = $period['accuracy_percentage'];
    }
    
    $html = '<div class="chart-container">';
    $html .= '<canvas id="' . $chartId . '"></canvas>';
    $html .= '<script>';
    $html .= 'document.addEventListener("DOMContentLoaded", function() {';
    $html .= '  var ctx = document.getElementById("' . $chartId . '").getContext("2d");';
    $html .= '  var accuracyChart = new Chart(ctx, {';
    $html .= '    type: "line",';
    $html .= '    data: {';
    $html .= '      labels: ' . json_encode($labels) . ',';
    $html .= '      datasets: [{';
    $html .= '        label: "ความแม่นยำการทำนาย (%)",';
    $html .= '        data: ' . json_encode($data) . ',';
    $html .= '        fill: false,';
    $html .= '        backgroundColor: "' . getConfig('chart_primary_color') . '",';
    $html .= '        borderColor: "' . getConfig('chart_primary_color') . '",';
    $html .= '        borderWidth: 3,';
    $html .= '        pointBackgroundColor: "' . getConfig('chart_secondary_color') . '",';
    $html .= '        pointBorderColor: "#fff",';
    $html .= '        pointRadius: 5,';
    $html .= '        pointHoverRadius: 7,';
    $html .= '        lineTension: 0.3';
    $html .= '      }]';
    $html .= '    },';
    $html .= '    options: {';
    $html .= '      responsive: true,';
    $html .= '      title: {';
    $html .= '        display: true,';
    $html .= '        text: "' . $title . '"';
    $html .= '      },';
    $html .= '      scales: {';
    $html .= '        xAxes: [{';
    $html .= '          gridLines: {';
    $html .= '            display: false';
    $html .= '          }';
    $html .= '        }],';
    $html .= '        yAxes: [{';
    $html .= '          ticks: {';
    $html .= '            beginAtZero: true,';
    $html .= '            max: 100,';
    $html .= '            callback: function(value) {';
    $html .= '              return value + "%";';
    $html .= '            }';
    $html .= '          },';
    $html .= '          scaleLabel: {';
    $html .= '            display: true,';
    $html .= '            labelString: "ความแม่นยำ (%)"';
    $html .= '          }';
    $html .= '        }]';
    $html .= '      },';
    $html .= '      tooltips: {';
    $html .= '        callbacks: {';
    $html .= '          title: function(tooltipItem, data) {';
    $html .= '            return data.labels[tooltipItem[0].index];';
    $html .= '          },';
    $html .= '          label: function(tooltipItem, data) {';
    $html .= '            return "ความแม่นยำ: " + tooltipItem.yLabel.toFixed(2) + "%";';
    $html .= '          }';
    $html .= '        }';
    $html .= '      }';
    $html .= '    }';
    $html .= '  });';
    $html .= '});';
    $html .= '</script>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate HTML for a comparison table
 * 
 * @param array $data1 First dataset
 * @param array $data2 Second dataset
 * @param string $label1 Label for first dataset
 * @param string $label2 Label for second dataset
 * @param string $title Table title
 * @return string HTML for comparison table
 */
function generateComparisonTable($data1, $data2, $label1, $label2, $title) {
    $html = '<div class="card mb-4">';
    $html .= '<div class="card-header">';
    $html .= '<h5 class="m-0 font-weight-bold text-primary">' . $title . '</h5>';
    $html .= '</div>';
    $html .= '<div class="card-body">';
    $html .= '<div class="table-responsive">';
    $html .= '<table class="table table-bordered" width="100%" cellspacing="0">';
    $html .= '<thead class="thead-light">';
    $html .= '<tr>';
    $html .= '<th>เลข</th>';
    $html .= '<th>' . $label1 . '</th>';
    $html .= '<th>' . $label2 . '</th>';
    $html .= '<th>ผลต่าง</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    // Merge all keys
    $allKeys = array_unique(array_merge(array_keys($data1), array_keys($data2)));
    
    foreach ($allKeys as $key) {
        $value1 = isset($data1[$key]) ? $data1[$key] : 0;
        $value2 = isset($data2[$key]) ? $data2[$key] : 0;
        $difference = $value2 - $value1;
        
        $diffClass = '';
        if ($difference > 0) {
            $diffClass = 'text-success';
        } elseif ($difference < 0) {
            $diffClass = 'text-danger';
        }
        
        $html .= '<tr>';
        $html .= '<td>' . $key . '</td>';
        $html .= '<td>' . $value1 . '</td>';
        $html .= '<td>' . $value2 . '</td>';
        $html .= '<td class="' . $diffClass . '">' . ($difference > 0 ? '+' : '') . $difference . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate HTML dashboard summary cards
 * 
 * @param array $stats Summary statistics data
 * @return string HTML for summary cards
 */
function generateSummaryCards($stats) {
    $html = '<div class="row">';
    
    // Total Predictions Card
    $html .= '<div class="col-xl-3 col-md-6 mb-4">';
    $html .= '<div class="card border-left-primary shadow h-100 py-2">';
    $html .= '<div class="card-body">';
    $html .= '<div class="row no-gutters align-items-center">';
    $html .= '<div class="col mr-2">';
    $html .= '<div class="text-xs font-weight-bold text-primary text-uppercase mb-1">การทำนายทั้งหมด</div>';
    $html .= '<div class="h5 mb-0 font-weight-bold text-gray-800">' . number_format($stats['total_predictions']) . '</div>';
    $html .= '</div>';
    $html .= '<div class="col-auto"><i class="fas fa-calculator fa-2x text-gray-300"></i></div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Accuracy Card
    $html .= '<div class="col-xl-3 col-md-6 mb-4">';
    $html .= '<div class="card border-left-success shadow h-100 py-2">';
    $html .= '<div class="card-body">';
    $html .= '<div class="row no-gutters align-items-center">';
    $html .= '<div class="col mr-2">';
    $html .= '<div class="text-xs font-weight-bold text-success text-uppercase mb-1">ความแม่นยำเฉลี่ย</div>';
    $html .= '<div class="h5 mb-0 font-weight-bold text-gray-800">' . number_format($stats['average_accuracy'], 2) . '%</div>';
    $html .= '</div>';
    $html .= '<div class="col-auto"><i class="fas fa-chart-line fa-2x text-gray-300"></i></div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Next Draw Card
    $html .= '<div class="col-xl-3 col-md-6 mb-4">';
    $html .= '<div class="card border-left-info shadow h-100 py-2">';
    $html .= '<div class="card-body">';
    $html .= '<div class="row no-gutters align-items-center">';
    $html .= '<div class="col mr-2">';
    $html .= '<div class="text-xs font-weight-bold text-info text-uppercase mb-1">งวดถัดไป</div>';
    $html .= '<div class="h5 mb-0 font-weight-bold text-gray-800">' . formatThaiDisplayDate($stats['next_draw_date']) . '</div>';
    $html .= '</div>';
    $html .= '<div class="col-auto"><i class="fas fa-calendar fa-2x text-gray-300"></i></div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Data Records Card
    $html .= '<div class="col-xl-3 col-md-6 mb-4">';
    $html .= '<div class="card border-left-warning shadow h-100 py-2">';
    $html .= '<div class="card-body">';
    $html .= '<div class="row no-gutters align-items-center">';
    $html .= '<div class="col mr-2">';
    $html .= '<div class="text-xs font-weight-bold text-warning text-uppercase mb-1">ข้อมูลทั้งหมด</div>';
    $html .= '<div class="h5 mb-0 font-weight-bold text-gray-800">' . number_format($stats['total_data_records']) . '</div>';
    $html .= '</div>';
    $html .= '<div class="col-auto"><i class="fas fa-database fa-2x text-gray-300"></i></div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    return $html;
}
?>