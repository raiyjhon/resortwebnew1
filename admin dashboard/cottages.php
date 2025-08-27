<?php
session_start();
include 'db.php'; // your database connection file

// --- Date Navigation Logic ---
$period = $_GET['period'] ?? 'day';
$date = $_GET['date'] ?? date('Y-m-d');
$compare_with = $_GET['compare_with'] ?? 'none';

try {
    $current_date = new DateTime($date);

    // Handle previous/next/today navigation
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
        if ($action === 'prev') {
            if ($period === 'day') {
                $current_date->modify('-1 day');
            } elseif ($period === 'week') {
                $current_date->modify('-1 week');
            } elseif ($period === 'month') {
                $current_date->modify('-1 month');
            }
        } elseif ($action === 'next') {
            if ($period === 'day') {
                $current_date->modify('+1 day');
            } elseif ($period === 'week') {
                $current_date->modify('+1 week');
            } elseif ($period === 'month') {
                $current_date->modify('+1 month');
            }
        } elseif ($action === 'today') {
            $current_date = new DateTime(); // Reset to today
        }
        // Redirect to new URL with updated date to ensure clean state
        header("Location: ?period={$period}&date={$current_date->format('Y-m-d')}&compare_with={$compare_with}");
        exit();
    }

    $current_date_str = $current_date->format('Y-m-d');
    
    // Determine the comparison date based on the period and comparison type
    $compare_date_str = null;
    if ($compare_with !== 'none') {
        $compare_date = clone $current_date;
        if ($period === 'day') {
            if ($compare_with === 'previous_day') {
                $compare_date->modify('-1 day');
            } elseif ($compare_with === 'previous_week') {
                $compare_date->modify('-1 week');
            } elseif ($compare_with === 'previous_month') {
                $compare_date->modify('-1 month');
            } elseif ($compare_with === 'previous_year') {
                $compare_date->modify('-1 year');
            }
        } elseif ($period === 'week') {
            if ($compare_with === 'previous_week') {
                $compare_date->modify('-1 week');
            } elseif ($compare_with === 'previous_month') {
                $compare_date->modify('-1 month');
            } elseif ($compare_with === 'previous_year') {
                $compare_date->modify('-1 year');
            }
        } elseif ($period === 'month') {
            if ($compare_with === 'previous_month') {
                $compare_date->modify('-1 month');
            } elseif ($compare_with === 'previous_year') {
                $compare_date->modify('-1 year');
            }
        }
        $compare_date_str = $compare_date->format('Y-m-d');
    }
} catch (Exception $e) {
    // Fallback in case of invalid date input
    $current_date = new DateTime();
    $current_date_str = $current_date->format('Y-m-d');
    $compare_date_str = null;
    error_log("Date parsing error: " . $e->getMessage());
}

