<?php
session_start();
include 'db.php'; // your database connection file

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Initialize analytics data variables to avoid undefined variable errors
$monthly_trends = null;
$daily_patterns = null;
$room_preferences = null;
$demand_forecast_data = []; // Initialize as array for easier handling
$analytics_error = null;

try {
    // Fetch bookings for the logged-in user (same as before) - though not used in analytics display part of this code.
    $user_bookings_sql = "SELECT * FROM bookingstatus WHERE user_id = ? ORDER BY created_at DESC";
    $user_bookings_stmt = $conn->prepare($user_bookings_sql);
    if ($user_bookings_stmt) {
        $user_bookings_stmt->bind_param("i", $user_id);
        $user_bookings_stmt->execute();
        $user_bookings_result = $user_bookings_stmt->get_result();
    } else {
        throw new Exception("Failed to prepare user bookings statement: " . $conn->error);
    }

    // Analytics queries
    // Monthly trends with prediction (using a 3-month rolling average for prediction)
    $monthly_trends_sql = "
        SELECT
            DATE_FORMAT(checkinDate, '%Y-%m') AS month,
            COUNT(*) AS bookings,
            SUM(totalPrice) AS sales,
            -- Calculate predicted bookings using a 3-month rolling average
            AVG(COUNT(*)) OVER (ORDER BY DATE_FORMAT(checkinDate, '%Y-%m') ROWS BETWEEN 3 PRECEDING AND CURRENT ROW) AS predicted_bookings,
            -- Calculate predicted sales using a 3-month rolling average
            AVG(SUM(totalPrice)) OVER (ORDER BY DATE_FORMAT(checkinDate, '%Y-%m') ROWS BETWEEN 3 PRECEDING AND CURRENT ROW) AS predicted_sales
        FROM bookingstatus
        WHERE checkinDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) -- Look at last 12 months for trends
        GROUP BY DATE_FORMAT(checkinDate, '%Y-%m')
        ORDER BY month
    ";
    $monthly_trends_result = $conn->query($monthly_trends_sql);
    if (!$monthly_trends_result) {
        throw new Exception("Error fetching monthly trends: " . $conn->error);
    }
    $monthly_data_for_insights = $monthly_trends_result->fetch_all(MYSQLI_ASSOC);

    // Daily patterns (order by DAYOFWEEK to ensure correct chart order, assuming Sunday=1, Monday=2...Saturday=7)
    $daily_patterns_sql = "
        SELECT
            DAYNAME(checkinDate) AS day_of_week,
            DAYOFWEEK(checkinDate) AS day_index, -- Add day_index for explicit ordering
            COUNT(*) AS bookings,
            SUM(totalPrice) AS sales
        FROM bookingstatus
        WHERE checkinDate >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH) -- Consider recent 3 months for daily patterns
        GROUP BY day_of_week, day_index
        ORDER BY day_index
    ";
    $daily_patterns_result = $conn->query($daily_patterns_sql);
    if (!$daily_patterns_result) {
        throw new Exception("Error fetching daily patterns: " . $conn->error);
    }
    $weekly_data_for_insights = $daily_patterns_result->fetch_all(MYSQLI_ASSOC);

    // Room preferences - MODIFIED TO INCLUDE ALL ROOMS FROM 'rooms' TABLE
    $room_preferences_sql = "
        SELECT
            r.name AS roomName,
            COUNT(bs.roomName) AS bookings,
            IFNULL(SUM(bs.totalPrice), 0) AS sales
        FROM rooms r
        LEFT JOIN bookingstatus bs ON r.name = bs.roomName
        GROUP BY r.name
        ORDER BY bookings DESC;
    ";
    $room_preferences_result = $conn->query($room_preferences_sql);
    if (!$room_preferences_result) {
        throw new Exception("Error fetching room preferences: " . $conn->error);
    }
    $room_data_for_table = $room_preferences_result->fetch_all(MYSQLI_ASSOC);

    // Demand forecast (next 3 months based on the latest available prediction from monthly trends)
    if (!empty($monthly_data_for_insights)) {
        $last_predicted_data = end($monthly_data_for_insights); // Get the prediction for the last month with data

        $forecast_bookings_base = $last_predicted_data['predicted_bookings'];
        $forecast_sales_base = $last_predicted_data['predicted_sales'];

        for ($i = 1; $i <= 3; $i++) {
            $future_month = date('Y-m', strtotime("+$i month"));
            $demand_forecast_data[] = [
                'future_month' => $future_month,
                'predicted_bookings' => $forecast_bookings_base, // Projecting the last predicted value
                'predicted_sales' => $forecast_sales_base // Projecting the last predicted value
            ];
        }
    }

} catch (Exception $e) {
    error_log("Analytics error: " . $e->getMessage());
    $analytics_error = "An error occurred while generating analytics: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Analytics Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
            color: #333;
        }
        h2 {
            color: #2a6496;
            text-align: center;
            margin-bottom: 30px;
        }
        .dashboard {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 30px;
        }

        .analytics-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .analytics-card h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .chart-container {
            height: 300px;
            position: relative;
        }

        .insights {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            font-size: 0.9em;
        }

        .insight-item {
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .data-table th, .data-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .data-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .highlight {
            font-weight: bold;
            color: #2a6496;
        }

        .positive-trend {
            color: #4CAF50;
        }

        .negative-trend {
            color: #F44336;
        }
        .error-message {
            color: red;
            background-color: #ffe0e0;
            border: 1px solid red;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@1.0.2"></script>
</head>
<body>

<?php if ($analytics_error): ?>
    <div class="error-message">
        <?php echo $analytics_error; ?>
    </div>
<?php endif; ?>

<h2>Analytics Dashboard</h2>

<div class="dashboard">
    <div class="analytics-card">
        <h3>Monthly Booking Trends</h3>
        <div class="chart-container">
            <canvas id="monthlyTrendsChart"></canvas>
        </div>
        <div class="insights">
            <?php
            if (count($monthly_data_for_insights) >= 2) { // Need at least 2 months for comparison
                $last_month = end($monthly_data_for_insights);
                // To get the previous month, we need to create a copy, pop the last, then get the new last.
                // This ensures we're comparing the *actual* previous month's data, not a predicted value.
                $prev_month_temp = $monthly_data_for_insights; // Create a copy
                array_pop($prev_month_temp); // Remove the last element
                $prev_month = end($prev_month_temp); // Get the new last element (which was second to last)

                $booking_change = $last_month['bookings'] - $prev_month['bookings'];
                $sales_change = $last_month['sales'] - $prev_month['sales'];

                $booking_trend_class = ($booking_change > 0) ? 'positive-trend' : (($booking_change < 0) ? 'negative-trend' : '');
                $sales_trend_class = ($sales_change > 0) ? 'positive-trend' : (($sales_change < 0) ? 'negative-trend' : '');
            ?>
                <div class="insight-item">
                    Last month (<?php echo $last_month['month']; ?>): <span class="highlight"><?php echo $last_month['bookings']; ?> bookings</span>
                    (<?php if ($booking_change != 0) { echo "<span class='{$booking_trend_class}'>" . abs($booking_change) . " " . (($booking_change >= 0) ? 'up' : 'down') . "</span>"; } else { echo "no change"; } ?> from <?php echo $prev_month['month']; ?>)
                </div>
                <div class="insight-item">
                    Sales: <span class="highlight">₱<?php echo number_format($last_month['sales'], 2); ?></span>
                    (<?php if ($sales_change != 0) { echo "<span class='{$sales_trend_class}'>" . abs($sales_change) . " " . (($sales_change >= 0) ? 'up' : 'down') . "</span>"; } else { echo "no change"; } ?>)
                </div>
                <div class="insight-item">
                    Latest prediction for trends: ~<span class="highlight"><?php echo round($last_month['predicted_bookings']); ?> bookings</span>,
                    ~<span class="highlight">₱<?php echo number_format($last_month['predicted_sales'], 2); ?></span>
                </div>
            <?php
            } else {
                echo "<div class='insight-item'>Not enough data to show monthly trends.</div>";
            }
            ?>
        </div>
    </div>

    <div class="analytics-card">
        <h3>Room Preferences</h3>
        <div class="chart-container">
            <canvas id="roomPreferencesChart"></canvas>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Room</th>
                    <th>Bookings</th>
                    <th>Sales</th>
                    <th>% of Total Bookings</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_bookings_all_rooms = array_sum(array_column($room_data_for_table, 'bookings'));

                if (!empty($room_data_for_table)) {
                    foreach($room_data_for_table as $room) {
                        $percentage = ($total_bookings_all_rooms > 0) ? round($room['bookings'] * 100.0 / $total_bookings_all_rooms, 1) : 0;
                        echo "<tr>
                            <td>{$room['roomName']}</td>
                            <td>{$room['bookings']}</td>
                            <td>₱" . number_format($room['sales'], 2) . "</td>
                            <td>{$percentage}%</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No room preference data available.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="analytics-card">
        <h3>Weekly Booking Patterns</h3>
        <div class="chart-container">
            <canvas id="weeklyPatternsChart"></canvas>
        </div>
        <div class="insights">
            <?php
            if (!empty($weekly_data_for_insights)) {
                $peak_day = ['day_of_week' => 'N/A', 'bookings' => -1];
                $low_day = ['day_of_week' => 'N/A', 'bookings' => PHP_INT_MAX];

                foreach ($weekly_data_for_insights as $day) {
                    if ($day['bookings'] > $peak_day['bookings']) $peak_day = $day;
                    if ($day['bookings'] < $low_day['bookings']) $low_day = $day;
                }
            ?>
            <div class="insight-item">
                Peak day: <span class="highlight"><?php echo $peak_day['day_of_week']; ?></span>
                (avg <?php echo $peak_day['bookings']; ?> bookings)
            </div>
            <div class="insight-item">
                Lowest demand: <span class="highlight"><?php echo $low_day['day_of_week']; ?></span>
                (avg <?php echo $low_day['bookings']; ?> bookings)
            </div>
            <?php
            } else {
                echo "<div class='insight-item'>No daily booking data available.</div>";
            }
            ?>
        </div>
    </div>

    <div class="analytics-card">
        <h3>Demand Forecast Insights (Next 3 Months)</h3>
        <div class="chart-container">
            <canvas id="demandForecastChart"></canvas>
        </div>
        <div class="insights">
            <?php
            if (!empty($demand_forecast_data)) {
                // Displaying the first month's forecast as a key insight
                $next_month = $demand_forecast_data[0];
            ?>
            <div class="insight-item">
                Next month (<?php echo $next_month['future_month']; ?>) forecast: ~<span class="highlight"><?php echo round($next_month['predicted_bookings']); ?> bookings</span>,
                ~<span class="highlight">₱<?php echo number_format($next_month['predicted_sales'], 2); ?></span>
            </div>
            <div class="insight-item">
                <strong>Recommendations:</strong>
                <ul>
                    <li>Adjust pricing dynamically based on demand forecasts to maximize revenue during peak periods and stimulate demand during low periods.</li>
                    <li>Offer promotions on low-demand days or for less popular rooms to boost bookings.</li>
                    <li>Plan staffing and resource allocation (e.g., cleaning, maintenance) according to weekly patterns and future demand to optimize operations.</li>
                    <li>Consider targeted marketing campaigns for upcoming low-demand periods.</li>
                </ul>
            </div>
            <?php
            } else {
                echo "<div class='insight-item'>Not enough historical data to generate a demand forecast.</div>";
            }
            ?>
        </div>
    </div>
</div>

<script>
// Monthly Trends Chart
const monthlyCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
const monthlyTrendsData = <?php echo json_encode($monthly_data_for_insights); ?>;

new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: monthlyTrendsData.map(row => row.month),
        datasets: [
            {
                label: 'Actual Bookings',
                data: monthlyTrendsData.map(row => row.bookings),
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1,
                fill: true
            },
            {
                label: 'Predicted Bookings (3-Month Avg)',
                data: monthlyTrendsData.map(row => row.predicted_bookings),
                borderColor: 'rgba(153, 102, 255, 1)',
                backgroundColor: 'rgba(153, 102, 255, 0.2)',
                borderDash: [5, 5],
                tension: 0.1,
                fill: false
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Monthly Bookings with Prediction'
            },
            tooltip: {
                mode: 'index', // Show tooltips for all datasets at the hovered index
                intersect: false, // Tooltip shows even if cursor is not exactly on the point
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        // This callback ensures the correct formatting for bookings
                        label += parseFloat(context.raw).toFixed(0) + ' bookings';
                        return label;
                    },
                    // If you also want to show sales in the tooltip for the actual bookings line,
                    // you'd need to add a sales dataset to the chart or fetch sales for the tooltip.
                    // For now, this focuses on 'bookings'.
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Bookings'
                }
            }
        }
    }
});

