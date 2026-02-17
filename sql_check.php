<?php
// Use shared config (replace credentials in includes/Config.php or use env)
require_once __DIR__ . '/includes/Config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Database Structure Check</h2>";
    
    // List all tables
    echo "<h3>1. List of all tables:</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";
    
    // Check student_attendance_summary table
    echo "<h3>2. student_attendance_summary table structure:</h3>";
    try {
        $stmt = $pdo->query("DESCRIBE student_attendance_summary");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($columns)) {
            echo "Table student_attendance_summary doesn't exist!";
        } else {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Default']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Show sample data
            echo "<h4>Sample data (first 5 rows):</h4>";
            $stmt = $pdo->query("SELECT * FROM student_attendance_summary LIMIT 5");
            $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<pre>" . htmlspecialchars(print_r($samples, true)) . "</pre>";
        }
    } catch (PDOException $e) {
        echo "Error checking student_attendance_summary: " . $e->getMessage();
    }
    
    // Check timetable_slots table
    echo "<h3>3. timetable_slots table structure:</h3>";
    try {
        $stmt = $pdo->query("DESCRIBE timetable_slots");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show sample JSON data from target_group
        echo "<h4>Sample target_group data:</h4>";
        $stmt = $pdo->query("SELECT slot_id, target_group FROM timetable_slots LIMIT 5");
        $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($samples as $sample) {
            echo "<p><strong>Slot ID: " . $sample['slot_id'] . "</strong></p>";
            $decoded = json_decode($sample['target_group'], true);
            echo "<pre>" . htmlspecialchars(print_r($decoded, true)) . "</pre>";
        }
    } catch (PDOException $e) {
        echo "Error checking timetable_slots: " . $e->getMessage();
    }
    
    // Complete Database Structure Analysis
    echo "<h3>4. Complete Database Structure Analysis:</h3>";
    
    foreach ($tables as $table) {
        echo "<h4>Table: " . htmlspecialchars($table) . "</h4>";
        
        // Get table structure
        $stmt = $pdo->query("DESCRIBE `$table`");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if table has primary/foreign keys
        echo "<h5>Indexes for $table:</h5>";
        $stmt = $pdo->query("SHOW INDEX FROM `$table`");
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($indexes)) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Key Name</th><th>Column</th><th>Non Unique</th><th>Index Type</th></tr>";
            foreach ($indexes as $idx) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($idx['Key_name']) . "</td>";
                echo "<td>" . htmlspecialchars($idx['Column_name']) . "</td>";
                echo "<td>" . htmlspecialchars($idx['Non_unique']) . "</td>";
                echo "<td>" . htmlspecialchars($idx['Index_type']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No indexes found</p>";
        }
        
        // Show first 2 rows for reference
        echo "<h5>Sample data (first 2 rows):</h5>";
        try {
            $stmt = $pdo->query("SELECT * FROM `$table` LIMIT 2");
            $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($samples)) {
                echo "<pre>" . htmlspecialchars(print_r($samples, true)) . "</pre>";
            } else {
                echo "<p>Table is empty</p>";
            }
        } catch (PDOException $e) {
            echo "<p>Error reading data: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        echo "<hr>";
    }
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>