<?php
// client-portal.php

require_once 'config.php';

// Connect to database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Backup function
function backupJobs($conn) {
    $filename = BACKUP_FOLDER . 'jobs_backup_' . date('Y-m-d_H-i-s') . '.csv';
    $fp = fopen($filename, 'w');

    // Get all jobs
    $result = $conn->query("SELECT * FROM jobs");

    // Write CSV headers
    $fields = array('id', 'customer_name', 'phone_number', 'serial_number', 'fault_description', 'diagnostics_summary', 'accessories', 'engineer_assigned', 'status', 'parts_used', 'part_numbers', 'created_at');
    fputcsv($fp, $fields);

    // Write rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($fp, $row);
    }

    fclose($fp);

    return basename($filename);
}

// Restore function
function restoreJobs($conn, $file) {
    $csv = array_map('str_getcsv', file($file));
    $headers = array_shift($csv);

    foreach ($csv as $row) {
        $data = array_combine($headers, $row);
        if (!$data) continue;

        $stmt = $conn->prepare("INSERT INTO jobs (customer_name, phone_number, serial_number, fault_description, diagnostics_summary, accessories, engineer_assigned, status, parts_used, part_numbers) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "ssssssssss",
            $data['customer_name'],
            $data['phone_number'],
            $data['serial_number'],
            $data['fault_description'],
            $data['diagnostics_summary'],
            $data['accessories'],
            $data['engineer_assigned'],
            $data['status'],
            $data['parts_used'],
            $data['part_numbers']
        );
        $stmt->execute();
    }
}

// Handle form submission
if (isset($_POST['add_job'])) {
    $stmt = $conn->prepare("INSERT INTO jobs (customer_name, phone_number, serial_number, fault_description, diagnostics_summary, accessories, engineer_assigned, status, parts_used, part_numbers) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "ssssssssss",
        $_POST['customer_name'],
        $_POST['phone_number'],
        $_POST['serial_number'],
        $_POST['fault_description'],
        $_POST['diagnostics_summary'],
        $_POST['accessories'],
        $_POST['engineer_assigned'],
        $_POST['status'],
        $_POST['parts_used'],
        $_POST['part_numbers']
    );
    $stmt->execute();
    header('Location: client-portal.php');
    exit();
}
// Handle job deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM jobs WHERE id = $id");
    header('Location: client-portal.php');
    exit();
}

// Handle backup
if (isset($_GET['backup'])) {
    $backupFile = backupJobs($conn);
    header('Location: backups/' . $backupFile);
    exit();
}

// Handle restore
if (isset($_POST['restore']) && isset($_FILES['restore_file'])) {
    $filePath = $_FILES['restore_file']['tmp_name'];
    restoreJobs($conn, $filePath);
    header('Location: client-portal.php');
    exit();
}

// Fetch last backup timestamp
function getLastBackup() {
    $files = glob(BACKUP_FOLDER . 'jobs_backup_*.csv');
    if (!$files) return "No backups yet.";
    $lastFile = end($files);
    return date("d-m-Y H:i:s", filemtime($lastFile));
}

// Fetch all jobs
$jobs = $conn->query("SELECT * FROM jobs ORDER BY created_at DESC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Job Sheet Portal</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #eee; }
        .form-section { margin-bottom: 40px; }
        .btn { padding: 8px 12px; margin-right: 10px; cursor: pointer; background: purple; color: #fff; border: none; }
    </style>
</head>
<body>

<h1>Job Sheet Portal</h1>

<div class="form-section">
    <h2>Add New Job</h2>
    <form method="POST" action="client-portal.php">
        <input type="text" name="customer_name" placeholder="Customer Name" required><br><br>
        <input type="text" name="phone_number" placeholder="Phone Number" required><br><br>
        <input type="text" name="serial_number" placeholder="Serial Number"><br><br>
        <textarea name="fault_description" placeholder="Fault Description"></textarea><br><br>
        <textarea name="diagnostics_summary" placeholder="Diagnostics Summary"></textarea><br><br>
        <input type="text" name="accessories" placeholder="Accessories Provided"><br><br>
        <input type="text" name="engineer_assigned" placeholder="Engineer Assigned"><br><br>
        <input type="text" name="status" placeholder="Status"><br><br>
        <input type="text" name="parts_used" placeholder="Parts Used"><br><br>
        <input type="text" name="part_numbers" placeholder="Part Numbers"><br><br>
        <button type="submit" name="add_job" class="btn">Add Job</button>
    </form>
</div>

<div class="form-section">
    <h2>Backup & Restore</h2>
    <form method="POST" enctype="multipart/form-data" action="client-portal.php">
        <button type="submit" name="backup_now" class="btn" onclick="window.location.href='client-portal.php?backup=1';return false;">Backup Now</button>
        Last Backup: <?php echo getLastBackup(); ?><br><br>
        <input type="file" name="restore_file" required>
        <button type="submit" name="restore" class="btn">Restore Backup</button>
    </form>
</div>
<div class="form-section">
    <h2>All Jobs</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>Serial</th>
                <th>Fault</th>
                <th>Diagnostics</th>
                <th>Accessories</th>
                <th>Engineer</th>
                <th>Status</th>
                <th>Parts Used</th>
                <th>Part Numbers</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($job = $jobs->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($job['id']); ?></td>
                    <td><?php echo htmlspecialchars($job['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($job['phone_number']); ?></td>
                    <td><?php echo htmlspecialchars($job['serial_number']); ?></td>
                    <td><?php echo htmlspecialchars($job['fault_description']); ?></td>
                    <td><?php echo htmlspecialchars($job['diagnostics_summary']); ?></td>
                    <td><?php echo htmlspecialchars($job['accessories']); ?></td>
                    <td><?php echo htmlspecialchars($job['engineer_assigned']); ?></td>
                    <td><?php echo htmlspecialchars($job['status']); ?></td>
                    <td><?php echo htmlspecialchars($job['parts_used']); ?></td>
                    <td><?php echo htmlspecialchars($job['part_numbers']); ?></td>
                    <td><?php echo htmlspecialchars($job['created_at']); ?></td>
                    <td>
                        <a href="client-portal.php?delete=<?php echo $job['id']; ?>" onclick="return confirm('Delete this job?');">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>
<?php $conn->close(); ?>
