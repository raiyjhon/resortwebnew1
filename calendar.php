<?php
// Include your database connection
require_once 'db.php';

// --- IMPORTANT: Replace 'John Doe' with the actual logged-in user's name from your session or authentication system ---
// For example: $current_user_name = $_SESSION['user_name'] ?? 'Guest';
$current_user_name = 'John Doe'; // Placeholder: Change this to the actual logged-in user's name

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Booking Calendar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom styles for specific elements not easily handled by Tailwind or for overrides */
        body {
            font-family: 'Inter', sans-serif; /* Using Inter font */
        }

        /* Base calendar grid for larger screens (default) */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr); /* 7 columns for full calendar view */
            gap: 1px; /* Small gap for borders */
            border: 1px solid #e2e8f0; /* Light border around the whole calendar */
            border-radius: 0.5rem; /* Rounded corners for the calendar */
            overflow: hidden; /* Ensures rounded corners are applied */
        }

        /* Mobile View: Stack days vertically */
        @media (max-width: 767px) { /* On screens smaller than 'md' breakpoint */
            .calendar-grid {
                display: block; /* Change to block layout for vertical stacking */
                border: none; /* Remove main calendar border */
                border-radius: 0; /* Remove main calendar border-radius */
            }

            .calendar-header {
                display: none; /* Hide day headers in mobile list view */
            }

            .calendar-cell {
                min-height: auto; /* Auto height for mobile cells */
                border: 1px solid #e2e8f0; /* Add border to individual cells */
                margin-bottom: 0.5rem; /* Space between days */
                border-radius: 0.5rem; /* Rounded corners for each day block */
                padding: 1rem; /* Adjust padding for mobile cells */
                flex-direction: row; /* Arrange day number and bookings horizontally */
                align-items: flex-start; /* Align content to top */
                position: relative; /* For positioning day number */
            }

            .calendar-cell:nth-child(7n),
            .calendar-cell:nth-last-child(-n + 7) {
                border-right: 1px solid #e2e8f0; /* Ensure borders on all sides for individual blocks */
                border-bottom: 1px solid #e2e8f0;
            }

            .day-number {
                position: static; /* Reset positioning for day number */
                margin-right: 1rem; /* Space between day number and bookings */
                font-size: 1.5rem; /* Larger day number for mobile */
                min-width: 40px; /* Ensure day number has space */
                text-align: center;
                flex-shrink: 0; /* Prevent day number from shrinking */
            }

            .booking-list {
                margin-top: 0; /* No top margin needed with new layout */
                max-height: none; /* Remove max-height for mobile list view */
                overflow-y: visible; /* Allow content to push cell height */
                flex-grow: 1; /* Allow booking list to take available space */
            }

            .booking-item {
                white-space: normal; /* Allow text to wrap within booking items */
                text-overflow: clip; /* No ellipsis needed if wrapping */
            }

            .today-cell {
                border: 2px solid #ef4444; /* Keep border for today */
                box-shadow: 0 0 0 2px #ef4444;
            }
        }

        /* Default (larger screens) styles */
        .calendar-header {
            background-color: #f8fafc; /* Light background for day names */
            font-weight: 600; /* Semi-bold */
            padding: 0.75rem; /* Padding */
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
        }

        .calendar-cell {
            background-color: #ffffff; /* White background for cells */
            padding: 0.75rem;
            min-height: 120px; /* Minimum height for cells */
            vertical-align: top;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            align-items: flex-start; /* Align content to top-left */
            position: relative;
        }

        .calendar-cell:nth-child(7n) {
            border-right: none; /* No right border for the last column on larger screens */
        }

        .calendar-cell:nth-last-child(-n + 7) {
            border-bottom: none; /* No bottom border for the last row on larger screens */
        }

        .day-number {
            font-weight: 700; /* Bold day number */
            font-size: 1.125rem; /* Larger font size */
            color: #334155; /* Darker text color */
            margin-bottom: 0.5rem;
            position: absolute; /* Position day number at top-left */
            top: 8px;
            left: 8px;
            z-index: 2; /* Ensure day number is above bookings if they overflow */
        }
        .booking-list {
            margin-top: 2rem; /* Space for day number */
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 0.25rem; /* Space between bookings */
            overflow-y: auto; /* Enable scrolling for many bookings */
            max-height: calc(100% - 2.5rem); /* Limit height to prevent overflow out of cell */
        }
        .booking-item {
            font-size: 0.875rem; /* Smaller font for bookings */
            padding: 0.5rem;
            border-radius: 0.375rem; /* Rounded corners for booking items */
            text-align: left;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap; /* Prevent text wrapping */
            box-shadow: 0 1px 2px rgba(0,0,0,0.05); /* Subtle shadow */
            flex-shrink: 0; /* Prevent items from shrinking */
        }
        .am-slot {
            background-color: #e0f2fe; /* Light blue */
            border-left: 4px solid #0ea5e9; /* Blue border */
        }
        .pm-slot {
            background-color: #fff7ed; /* Light orange */
            border-left: 4px solid #f97316; /* Orange border */
        }
        .whole-slot {
            background-color: #ecfdf5; /* Light green */
            border-left: 4px solid #22c55e; /* Green border */
        }
        .time-display {
            font-style: italic;
            color: #64748b; /* Grayish text */
            font-size: 0.75rem; /* Even smaller font */
            margin-top: 0.25rem;
        }
        .today-cell {
            background-color: #fef2f2; /* Light red for today */
            border: 2px solid #ef4444; /* Red border for today */
            box-shadow: 0 0 0 2px #ef4444; /* Outline for today */
            z-index: 1; /* Bring today's cell to front */
        }
        .other-booking-text {
            font-weight: 600; /* Semi-bold for "Reserved" */
            color: #6b7280; /* Darker gray for reserved */
        }
        /* Custom button styles */
        .btn {
            @apply px-4 py-2 rounded-lg font-semibold transition-colors duration-200 ease-in-out;
        }
        .btn-primary {
            @apply bg-blue-600 text-white hover:bg-blue-700 shadow-md;
        }
        .btn-secondary {
            @apply bg-gray-200 text-gray-800 hover:bg-gray-300 shadow-sm;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 antialiased p-4 sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto bg-white rounded-xl shadow-lg p-6 sm:p-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6">
            <a href="booklogin.php" class="btn btn-secondary mb-4 sm:mb-0">
                &larr; Back to Dashboard
            </a>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-800 text-center flex-grow">
                My Booking Calendar
            </h1>
            <div class="w-auto sm:w-48"></div> </div>

        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 px-4 py-2 bg-gray-50 rounded-lg shadow-inner">
            <a href="?month=<?= $prev_month ?>&year=<?= $prev_year ?>" class="btn btn-primary mb-2 sm:mb-0">
                &lt; Previous
            </a>
            <h2 class="text-2xl font-bold text-gray-700 mx-auto sm:mx-0">
                <?= date('F Y', $first_day) ?>
            </h2>
            <a href="?month=<?= $next_month ?>&year=<?= $next_year ?>" class="btn btn-primary mt-2 sm:mt-0">
                Next &gt;
            </a>
        </div>

        <div class="calendar-grid">
            <?php
            // Day headers - only show on screens wider than mobile (md breakpoint)
            $days_of_week = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            foreach ($days_of_week as $day_name) {
                // Hide full day names on small screens, show abbreviated
                echo '<div class="calendar-header hidden md:block">' . $day_name . '</div>';
                echo '<div class="calendar-header block md:hidden">' . substr($day_name, 0, 3) . '</div>';
            }

            // Fill empty cells for first week - only for grid view (on md and larger screens)
            // These will be hidden in the mobile view via CSS
            for ($i = 0; $i < $first_day_of_week; $i++) {
                echo '<div class="calendar-cell hidden md:block"></div>';
            }

            $day_counter = 1;
            $today = date('Y-m-d');
            while ($day_counter <= $total_days) {
                $current_date_full = date('Y-m-d', strtotime("$year-$month-$day_counter"));
                $is_today_class = ($current_date_full == $today) ? 'today-cell' : '';

                // For mobile, we will show all days sequentially.
                // For desktop, these will be part of the grid.
                echo '<div class="calendar-cell ' . $is_today_class . '">';
                echo '<div class="day-number">' . $day_counter . '</div>';
                echo '<div class="booking-list">';

                // Display bookings for this day
                if (isset($bookings[$day_counter])) {
                    foreach ($bookings[$day_counter] as $booking) {
                        $time_slot_class = strtolower($booking['time_slot']) . '-slot';
                        $time_display = '';

                        if ($booking['time_slot'] == 'am') {
                            $time_display = ' (Morning)';
                        } elseif ($booking['time_slot'] == 'pm') {
                            $time_display = ' (Afternoon)';
                        }

                        echo '<div class="booking-item ' . $time_slot_class . '">';

                        // Check if the booking belongs to the current user
                        if (htmlspecialchars($booking['guestName']) == $current_user_name) {
                            echo '<strong class="text-gray-900">' . htmlspecialchars($booking['guestName']) . '</strong><br>';
                        } else {
                            // For other users, display "Reserved"
                            echo '<span class="other-booking-text">Reserved</span><br>';
                        }

                        echo '<span class="text-gray-700">' . htmlspecialchars($booking['roomName']) . $time_display . '</span>';

                        // Show time range if available
                        if ($booking['checkinTime'] && $booking['checkoutTime']) {
                            echo '<div class="time-display">';
                            echo date('g:i A', strtotime($booking['checkinTime'])) . ' - ' . date('g:i A', strtotime($booking['checkoutTime']));
                            echo '</div>';
                        }

                        echo '</div>';
                    }
                } else {
                    // Display "No bookings" if there are none for the day
                    echo '<p class="text-gray-500 text-sm mt-1">No bookings</p>';
                }


                echo '</div>'; // Close booking-list
                echo '</div>'; // Close calendar-cell
                $day_counter++;
            }

            // Fill remaining cells in last week - only for grid view (on md and larger screens)
            // These will be hidden in the mobile view via CSS
            while (($day_counter + $first_day_of_week - 1) % 7 != 0 && ($day_counter - 1 + $first_day_of_week) <= (7 * 6)) { // Ensure we don't add more than 6 weeks total
                echo '<div class="calendar-cell hidden md:block"></div>';
                $day_counter++;
            }
            ?>
        </div>
    </div>
</body>
</html>