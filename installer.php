<?php
// installer.php

require_once 'config.php';

// Connect to database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// Create jobs table
$sql = "CREATE TABLE IF NOT EXISTS jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(255) NOT NULL,
    serial_number VARCHAR(255) NOT NULL,
    fault_description TEXT,
    diagnostics_summary TEXT,
    accessories TEXT,
    engineer_assigned VARCHAR(255),
    status VARCHAR(100),
    parts_used TEXT,
    part_numbers TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'jobs' created successfully or already exists.";
} else {
    echo "❌ Error creating table: " . $conn->error;
}

$conn->close();
?>
