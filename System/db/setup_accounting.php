<?php
try {
    // Create or connect to SQLite database
    $db = new SQLite3(__DIR__ . '/daycare.db');
    $db->enableExceptions(true);
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/accounting.sql');
    $db->exec($sql);
    
    echo "<h2>Success!</h2>";
    echo "<p>Accounting tables have been created successfully.</p>";
    echo "<p><a href='../accounting/index.php'>Go to Accounting Module</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<p>Error creating accounting tables: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>