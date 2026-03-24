<?php
// Database connection for FlyWings Flight Management System

$db_host = "localhost";
$db_user = "root";      // change if different in XAMPP
$db_pass = "";          // change if you set a password
$db_name = "flywing";  // create this database in phpMyAdmin

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