// Function to fetch reservation data
function getReservationData($conn, $period, $date_str) {
    $data = [];
    
    try {
        $dt = new DateTime($date_str); // Create DateTime object from string
        
        if ($period === 'day') {
            // For daily view - reservations by hour
            $sql = "SELECT 
                        HOUR(checkinTime) as hour,
                        COUNT(*) as count,
                        SUM(totalPrice) as sales
                    FROM bookingstatus 
                    WHERE checkinDate = ? 
                    AND status != 'Cancelled'
                    GROUP BY HOUR(checkinTime)
                    ORDER BY hour";
            
            $stmt = $conn->prepare($sql);
            $formattedDate = $dt->format('Y-m-d'); // Store in variable
            $stmt->bind_param("s", $formattedDate); // Pass variable
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Initialize all hours with 0
            for ($i = 0; $i < 24; $i++) {
                $data[$i] = [
                    'hour' => $i,
                    'count' => 0,
                    'sales' => 0
                ];
            }
            
            // Fill in actual data
            while ($row = $result->fetch_assoc()) {
                $hour = (int)$row['hour'];
                $data[$hour] = [
                    'hour' => $hour,
                    'count' => (int)$row['count'],
                    'sales' => (float)$row['sales']
                ];
            }
            
            $stmt->close();
            
            // Get total for the day
            $total_sql = "SELECT 
                                COUNT(*) as total_count,
                                SUM(totalPrice) as total_sales,
                                AVG(stayDuration) as avg_duration
                            FROM bookingstatus 
                            WHERE checkinDate = ? 
                            AND status != 'Cancelled'";
                            
            $total_stmt = $conn->prepare($total_sql);
            $total_stmt->bind_param("s", $formattedDate); // Pass variable
            $total_stmt->execute();
            $total_result = $total_stmt->get_result();
            $totals = $total_result->fetch_assoc();
            
            $data['totals'] = [
                'count' => $totals['total_count'] ?? 0,
                'sales' => $totals['total_sales'] ?? 0,
                'avg_duration' => $totals['avg_duration'] ?? 0
            ];
            
            $total_stmt->close();
            
        } elseif ($period === 'week') {
            // For weekly view - reservations by day
            $start = clone $dt;
            $start->modify('Monday this week'); // Start of the week (Monday)
            $end = clone $start;
            $end->modify('Sunday this week');   // End of the week (Sunday)
            
            $sql = "SELECT 
                        DAYOFWEEK(checkinDate) as day_of_week, -- 1=Sunday, 2=Monday, ..., 7=Saturday
                        DATE(checkinDate) as date,
                        COUNT(*) as count,
                        SUM(totalPrice) as sales
                    FROM bookingstatus 
                    WHERE checkinDate BETWEEN ? AND ?
                    AND status != 'Cancelled'
                    GROUP BY DATE(checkinDate), DAYOFWEEK(checkinDate)
                    ORDER BY date";
            
            $stmt = $conn->prepare($sql);
            $formattedStartDate = $start->format('Y-m-d'); // Store in variable
            $formattedEndDate = $end->format('Y-m-d');     // Store in variable
            $stmt->bind_param("ss", $formattedStartDate, $formattedEndDate); // Pass variables
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Initialize all days with 0 (1=Sunday, 2=Monday...7=Saturday)
            $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            for ($i = 1; $i <= 7; $i++) {
                $day_date = clone $start;
                $day_date->modify('+' . ($i-1) . ' days'); // Adjust for Monday start (i=1 is Sunday, so +0 days from Monday for Monday)
                
                // Correct day mapping for initialisation (MySQL's DAYOFWEEK starts Sunday=1)
                $actual_day_index = ($start->format('w') + $i - 1) % 7; // PHP's 'w' is 0=Sun to 6=Sat
                
                $data[$i] = [ // Use MySQL's DAYOFWEEK index (1-7) as key
                    'day_num' => $i, // MySQL's DAYOFWEEK (1=Sun, 2=Mon...)
                    'day_name' => $days[$i-1], // PHP's day_name (0=Sun, 1=Mon...)
                    'date' => $day_date->format('Y-m-d'),
                    'count' => 0,
                    'sales' => 0
                ];
            }
            
            // Fill in actual data
            while ($row = $result->fetch_assoc()) {
                $day_num = (int)$row['day_of_week']; // This is MySQL's DAYOFWEEK (1=Sunday, 2=Monday...)
                $data[$day_num] = [
                    'day_num' => $day_num,
                    'day_name' => $days[$day_num-1], // Map MySQL's DAYOFWEEK (1-7) to PHP's day name array (0-6)
                    'date' => $row['date'],
                    'count' => (int)$row['count'],
                    'sales' => (float)$row['sales']
                ];
            }
            
            $stmt->close();
            
            // Get total for the week
            $total_sql = "SELECT 
                                COUNT(*) as total_count,
                                SUM(totalPrice) as total_sales,
                                AVG(stayDuration) as avg_duration
                            FROM bookingstatus 
                            WHERE checkinDate BETWEEN ? AND ?
                            AND status != 'Cancelled'";
                            
            $total_stmt = $conn->prepare($total_sql);
            $total_stmt->bind_param("ss", $formattedStartDate, $formattedEndDate); // Pass variables
            $total_stmt->execute();
            $total_result = $total_stmt->get_result();
            $totals = $total_result->fetch_assoc();
            
            $data['totals'] = [
                'count' => $totals['total_count'] ?? 0,
                'sales' => $totals['total_sales'] ?? 0,
                'avg_duration' => $totals['avg_duration'] ?? 0
            ];
            
            $total_stmt->close();
            
        } elseif ($period === 'month') {
            // For monthly view - reservations by week
            $start = clone $dt;
            $start->modify('first day of this month');
            $end = clone $start;
            $end->modify('last day of this month');
            
            // Get all weeks in the month
            $weeks = [];
            $current = clone $start;
            $week_num = 1;
            
            while ($current <= $end) {
                $week_start = clone $current;
                $week_end = clone $current;
                // Move to the end of the current week (Sunday)
                // If it goes past the month end, cap it at month end
                $week_end->modify('Sunday this week'); 
                
                if ($week_end > $end) {
                    $week_end = clone $end;
                }
                
                $weeks[$week_num] = [
                    'week_num' => $week_num,
                    'start_date' => $week_start->format('Y-m-d'),
                    'end_date' => $week_end->format('Y-m-d'),
                    'count' => 0,
                    'sales' => 0
                ];
                
                // Move to the next Monday for the next week
                $current->modify('next Monday'); 
                $week_num++;
            }
            
            // Get data for each week
            foreach ($weeks as $week_idx => $week) { // Use $week_idx to maintain the numeric key
                $sql = "SELECT 
                            COUNT(*) as count,
                            SUM(totalPrice) as sales
                        FROM bookingstatus 
                        WHERE checkinDate BETWEEN ? AND ?
                        AND status != 'Cancelled'";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $week['start_date'], $week['end_date']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    $weeks[$week_idx]['count'] = (int)$row['count'];
                    $weeks[$week_idx]['sales'] = (float)$row['sales'];
                }
                
                $stmt->close();
            }
            
            $data = $weeks; // Assign the processed weeks data
            
            // Get total for the month
            $total_sql = "SELECT 
                                COUNT(*) as total_count,
                                SUM(totalPrice) as total_sales,
                                AVG(stayDuration) as avg_duration
                            FROM bookingstatus 
                            WHERE checkinDate BETWEEN ? AND ?
                            AND status != 'Cancelled'";
                            
            $total_stmt = $conn->prepare($total_sql);
            $formattedStartDate = $start->format('Y-m-d'); // Store in variable
            $formattedEndDate = $end->format('Y-m-d');     // Store in variable
            $total_stmt->bind_param("ss", $formattedStartDate, $formattedEndDate); // Pass variables
            $total_stmt->execute();
            $total_result = $total_stmt->get_result();
            $totals = $total_result->fetch_assoc();
            
            $data['totals'] = [
                'count' => $totals['total_count'] ?? 0,
                'sales' => $totals['total_sales'] ?? 0,
                'avg_duration' => $totals['avg_duration'] ?? 0
            ];
            
            $total_stmt->close();
        }
        
    } catch (Exception $e) {
        error_log("Error in getReservationData: " . $e->getMessage());
    }
    
    return $data;
}

