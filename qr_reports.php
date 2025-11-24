<?php
require_once 'auth_guard.php';
include 'connect_db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo csrfToken(); ?>">
    <title>QR Scan Reports - GABAY Admin</title>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="assets/css/system-fonts.css">
    <link rel="stylesheet" href="filter-styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>window.CSRF_TOKEN = '<?php echo csrfToken(); ?>';</script>
    <script src="auth_helper.js"></script>
    <style>
        .report-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .report-controls {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .controls-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .control-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        
        .control-group select,
        .control-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #2e7d32;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1b5e20;
        }
        
        .btn-secondary {
            background: #1976d2;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #1565c0;
        }
        
        .report-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .report-section h3 {
            margin-top: 0;
            color: #2e7d32;
            border-bottom: 2px solid #e8f5e8;
            padding-bottom: 10px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .summary-card {
            text-align: center;
            padding: 20px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .summary-card h4 {
            font-size: 32px;
            margin: 10px 0;
            color: #2e7d32;
        }
        
        .summary-card p {
            margin: 0;
            color: #666;
            font-weight: 600;
        }
        
        .chart-container {
            position: relative;
            height: 400px;
            margin-top: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        th {
            background: #f8fafc;
            font-weight: 600;
            color: #2e7d32;
        }
        
        tr:hover {
            background: #f8fafc;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .loading::after {
            content: '...';
            animation: dots 1.5s steps(4, end) infinite;
        }
        
        @keyframes dots {
            0%, 20% { content: '.'; }
            40% { content: '..'; }
            60%, 100% { content: '...'; }
        }

        @media print {
            .sidebar, .report-controls, .button-group {
                display: none !important;
            }
            .report-section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>GABAY Admin</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="home.php">Dashboard</a>
                <a href="officeManagement.php">Office Management</a>
                <a href="floorPlan.php">Floor Plans</a>
                <a href="qr_reports.php" class="active">QR Reports</a>
                <a href="visitorFeedback.php">Visitor Feedback</a>
                <a href="systemSettings.php">System Settings</a>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php">Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <header class="header">
                <h1>QR Scan Reports</h1>
            </header>

            <div class="report-container">
                <!-- Report Controls -->
                <div class="report-controls">
                    <h3>Report Configuration</h3>
                    <div class="controls-grid">
                        <div class="control-group">
                            <label for="report-type">Report Type</label>
                            <select id="report-type">
                                <option value="all">All QR Scans</option>
                                <option value="office">Office QR Only</option>
                                <option value="panorama">Panorama QR Only</option>
                            </select>
                        </div>
                        <div class="control-group">
                            <label for="date-from">From Date</label>
                            <input type="date" id="date-from">
                        </div>
                        <div class="control-group">
                            <label for="date-to">To Date</label>
                            <input type="date" id="date-to" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="control-group">
                            <label for="group-by">Group By</label>
                            <select id="group-by">
                                <option value="day">Daily</option>
                                <option value="week">Weekly</option>
                                <option value="month">Monthly</option>
                            </select>
                        </div>
                    </div>
                    <div class="button-group">
                        <button class="btn btn-primary" onclick="generateReport()">Generate Report</button>
                        <button class="btn btn-secondary" onclick="exportCSV()">Export CSV</button>
                        <button class="btn btn-secondary" onclick="printReport()">Print Report</button>
                    </div>
                </div>

                <!-- Summary Section -->
                <div class="report-section" id="summary-section" style="display: none;">
                    <h3>Summary Statistics</h3>
                    <div class="summary-grid" id="summary-grid"></div>
                </div>

                <!-- Timeline Chart -->
                <div class="report-section" id="timeline-section" style="display: none;">
                    <h3>Scan Timeline</h3>
                    <div class="chart-container">
                        <canvas id="timeline-chart"></canvas>
                    </div>
                </div>

                <!-- Hourly Distribution -->
                <div class="report-section" id="hourly-section" style="display: none;">
                    <h3>Hourly Distribution</h3>
                    <div class="chart-container">
                        <canvas id="hourly-chart"></canvas>
                    </div>
                </div>

                <!-- Daily Comparison -->
                <div class="report-section" id="daily-section" style="display: none;">
                    <h3>Day of Week Comparison</h3>
                    <div class="chart-container">
                        <canvas id="daily-chart"></canvas>
                    </div>
                </div>

                <!-- Top Offices -->
                <div class="report-section" id="offices-section" style="display: none;">
                    <h3>Most Visited Offices</h3>
                    <table id="offices-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Office Name</th>
                                <th>Title</th>
                                <th>Scan Count</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <!-- Top Panoramas -->
                <div class="report-section" id="panoramas-section" style="display: none;">
                    <h3>Most Viewed Panorama Locations</h3>
                    <table id="panoramas-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Location</th>
                                <th>Floor</th>
                                <th>View Count</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        let currentReportData = null;
        let timelineChart = null;
        let hourlyChart = null;
        let dailyChart = null;

        async function generateReport() {
            const reportType = document.getElementById('report-type').value;
            const dateFrom = document.getElementById('date-from').value;
            const dateTo = document.getElementById('date-to').value;
            const groupBy = document.getElementById('group-by').value;

            // Show loading state
            showLoading();

            try {
                const params = new URLSearchParams({
                    action: 'generate_report',
                    report_type: reportType,
                    group_by: groupBy
                });

                if (dateFrom) params.append('date_from', dateFrom);
                if (dateTo) params.append('date_to', dateTo);

                const response = await fetch(`qr_report_api.php?${params}`);
                const result = await response.json();

                if (result.success) {
                    currentReportData = result.data;
                    renderReport(result.data);
                } else {
                    alert('Error generating report: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to generate report');
            }

            hideLoading();
        }

        function renderReport(data) {
            renderSummary(data.summary);
            renderTimeline(data.timeline);
            renderHourlyDistribution(data.hourly_distribution);
            renderDailyComparison(data.daily_comparison);
            renderTopOffices(data.top_offices);
            renderTopPanoramas(data.top_panoramas);

            // Show all sections
            document.querySelectorAll('.report-section').forEach(section => {
                section.style.display = 'block';
            });
        }

        function renderSummary(summary) {
            const grid = document.getElementById('summary-grid');
            grid.innerHTML = `
                <div class="summary-card">
                    <p>Office QR Scans</p>
                    <h4>${summary.office.toLocaleString()}</h4>
                </div>
                <div class="summary-card">
                    <p>Panorama QR Scans</p>
                    <h4>${summary.panorama.toLocaleString()}</h4>
                </div>
                <div class="summary-card">
                    <p>Total Scans</p>
                    <h4>${summary.total.toLocaleString()}</h4>
                </div>
            `;
        }

        function renderTimeline(timeline) {
            const ctx = document.getElementById('timeline-chart').getContext('2d');
            
            if (timelineChart) timelineChart.destroy();

            timelineChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: timeline.map(d => d.period),
                    datasets: [
                        {
                            label: 'Office QR',
                            data: timeline.map(d => d.office),
                            borderColor: '#2e7d32',
                            backgroundColor: 'rgba(46, 125, 50, 0.1)',
                            tension: 0.4
                        },
                        {
                            label: 'Panorama QR',
                            data: timeline.map(d => d.panorama),
                            borderColor: '#1976d2',
                            backgroundColor: 'rgba(25, 118, 210, 0.1)',
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        function renderHourlyDistribution(hourly) {
            const ctx = document.getElementById('hourly-chart').getContext('2d');
            
            if (hourlyChart) hourlyChart.destroy();

            hourlyChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: hourly.map(d => d.hour),
                    datasets: [
                        {
                            label: 'Office QR',
                            data: hourly.map(d => d.office),
                            backgroundColor: '#4CAF50'
                        },
                        {
                            label: 'Panorama QR',
                            data: hourly.map(d => d.panorama),
                            backgroundColor: '#1976D2'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: { beginAtZero: true },
                        x: { stacked: false }
                    }
                }
            });
        }

        function renderDailyComparison(daily) {
            const ctx = document.getElementById('daily-chart').getContext('2d');
            
            if (dailyChart) dailyChart.destroy();

            dailyChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: daily.map(d => d.day),
                    datasets: [
                        {
                            label: 'Office QR',
                            data: daily.map(d => d.office),
                            backgroundColor: '#4CAF50'
                        },
                        {
                            label: 'Panorama QR',
                            data: daily.map(d => d.panorama),
                            backgroundColor: '#1976D2'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        function renderTopOffices(offices) {
            const tbody = document.querySelector('#offices-table tbody');
            tbody.innerHTML = offices.map((office, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>${office.name}</td>
                    <td>${office.title}</td>
                    <td><strong>${office.scan_count}</strong></td>
                </tr>
            `).join('');
        }

        function renderTopPanoramas(panoramas) {
            const tbody = document.querySelector('#panoramas-table tbody');
            tbody.innerHTML = panoramas.map((panorama, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>${panorama.location_name}</td>
                    <td>Floor ${panorama.floor_number}</td>
                    <td><strong>${panorama.view_count}</strong></td>
                </tr>
            `).join('');
        }

        async function exportCSV() {
            const reportType = document.getElementById('report-type').value;
            const dateFrom = document.getElementById('date-from').value;
            const dateTo = document.getElementById('date-to').value;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'qr_report_api.php?action=export_csv';

            const fields = {
                csrf_token: window.CSRF_TOKEN,
                report_type: reportType,
                date_from: dateFrom,
                date_to: dateTo
            };

            for (const [key, value] of Object.entries(fields)) {
                if (value) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    form.appendChild(input);
                }
            }

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

        function printReport() {
            window.print();
        }

        function showLoading() {
            document.querySelectorAll('.report-section').forEach(section => {
                section.innerHTML = '<div class="loading">Loading report data</div>';
                section.style.display = 'block';
            });
        }

        function hideLoading() {
            // Loading will be replaced by actual content
        }

        // Set default date range (last 30 days) and auto-load from URL params
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            
            // Check for URL parameters
            const reportType = urlParams.get('report_type');
            const dateFrom = urlParams.get('date_from');
            const dateTo = urlParams.get('date_to');
            
            // Set report type if provided
            if (reportType) {
                document.getElementById('report-type').value = reportType;
            }
            
            // Set dates from URL or use defaults
            if (dateFrom && dateTo) {
                document.getElementById('date-from').value = dateFrom;
                document.getElementById('date-to').value = dateTo;
            } else {
                // Default to last 30 days
                const defaultDateTo = new Date();
                const defaultDateFrom = new Date();
                defaultDateFrom.setDate(defaultDateFrom.getDate() - 30);
                
                document.getElementById('date-to').value = defaultDateTo.toISOString().split('T')[0];
                document.getElementById('date-from').value = defaultDateFrom.toISOString().split('T')[0];
            }
            
            // Auto-generate report if URL params are present
            if (reportType || (dateFrom && dateTo)) {
                generateReport();
            }
        });
    </script>
</body>
</html>
