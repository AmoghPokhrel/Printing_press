<?php
require_once __DIR__ . '/includes/dbcon.php';

try {
    // Test connection
    $pdo->query("SELECT 1");
    echo "Database connection successful!\n";

    // Test template_modifications table access
    $stmt = $pdo->query("SHOW TABLES LIKE 'template_modifications'");
    if ($stmt->rowCount() > 0) {
        echo "template_modifications table exists!\n";

        // Test update permission
        $stmt = $pdo->prepare("SELECT id FROM template_modifications LIMIT 1");
        $stmt->execute();
        if ($row = $stmt->fetch()) {
            $testId = $row['id'];
            $currentValue = null;

            // Get current value
            $stmt = $pdo->prepare("SELECT feedback FROM template_modifications WHERE id = ?");
            $stmt->execute([$testId]);
            if ($row = $stmt->fetch()) {
                $currentValue = $row['feedback'];
                echo "Current feedback exists for test record\n";
            }

            // Try update
            $stmt = $pdo->prepare("UPDATE template_modifications SET feedback = ? WHERE id = ?");
            $testFeedback = "Test feedback - " . date('Y-m-d H:i:s');
            $stmt->execute([$testFeedback, $testId]);
            echo "Update permission test successful!\n";

            // Revert the change
            $stmt = $pdo->prepare("UPDATE template_modifications SET feedback = ? WHERE id = ?");
            $stmt->execute([$currentValue, $testId]);
        } else {
            echo "No records found in template_modifications table\n";
        }
    } else {
        echo "template_modifications table does not exist!\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}