<?php
require 'config.php';

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $car = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($car);
} 