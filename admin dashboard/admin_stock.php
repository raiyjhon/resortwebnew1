<?php
session_start();
include 'db.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomId = $_POST['room_id'];
    $stock_6h_am = (int)$_POST['stock_6h_am'];
    $stock_6h_pm = (int)$_POST['stock_6h_pm'];
    $stock_12h = (int)$_POST['stock_12h'];

    // Validate: Each stock must not exceed 1
    if ($stock_6h_am > 1 || $stock_6h_pm > 1 || $stock_12h > 1) {
        header("Location: admin_stock.php?error=stock_exceeds_1");
        exit();
    }

    // (Optional) Validate total not exceeding 100
    $total = $stock_6h_am + $stock_6h_pm + $stock_12h;
    if ($total > 100) {
        header("Location: admin_stock.php?error=total_exceeds_100");
        exit();
    }

    $stmt = $conn->prepare("UPDATE rooms SET stock_6h_am=?, stock_6h_pm=?, stock_12h=? WHERE id=?");
    $stmt->bind_param("iiii", $stock_6h_am, $stock_6h_pm, $stock_12h, $roomId);
    $stmt->execute();

    header("Location: admin_stock.php?success=1");
    exit();
}

// Get all rooms
$rooms = $conn->query("SELECT * FROM rooms");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Room Stock</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        .stock-input { width: 50px; text-align: center; }
        .success { color: green; margin: 10px 0; }
        .error { color: red; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Manage Room Stock</h1>

    <?php if (isset($_GET['success'])): ?>
        <div class="success">Stock updated successfully!</div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="error">
            <?php 
                if ($_GET['error'] === 'stock_exceeds_1') {
                    echo "Each stock value must not exceed 1.";
                } elseif ($_GET['error'] === 'total_exceeds_100') {
                    echo "Total stock across all types must not exceed 100.";
                }
            ?>
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Room Name</th>
                <th>6h AM Stock</th>
                <th>6h PM Stock</th>
                <th>12h Stock</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($room = $rooms->fetch_assoc()): ?>
            <form method="POST">
                <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                <tr>
                    <td><?= htmlspecialchars($room['name']) ?></td>
                    <td>
                        <input type="number" name="stock_6h_am" class="stock-input" 
                               min="0" max="1" value="<?= $room['stock_6h_am'] ?>">
                    </td>
                    <td>
                        <input type="number" name="stock_6h_pm" class="stock-input" 
                               min="0" max="1" value="<?= $room['stock_6h_pm'] ?>">
                    </td>
                    <td>
                        <input type="number" name="stock_12h" class="stock-input" 
                               min="0" max="1" value="<?= $room['stock_12h'] ?>">
                    </td>
                    <td><button type="submit">Update</button></td>
                </tr>
            </form>
            <?php endwhile; ?>
        </tbody>
    </table>

    <script>
    document.querySelectorAll("form").forEach(form => {
        form.addEventListener("submit", function(e) {
            const stock6hAm = parseInt(form.stock_6h_am.value);
            const stock6hPm = parseInt(form.stock_6h_pm.value);
            const stock12h  = parseInt(form.stock_12h.value);

            if (stock6hAm > 1 || stock6hPm > 1 || stock12h > 1) {
                alert("Each stock value must not exceed 1.");
                e.preventDefault();
                return;
            }

            const total = stock6hAm + stock6hPm + stock12h;
            if (total > 100) {
                alert("Total stock must not exceed 100.");
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>