// Room Preferences Chart
const roomCtx = document.getElementById('roomPreferencesChart').getContext('2d');
const roomPreferencesData = <?php echo json_encode($room_data_for_table); ?>;

new Chart(roomCtx, {
    type: 'pie',
    data: {
        labels: roomPreferencesData.map(row => row.roomName),
        datasets: [{
            data: roomPreferencesData.map(row => row.bookings),
            backgroundColor: [
                'rgba(255, 99, 132, 0.7)',
                'rgba(54, 162, 235, 0.7)',
                'rgba(255, 206, 86, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(153, 102, 255, 0.7)',
                'rgba(255, 159, 64, 0.7)',
                'rgba(199, 199, 199, 0.7)',
                'rgba(83, 102, 255, 0.7)' // Added more colors for more rooms
            ],
            borderColor: '#fff',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Room/Cottage Popularity'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.raw || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                        return `${label}: ${value} bookings (${percentage}%)`;
                    }
                }
            }
        }
    }
});

// Weekly Patterns Chart
const weeklyCtx = document.getElementById('weeklyPatternsChart').getContext('2d');
const weeklyPatternsData = <?php echo json_encode($weekly_data_for_insights); ?>;

// Create an array for all days of the week, initialized with 0 bookings/sales
const daysOfWeekOrder = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
const orderedWeeklyData = daysOfWeekOrder.map(dayName => {
    const foundDay = weeklyPatternsData.find(d => d.day_of_week === dayName);
    return foundDay || { day_of_week: dayName, bookings: 0, sales: 0 };
});

