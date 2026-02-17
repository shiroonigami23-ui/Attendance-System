<?php
require_once 'includes/Config.php';

echo "<h3>Creating Swap System Tables...</h3>";

try {
    // 1. Create swap_requests table
    $sql = "CREATE TABLE IF NOT EXISTS swap_requests (
        swap_id INT AUTO_INCREMENT PRIMARY KEY,
        requester_id BIGINT NOT NULL COMMENT 'Teacher who requested the swap',
        receiver_id BIGINT NOT NULL COMMENT 'Teacher who received the request',
        slot_id INT NOT NULL COMMENT 'Timetable slot to swap',
        requested_date DATE NOT NULL COMMENT 'Date of the slot to swap',
        status ENUM('PENDING', 'APPROVED', 'REJECTED', 'CANCELLED', 'COMPLETED') DEFAULT 'PENDING',
        request_notes TEXT COMMENT 'Optional notes from requester',
        response_notes TEXT COMMENT 'Optional notes from receiver',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        responded_at TIMESTAMP NULL,
        
        FOREIGN KEY (requester_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (slot_id) REFERENCES timetable_slots(slot_id) ON DELETE CASCADE,
        
        INDEX idx_requester (requester_id, status),
        INDEX idx_receiver (receiver_id, status),
        INDEX idx_slot (slot_id, requested_date)
    )";
    
    $pdo->exec($sql);
    echo "<p>✓ Created swap_requests table</p>";
    
    // 2. Create swap_resources table
    $sql = "CREATE TABLE IF NOT EXISTS swap_resources (
        resource_id INT AUTO_INCREMENT PRIMARY KEY,
        swap_id INT NOT NULL,
        teacher_id BIGINT NOT NULL COMMENT 'Teacher who uploaded the resource',
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_type VARCHAR(50) COMMENT 'PDF, PPT, IMG, etc',
        file_size INT COMMENT 'Size in bytes',
        upload_notes TEXT,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        FOREIGN KEY (swap_id) REFERENCES swap_requests(swap_id) ON DELETE CASCADE,
        FOREIGN KEY (teacher_id) REFERENCES users(user_id) ON DELETE CASCADE,
        
        INDEX idx_swap (swap_id),
        INDEX idx_teacher (teacher_id)
    )";
    
    $pdo->exec($sql);
    echo "<p>✓ Created swap_resources table</p>";
    
    // 3. Add swap-related columns to live_sessions if needed
    echo "<h4>Checking live_sessions table for swap columns...</h4>";
    
    // Check if swap_id column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM live_sessions LIKE 'swap_id'");
    if ($stmt->rowCount() == 0) {
        $sql = "ALTER TABLE live_sessions ADD COLUMN swap_id INT NULL AFTER slot_id";
        $pdo->exec($sql);
        echo "<p>✓ Added swap_id column to live_sessions</p>";
        
        // Add foreign key constraint
        $sql = "ALTER TABLE live_sessions ADD CONSTRAINT fk_live_session_swap 
                FOREIGN KEY (swap_id) REFERENCES swap_requests(swap_id) ON DELETE SET NULL";
        $pdo->exec($sql);
        echo "<p>✓ Added foreign key constraint for swap_id</p>";
    } else {
        echo "<p>✓ swap_id column already exists in live_sessions</p>";
    }
    
    echo "<hr><h3>✅ Swap System Tables Created Successfully!</h3>";
    
    // Show table structures
    echo "<h4>Table Structures:</h4>";
    
    $tables = ['swap_requests', 'swap_resources'];
    foreach ($tables as $table) {
        echo "<h5>$table:</h5>";
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5' style='margin-bottom: 20px;'>";
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
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr><h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Create teacher swap interface</li>";
echo "<li>Build swap request form</li>";
echo "<li>Create swap approval system</li>";
echo "<li>Add resource upload functionality</li>";
echo "</ol>";
?>