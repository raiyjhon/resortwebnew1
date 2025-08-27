<?php
// Include your database connection
require_once 'db.php';

// Get current month and year
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Calculate previous and next month for navigation
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// Get first day of month and total days
$first_day = mktime(0, 0, 0, $month, 1, $year);
$total_days = date('t', $first_day);
$first_day_of_week = date('w', $first_day); // 0=Sunday, 6=Saturday

// Fetch bookings for the month
$start_date = "$year-$month-01";
$end_date = "$year-$month-$total_days";
$query = "SELECT id, guestName, roomName, time_slot, checkinDate, checkinTime, checkoutTime 
          FROM bookingstatus 
          WHERE checkinDate BETWEEN '$start_date' AND '$end_date' 
          AND status != 'Cancelled'
          ORDER BY checkinDate, checkinTime";
$result = $conn->query($query);
$bookings = [];
while ($row = $result->fetch_assoc()) {
    $day = date('j', strtotime($row['checkinDate']));
    $bookings[$day][] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Booking Calendar</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .calendar {
            width: 100%;
            border-collapse: collapse;
        }
        .calendar th, .calendar td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            height: 100px;
            vertical-align: top;
        }
        .calendar th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .day-number {
            font-weight: bold;
            text-align: left;
            margin-bottom: 5px;
        }
        .booking {
            font-size: 12px;
            margin-bottom: 3px;
            padding: 3px;
            border-radius: 3px;
            text-align: left;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .time-slot {
            font-style: italic;
            color: #555;
            font-size: 11px;
        }
        .nav {
            margin: 20px 0;
            text-align: center;
        }
        .nav a {
            text-decoration: none;
            padding: 5px 15px;
            margin: 0 10px;
            background-color: #4CAF50;
            color: white;
            border-radius: 4px;
        }
        .nav a:hover {
            background-color: #45a049;
        }
        .nav h2 {
            display: inline-block;
            margin: 0 20px;
        }
        .am-slot {
            background-color: #e6f7ff;
            border-left: 3px solid #1890ff;
        }
        .pm-slot {
            background-color: #fff7e6;
            border-left: 3px solid #fa8c16;
        }
        .whole-slot {
            background-color: #f6ffed;
            border-left: 3px solid #52c41a;
        }
        .today {
            background-color: #fff2f2;
        }
        .dashboard-btn {
            display: inline-block;
            margin: 10px 0;
            padding: 8px 15px;
            background-color: #555;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .dashboard-btn:hover {
            background-color: #333;
        }
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <a href="dashboard.php" class="dashboard-btn">← Back to Dashboard</a>
        <h1>Booking Calendar</h1>
    </div>

    <div class="nav">
        <a href="?month=<?= $prev_month ?>&year=<?= $prev_year ?>">&lt; Previous</a>
        <h2><?= date('F Y', $first_day) ?></h2>
        <a href="?month=<?= $next_month ?>&year=<?= $next_year ?>">Next &gt;</a>
    </div>

    <table class="calendar">
        <tr>
            <th>Sunday</th>
            <th>Monday</th>
            <th>Tuesday</th>
            <th>Wednesday</th>
            <th>Thursday</th>
            <th>Friday</th>
            <th>Saturday</th>
        </tr>
        <tr>
            <?php
            // Fill empty cells for first week
            for ($i = 0; $i < $first_day_of_week; $i++) {
                echo '<td></td>';
            }

            $day_counter = 1;
            $today = date('Y-m-d');
            while ($day_counter <= $total_days) {
                if (($day_counter + $first_day_of_week - 1) % 7 == 0 && $day_counter != 1) {
                    echo '</tr><tr>';
                }
                
                $current_date = date('Y-m-d', strtotime("$year-$month-$day_counter"));
                $is_today = ($current_date == $today) ? 'today' : '';
                
                echo '<td class="' . $is_today . '">';
                echo '<div class="day-number">' . $day_counter . '</div>';
                
                // Display bookings for this day
                if (isset($bookings[$day_counter])) {
                    foreach ($bookings[$day_counter] as $booking) {
                        $time_slot_class = strtolower($booking['time_slot']) . '-slot';
                        $time_display = '';
                        
                        if ($booking['time_slot'] != 'whole') {
                            $time_display = ' (' . ($booking['time_slot'] == 'am' ? 'Morning' : 'Afternoon') . ')';
                        }
                        
                        echo '<div class="booking ' . $time_slot_class . '" title="Booking ID: ' . $booking['id'] . '">';
                        echo '<strong>' . htmlspecialchars($booking['guestName']) . '</strong><br>';
                        echo htmlspecialchars($booking['roomName']) . $time_display;
                        
                        // Show time range if available
                        if ($booking['checkinTime'] && $booking['checkoutTime']) {
                            echo '<div class="time-slot">';
                            echo date('g:i A', strtotime($booking['checkinTime'])) . ' - ' . date('g:i A', strtotime($booking['checkoutTime']));
                            echo '</div>';
                        }
                        
                        echo '</div>';
                    }
                }
                
                echo '</td>';
                $day_counter++;
            }

            // Fill remaining cells in last week
            while (($day_counter + $first_day_of_week - 1) % 7 != 0) {
                echo '<td></td>';
                $day_counter++;
            }
            ?>
        </tr>
    </table>
</body>
</html>