// Get current period data
$current_data = getReservationData($conn, $period, $current_date_str);

// Get comparison data if requested
$compare_data = null;
if ($compare_with !== 'none' && isset($compare_date_str)) {
    $compare_data = getReservationData($conn, $period, $compare_date_str);
}

// Prepare data for charts
function prepareChartData($data, $period) {
    $labels = [];
    $counts = [];
    $sales = [];
    
    if ($period === 'day') {
        // Hourly data for day view
        for ($i = 0; $i < 24; $i++) {
            $labels[] = sprintf("%02d:00", $i);
            $counts[] = $data[$i]['count'];
            $sales[] = $data[$i]['sales'];
        }
    } elseif ($period === 'week') {

        $ordered_data = [];

        for ($i = 2; $i <= 7; $i++) { // Monday to Saturday
            if (isset($data[$i])) {
                $ordered_data[] = $data[$i];
            }
        }
        if (isset($data[1])) { // Sunday
            $ordered_data[] = $data[1];
        }

        foreach ($ordered_data as $item) {
            $labels[] = $item['day_name'];
            $counts[] = $item['count'];
            $sales[] = $item['sales'];
        }
    } elseif ($period === 'month') {
        // Weekly data for month view
        // Filter out the 'totals' key before iterating for chart
        $chart_weeks = array_filter($data, function($k) { return $k !== 'totals'; }, ARRAY_FILTER_USE_KEY);
        foreach ($chart_weeks as $week_num => $week) {
            $labels[] = "Week " . $week_num;
            $counts[] = $week['count'];
            $sales[] = $week['sales'];
        }
    }
    
    return [
        'labels' => $labels,
        'counts' => $counts,
        'sales' => $sales
    ];
}

