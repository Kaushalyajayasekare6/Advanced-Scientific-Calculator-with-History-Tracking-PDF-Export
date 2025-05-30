<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

require 'vendor/autoload.php';
require 'config/db.php';

use Dompdf\Dompdf;

$user_id = $_SESSION['user_id'];
$query = $conn->prepare("SELECT expression, result, created_at FROM history WHERE user_id = ? ORDER BY created_at DESC");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

$html = '<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        h1 { color: #4361ee; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #4361ee; color: white; padding: 10px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Calculation History</h1>
    <table>
        <tr>
            <th>Expression</th>
            <th>Result</th>
            <th>Date</th>
        </tr>';

while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
        <td>'.htmlspecialchars($row['expression']).'</td>
        <td>'.htmlspecialchars($row['result']).'</td>
        <td>'.$row['created_at'].'</td>
    </tr>';
}

$html .= '</table>
    <p style="text-align: center; margin-top: 30px; color: #666;">
        Generated by MathGenius on '.date('F j, Y').'
    </p>
</body>
</html>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("calculation_history.pdf", ["Attachment" => true]);

exit();
?>