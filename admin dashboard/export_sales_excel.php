<?php
require 'db.php';

// Set headers to force download CSV file as Excel
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=sales_report_' . date('Ymd') . '.csv');

$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Title row
fputcsv($output, ['Sales Report']);
fputcsv($output, []);

// Weekly sales
fputcsv($output, ['Weekly Sales (Last 7 Days)']);
fputcsv($output, ['Date', 'Total Sales (PHP)']);

$weeklyQuery = "
  SELECT DATE(created_at) AS sale_date, 
         SUM(totalPrice) AS total_sales
  FROM bookingstatus
  WHERE created_at >= CURDATE() - INTERVAL 6 DAY
  GROUP BY sale_date
  ORDER BY sale_date ASC
";
$weeklyResult = $conn->query($weeklyQuery);

$weeklyTotal = 0;
$weeklySales = [];
while ($row = $weeklyResult->fetch_assoc()) {
    $weeklySales[$row['sale_date']] = (float)$row['total_sales'];
    $weeklyTotal += (float)$row['total_sales'];
}
// Fill missing days with 0
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $total = $weeklySales[$date] ?? 0;
    fputcsv($output, [$date, number_format($total, 2)]);
}
fputcsv($output, ['Total Weekly Sales', number_format($weeklyTotal, 2)]);
fputcsv($output, []);

// Monthly sales
fputcsv($output, ['Monthly Sales (Last 12 Months)']);
fputcsv($output, ['Month', 'Total Sales (PHP)']);

$monthlyQuery = "
  SELECT DATE_FORMAT(created_at, '%Y-%m') AS sale_month,
         SUM(totalPrice) AS total_sales
  FROM bookingstatus
  WHERE created_at >= DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH, '%Y-%m-01')
  GROUP BY sale_month
  ORDER BY sale_month ASC
";
$monthlyResult = $conn->query($monthlyQuery);

$monthlyTotal = 0;
$monthlySales = [];
while ($row = $monthlyResult->fetch_assoc()) {
    $monthlySales[$row['sale_month']] = (float)$row['total_sales'];
    $monthlyTotal += (float)$row['total_sales'];
}
// Fill missing months with 0
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $total = $monthlySales[$month] ?? 0;
    fputcsv($output, [$month, number_format($total, 2)]);
}
fputcsv($output, ['Total Monthly Sales', number_format($monthlyTotal, 2)]);

fclose($output);
exit;
