<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

require 'config/db.php';

$user_id = $_SESSION['user_id'];
$query = $conn->prepare("SELECT expression, result, created_at FROM history WHERE user_id = ? ORDER BY created_at DESC");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="calculation_history.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Expression', 'Result', 'Date']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        htmlspecialchars_decode($row['expression']),
        htmlspecialchars_decode($row['result']),
        $row['created_at']
    ]);
}

fclose($output);
exit();
?>