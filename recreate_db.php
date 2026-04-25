<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
    $pdo->exec('DROP DATABASE IF EXISTS agriculture_pendataan');
    $pdo->exec('CREATE DATABASE agriculture_pendataan');
    echo 'Database recreated successfully';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