new Chart(weeklyCtx, {
    type: 'bar',
    data: {
        labels: orderedWeeklyData.map(d => d.day_of_week),
        datasets: [
            {
                label: 'Bookings',
                data: orderedWeeklyData.map(d => d.bookings),
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            },
            {
                label: 'Sales (₱)',
                data: orderedWeeklyData.map(d => d.sales),
                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1,
                type: 'line', // Mixed chart type
                yAxisID: 'y1',
                fill: false,
                tension: 0.1
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Weekly Booking Patterns'
            },
            tooltip: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.dataset.label === 'Sales (₱)') {
                            label += '₱' + parseFloat(context.raw).toFixed(2);
                        } else {
                            label += parseFloat(context.raw).toFixed(0) + ' bookings';
                        }
                        return label;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Bookings'
                }
            },
            y1: {
                position: 'right',
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Sales (₱)'
                },
                grid: {
                    drawOnChartArea: false
                }
            }
        }
    }
});

// Demand Forecast Chart
const forecastCtx = document.getElementById('demandForecastChart').getContext('2d');
const demandForecastChartData = <?php echo json_encode($demand_forecast_data); ?>;

new Chart(forecastCtx, {
    type: 'bar',
    data: {
        labels: demandForecastChartData.map(row => row.future_month),
        datasets: [
            {
                label: 'Predicted Bookings',
                data: demandForecastChartData.map(row => row.predicted_bookings),
                backgroundColor: 'rgba(255, 159, 64, 0.6)',
                borderColor: 'rgba(255, 159, 64, 1)',
                borderWidth: 1
            },
            {
                label: 'Predicted Sales (₱)',
                data: demandForecastChartData.map(row => row.predicted_sales),
                backgroundColor: 'rgba(153, 102, 255, 0.6)',
                borderColor: 'rgba(153, 102, 255, 1)',
                borderWidth: 1,
                type: 'line', // Mixed chart type
                yAxisID: 'y1',
                fill: false,
                tension: 0.1
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: '3-Month Demand Forecast'
            },
            tooltip: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.dataset.label === 'Predicted Sales (₱)') {
                            label += '₱' + parseFloat(context.raw).toFixed(2);
                        } else {
                            label += parseFloat(context.raw).toFixed(0) + ' bookings';
                        }
                        return label;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Bookings'
                }
            },
            y1: {
                position: 'right',
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Sales (₱)'
                },
                grid: {
                    drawOnChartArea: false
                }
            }
        }
    }
});
</script>
</body>
</html>

<?php
// Close statements and connection
if (isset($user_bookings_stmt)) $user_bookings_stmt->close();
// mysqli_result objects for query() calls don't need explicit close, they are freed when PHP script ends
if (isset($monthly_trends_result)) $monthly_trends_result->free();
if (isset($daily_patterns_result)) $daily_patterns_result->free();
if (isset($room_preferences_result)) $room_preferences_result->free();
if (isset($conn)) $conn->close();
?>