$current_chart_data = prepareChartData($current_data, $period);

if ($compare_data) {
    $compare_chart_data = prepareChartData($compare_data, $period);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reservation Analytics Dashboard - My Resort</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f7f6;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            padding: 30px;
        }
        
        h1, h2, h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        h1 {
            font-size: 2.5em;
            margin-bottom: 0;
        }

        h2 {
            font-size: 1.8em;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-top: 30px;
        }

        h3 {
            font-size: 1.3em;
            margin-bottom: 10px;
        }
        
        .analytics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .period-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .period-selector a {
            padding: 10px 20px;
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #555;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .period-selector a.active {
            background-color: #3498db;
            color: white;
            border-color: #2980b9;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .period-selector a:hover:not(.active) {
            background-color: #e9e9e9;
            border-color: #ccc;
        }
        
        .date-navigation {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
        }
        
        .date-navigation form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .date-navigation input[type="date"],
        .date-navigation input[type="week"],
        .date-navigation input[type="month"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            flex-grow: 1; /* Allow input to grow */
            min-width: 150px; /* Minimum width for date inputs */
        }
        
        .date-navigation button {
            padding: 10px 18px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        
        .date-navigation button:hover {
            background-color: #2980b9;
            transform: translateY(-1px);
        }

        .date-navigation select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            background-color: white;
            cursor: pointer;
            min-width: 150px;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Responsive grid */
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .card {
            background-color: #fdfdfd;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.2s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }
        
        .card h3 {
            margin-top: 0;
            font-size: 1.1em;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .card .value {
            font-size: 2.5em;
            font-weight: bold;
            margin: 10px 0;
            color: #34495e;
        }
        
        .card .change {
            font-size: 0.9em;
            margin-top: 10px;
        }
        
        .positive {
            color: #27ae60;
            font-weight: bold;
        }
        
        .negative {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .neutral {
            color: #7f8c8d;
            font-weight: bold;
        }
        
        .chart-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .chart-box {
            background-color: #fdfdfd;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .chart-box h3 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.4em;
            color: #34495e;
            text-align: center;
        }
        
        .chart-wrapper {
            position: relative;
            height: 350px; /* Increased height for better chart visibility */
            width: 100%;
        }
        
        .comparison-charts {
            display: grid;
            grid-template-columns: 1fr 1fr; /* Two columns for comparison */
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .comparison-title {
            grid-column: 1 / -1; /* Span across all columns */
            text-align: center;
            font-size: 2em;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background-color: #fdfdfd;
            border-radius: 10px;
            overflow: hidden; /* Ensures rounded corners for table */
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: #eef1f4;
            font-weight: 600;
            color: #34495e;
            text-transform: uppercase;
            font-size: 0.9em;
        }
        
        tr:nth-child(even) {
            background-color: #f8fbfd;
        }

        tr:hover {
            background-color: #eef5fc;
        }
        
        .upcoming-reservations {
            margin-top: 40px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .analytics-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .date-navigation form {
                flex-direction: column;
                align-items: stretch;
            }
            .comparison-charts {
                grid-template-columns: 1fr; /* Stack charts on small screens */
            }
            .summary-cards {
                grid-template-columns: 1fr; /* Stack cards on small screens */
            }
            .period-selector {
                flex-wrap: wrap;
            }
            .period-selector a {
                flex: 1 1 auto; /* Allow buttons to grow and wrap */
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="analytics-header">
            <h1>Reservation Analytics</h1>
            <div>
                <form method="get" action="">
                    <input type="hidden" name="period" value="<?php echo htmlspecialchars($period); ?>">
                    <input type="hidden" name="date" value="<?php echo htmlspecialchars($current_date_str); ?>">
                    <select name="compare_with" onchange="this.form.submit()">
                        <option value="none" <?php echo $compare_with === 'none' ? 'selected' : ''; ?>>No Comparison</option>
                        <option value="previous_day" <?php echo ($period === 'day' && $compare_with === 'previous_day') ? 'selected' : 'disabled'; ?>>Previous Day</option>
                        <option value="previous_week" <?php echo $compare_with === 'previous_week' ? 'selected' : ''; ?>>Previous Week</option>
                        <option value="previous_month" <?php echo $compare_with === 'previous_month' ? 'selected' : ''; ?>>Previous Month</option>
                        <option value="previous_year" <?php echo $compare_with === 'previous_year' ? 'selected' : ''; ?>>Previous Year</option>
                    </select>
                </form>
            </div>
        </div>
        
        <div class="period-selector">
            <a href="?period=day&date=<?php echo htmlspecialchars($current_date_str); ?>&compare_with=<?php echo htmlspecialchars($compare_with); ?>" class="<?php echo $period === 'day' ? 'active' : ''; ?>">Daily</a>
            <a href="?period=week&date=<?php echo htmlspecialchars($current_date_str); ?>&compare_with=<?php echo htmlspecialchars($compare_with); ?>" class="<?php echo $period === 'week' ? 'active' : ''; ?>">Weekly</a>
            <a href="?period=month&date=<?php echo htmlspecialchars($current_date_str); ?>&compare_with=<?php echo htmlspecialchars($compare_with); ?>" class="<?php echo $period === 'month' ? 'active' : ''; ?>">Monthly</a>
        </div>
        
        <div class="date-navigation">
            <form method="get" action="">
                <input type="hidden" name="period" value="<?php echo htmlspecialchars($period); ?>">
                <input type="hidden" name="compare_with" value="<?php echo htmlspecialchars($compare_with); ?>">
                <?php if ($period === 'day'): ?>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($current_date_str); ?>" onchange="this.form.submit()">
                <?php elseif ($period === 'week'): ?>
                    <input type="week" name="date" value="<?php echo htmlspecialchars($current_date->format('Y-\WW')); ?>" onchange="this.form.submit()">
                <?php elseif ($period === 'month'): ?>
                    <input type="month" name="date" value="<?php echo htmlspecialchars($current_date->format('Y-m')); ?>" onchange="this.form.submit()">
                <?php endif; ?>
                <button type="submit" name="action" value="prev">&lt; Previous</button>
                <button type="submit" name="action" value="next">Next &gt;</button>
                <button type="submit" name="action" value="today">Today</button>
            </form>
        </div>
        
        <div class="summary-cards">
            <div class="card">
                <h3>Total Reservations</h3>
                <div class="value"><?php echo $current_data['totals']['count'] ?? 0; ?></div>
                <?php if ($compare_data && ($compare_data['totals']['count'] !== null)): ?>
                    <div class="change">
                        <?php 
                        $prev_count = $compare_data['totals']['count'] ?? 0;
                        $current_count = $current_data['totals']['count'] ?? 0;
                        $diff = $current_count - $prev_count;
                        $percent = $prev_count > 0 ? round(($diff / $prev_count) * 100, 1) : 0;
                        
                        if ($diff > 0) {
                            echo '<span class="positive">+' . $diff . ' (+' . $percent . '%)</span>';
                        } elseif ($diff < 0) {
                            echo '<span class="negative">' . $diff . ' (' . $percent . '%)</span>';
                        } else {
                            echo '<span class="neutral">No change</span>';
                        }
                        ?>
                        vs comparison period
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h3>Total Sales</h3>
                <div class="value">₱<?php echo number_format($current_data['totals']['sales'] ?? 0, 2); ?></div>
                <?php if ($compare_data && ($compare_data['totals']['sales'] !== null)): ?>
                    <div class="change">
                        <?php 
                        $prev_sales = $compare_data['totals']['sales'] ?? 0;
                        $current_sales = $current_data['totals']['sales'] ?? 0;
                        $diff = $current_sales - $prev_sales;
                        $percent = $prev_sales > 0 ? round(($diff / $prev_sales) * 100, 1) : 0;
                        
                        if ($diff > 0) {
                            echo '<span class="positive">+₱' . number_format($diff, 2) . ' (+' . $percent . '%)</span>';
                        } elseif ($diff < 0) {
                            echo '<span class="negative">-₱' . number_format(abs($diff), 2) . ' (' . $percent . '%)</span>';
                        } else {
                            echo '<span class="neutral">No change</span>';
                        }
                        ?>
                        vs comparison period
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h3>Average Duration</h3>
                <div class="value"><?php echo number_format($current_data['totals']['avg_duration'] ?? 0, 1); ?> hours</div>
                <?php if ($compare_data && ($compare_data['totals']['avg_duration'] !== null)): ?>
                    <div class="change">
                        <?php 
                        $prev_duration = $compare_data['totals']['avg_duration'] ?? 0;
                        $current_duration = $current_data['totals']['avg_duration'] ?? 0;
                        $diff = $current_duration - $prev_duration;
                        
                        if ($diff > 0) {
                            echo '<span class="positive">+' . number_format($diff, 1) . ' hours</span>';
                        } elseif ($diff < 0) {
                            echo '<span class="negative">' . number_format($diff, 1) . ' hours</span>';
                        } else {
                            echo '<span class="neutral">No change</span>';
                        }
                        ?>
                        vs comparison period
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($compare_data): ?>
            <h2 class="comparison-title">Analytics Comparison</h2>
            <div class="comparison-charts">
                <div class="chart-box">
                    <h3>Current Period (<?php echo htmlspecialchars($current_date_str); ?>)</h3>
                    <div class="chart-wrapper">
                        <canvas id="currentChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-box">
                    <h3>Comparison Period (<?php echo htmlspecialchars($compare_date_str); ?>)</h3>
                    <div class="chart-wrapper">
                        <canvas id="compareChart"></canvas>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="chart-container">
                <div class="chart-box">
                    <h3>Reservations by <?php echo $period === 'day' ? 'Hour' : ($period === 'week' ? 'Day' : 'Week'); ?></h3>
                    <div class="chart-wrapper">
                        <canvas id="reservationsChart"></canvas>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="chart-container">
            <div class="chart-box">
                <h3>Sales by <?php echo $period === 'day' ? 'Hour' : ($period === 'week' ? 'Day' : 'Week'); ?></h3>
                <div class="chart-wrapper">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>
        
        <h2>Detailed Data for Current Period</h2>
        <table>
            <thead>
                <tr>
                    <?php if ($period === 'day'): ?>
                        <th>Hour</th>
                    <?php elseif ($period === 'week'): ?>
                        <th>Day</th>
                        <th>Date</th>
                    <?php elseif ($period === 'month'): ?>
                        <th>Week</th>
                        <th>Date Range</th>
                    <?php endif; ?>
                    <th>Reservations</th>
                    <th>Sales</th>
                    <?php if ($compare_data): ?>
                        <th>Change (Reservations)</th>
                        <th>Change (Sales)</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Ensure data items are in a consistent order for table display
                $data_items_for_table = [];
                if ($period === 'day') {
                    for ($i = 0; $i < 24; $i++) {
                        $data_items_for_table[] = $current_data[$i];
                    }
                } elseif ($period === 'week') {
                    // Reorder for Monday to Sunday
                    for ($i = 2; $i <= 7; $i++) { // Monday to Saturday
                        if (isset($current_data[$i])) $data_items_for_table[] = $current_data[$i];
                    }
                    if (isset($current_data[1])) $data_items_for_table[] = $current_data[1]; // Sunday
                } elseif ($period === 'month') {
                    // Filter out 'totals' and keep original numeric keys for weeks
                    $data_items_for_table = array_filter($current_data, function($k) { return $k !== 'totals'; }, ARRAY_FILTER_USE_KEY);
                }

                foreach ($data_items_for_table as $item): 
                    // Find corresponding comparison item if it exists
                    $compare_item = null;
                    if ($compare_data) {
                        if ($period === 'day') {
                            $compare_item = $compare_data[$item['hour']] ?? null;
                        } elseif ($period === 'week') {
                            $compare_item = $compare_data[$item['day_num']] ?? null;
                        } elseif ($period === 'month') {
                            $compare_item = $compare_data[$item['week_num']] ?? null;
                        }
                    }
                ?>
                    <tr>
                        <?php if ($period === 'day'): ?>
                            <td><?php echo sprintf("%02d:00", $item['hour']); ?></td>
                        <?php elseif ($period === 'week'): ?>
                            <td><?php echo htmlspecialchars($item['day_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['date']); ?></td>
                        <?php elseif ($period === 'month'): ?>
                            <td><?php echo "Week " . htmlspecialchars($item['week_num']); ?></td>
                            <td><?php echo htmlspecialchars($item['start_date']) . " to " . htmlspecialchars($item['end_date']); ?></td>
                        <?php endif; ?>
                        
                        <td><?php echo htmlspecialchars($item['count']); ?></td>
                        <td>₱<?php echo number_format($item['sales'], 2); ?></td>
                        
                        <?php if ($compare_data && $compare_item): ?>
                            <td>
                                <?php 
                                $count_diff = $item['count'] - ($compare_item['count'] ?? 0);
                                if ($count_diff > 0) {
                                    echo '<span class="positive">+' . $count_diff . '</span>';
                                } elseif ($count_diff < 0) {
                                    echo '<span class="negative">' . $count_diff . '</span>';
                                } else {
                                    echo '<span class="neutral">0</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                $sales_diff = $item['sales'] - ($compare_item['sales'] ?? 0);
                                if ($sales_diff > 0) {
                                    echo '<span class="positive">+₱' . number_format($sales_diff, 2) . '</span>';
                                } elseif ($sales_diff < 0) {
                                    echo '<span class="negative">-₱' . number_format(abs($sales_diff), 2) . '</span>';
                                } else {
                                    echo '<span class="neutral">₱0.00</span>';
                                }
                                ?>
                            </td>
                        <?php else: // Display empty columns if no comparison data ?>
                            <td><span class="neutral">-</span></td>
                            <td><span class="neutral">-</span></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="upcoming-reservations">
            <h2>Upcoming Reservations (Next 10)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Guest Name</th>
                        <th>Room</th>
                        <th>Check-in Date</th>
                        <th>Check-in Time</th>
                        <th>Duration</th>
                        <th>Total Price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch upcoming reservations from today onwards
                    $upcoming_sql = "SELECT id, guestName, roomName, checkinDate, checkinTime, stayDuration, totalPrice, status
                                     FROM bookingstatus 
                                     WHERE checkinDate >= ? 
                                     AND status != 'Cancelled'
                                     ORDER BY checkinDate ASC, checkinTime ASC 
                                     LIMIT 10";
                    $upcoming_stmt = $conn->prepare($upcoming_sql);
                    $today_date_str = date('Y-m-d'); // Use current actual date for upcoming
                    $upcoming_stmt->bind_param("s", $today_date_str);
                    $upcoming_stmt->execute();
                    $upcoming_result = $upcoming_stmt->get_result();
                    
                    if ($upcoming_result->num_rows > 0) {
                        while ($booking = $upcoming_result->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['id']); ?></td>
                            <td><?php echo htmlspecialchars($booking['guestName']); ?></td>
                            <td><?php echo htmlspecialchars($booking['roomName']); ?></td>
                            <td><?php echo htmlspecialchars($booking['checkinDate']); ?></td>
                            <td><?php echo htmlspecialchars($booking['checkinTime']); ?></td>
                            <td><?php echo htmlspecialchars($booking['stayDuration']); ?> hours</td>
                            <td>₱<?php echo number_format($booking['totalPrice'], 2); ?></td>
                            <td><?php echo htmlspecialchars($booking['status']); ?></td>
                        </tr>
                    <?php 
                        endwhile;
                    } else {
                        echo '<tr><td colspan="8" style="text-align: center;">No upcoming reservations found.</td></tr>';
                    }
                    $upcoming_stmt->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        // Data from PHP for Chart.js
        const currentChartLabels = <?php echo json_encode($current_chart_data['labels']); ?>;
        const currentChartCounts = <?php echo json_encode($current_chart_data['counts']); ?>;
        const currentChartSales = <?php echo json_encode($current_chart_data['sales']); ?>;

        <?php if ($compare_data): ?>
        const compareChartLabels = <?php echo json_encode($compare_chart_data['labels']); ?>;
        const compareChartCounts = <?php echo json_encode($compare_chart_data['counts']); ?>;
        const compareChartSales = <?php echo json_encode($compare_chart_data['sales']); ?>;
        <?php endif; ?>

        // Function to create a generic bar chart
        function createBarChart(elementId, labels, data, chartLabel, color, type = 'bar') {
            const ctx = document.getElementById(elementId).getContext('2d');
            return new Chart(ctx, {
                type: type,
                data: {
                    labels: labels,
                    datasets: [{
                        label: chartLabel,
                        data: data,
                        backgroundColor: color,
                        borderColor: color.replace('0.6', '1'), // Solid color for border
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Important for chart-wrapper to control size
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: chartLabel.includes('Sales') ? 'Amount (₱)' : 'Count'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: '<?php echo $period === "day" ? "Time of Day" : ($period === "week" ? "Day of Week" : "Week of Month"); ?>'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false // Hide legend if only one dataset
                        },
                        title: {
                            display: true,
                            text: chartLabel
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (chartLabel.includes('Sales')) {
                                        label += '₱' + new Intl.NumberFormat('en-PH').format(context.raw);
                                    } else {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Initialize charts
        window.onload = function() {
            <?php if ($compare_data): ?>
                // Comparison Charts
                createBarChart('currentChart', currentChartLabels, currentChartCounts, 'Reservations - Current Period', 'rgba(52, 152, 219, 0.6)');
                createBarChart('compareChart', compareChartLabels, compareChartCounts, 'Reservations - Comparison Period', 'rgba(231, 76, 60, 0.6)');


                createBarChart('salesChart', currentChartLabels, currentChartSales, 'Sales - Current Period', 'rgba(46, 204, 113, 0.6)');

            <?php else: ?>
                // Single period charts
                createBarChart('reservationsChart', currentChartLabels, currentChartCounts, 'Number of Reservations', 'rgba(52, 152, 219, 0.6)');
                createBarChart('salesChart', currentChartLabels, currentChartSales, 'Total Sales', 'rgba(46, 204, 113, 0.6)');
            <?php endif; ?>
        };
    </script>
</body>
</html>