<?php
$host = 'localhost';
$dbname = 'car_rental';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  die("Database error: " . $e->getMessage());
}
?>