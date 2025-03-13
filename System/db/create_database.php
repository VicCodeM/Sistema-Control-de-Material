<?php
// This script will create the database file and initialize the tables
// Run this script manually from the browser: http://localhost/GUARDERIA/db/create_database.php

echo "<h1>Database Initialization</h1>";

try {
    // Check if SQLite3 extension is loaded
    if (!class_exists('SQLite3')) {
        throw new Exception("SQLite3 extension is not enabled in your PHP installation.");
    }
    
    // Create or connect to SQLite database
    $db_path = __DIR__ . '/daycare.db';
    $db = new SQLite3($db_path);
    
    echo "<p>Successfully connected to SQLite database.</p>";
    echo "<p>Database file created at: " . $db_path . "</p>";
    
    // Create Users table with different access levels
    $db->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT NOT NULL,
        name TEXT NOT NULL,
        active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    echo "<p>Users table created.</p>";

    // Create Children table
    $db->exec('CREATE TABLE IF NOT EXISTS children (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        first_name TEXT NOT NULL,
        last_name TEXT NOT NULL,
        birth_date DATE NOT NULL,
        enrollment_date DATE NOT NULL,
        allergies TEXT,
        special_notes TEXT,
        status TEXT DEFAULT "active",
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    echo "<p>Children table created.</p>";

    // Create Guardians table
    $db->exec('CREATE TABLE IF NOT EXISTS guardians (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        first_name TEXT NOT NULL,
        last_name TEXT NOT NULL,
        relationship TEXT NOT NULL,
        phone TEXT NOT NULL,
        email TEXT,
        address TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    echo "<p>Guardians table created.</p>";

    // Create Child-Guardian relationship table
    $db->exec('CREATE TABLE IF NOT EXISTS child_guardian (
        child_id INTEGER,
        guardian_id INTEGER,
        is_primary BOOLEAN DEFAULT 0,
        FOREIGN KEY (child_id) REFERENCES children(id),
        FOREIGN KEY (guardian_id) REFERENCES guardians(id),
        PRIMARY KEY (child_id, guardian_id)
    )');
    echo "<p>Child-Guardian relationship table created.</p>";

    // Create Attendance table for children
    $db->exec('CREATE TABLE IF NOT EXISTS child_attendance (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        child_id INTEGER,
        check_in DATETIME NOT NULL,
        check_out DATETIME,
        notes TEXT,
        FOREIGN KEY (child_id) REFERENCES children(id)
    )');
    echo "<p>Child Attendance table created.</p>";

    // Create Staff Attendance table
    $db->exec('CREATE TABLE IF NOT EXISTS staff_attendance (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        check_in DATETIME NOT NULL,
        check_out DATETIME,
        notes TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )');
    echo "<p>Staff Attendance table created.</p>";

    // Create Payments table
    $db->exec('CREATE TABLE IF NOT EXISTS payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        child_id INTEGER,
        amount DECIMAL(10,2) NOT NULL,
        payment_date DATE NOT NULL,
        week_start DATE NOT NULL,
        week_end DATE NOT NULL,
        payment_status TEXT NOT NULL,
        payment_method TEXT NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (child_id) REFERENCES children(id)
    )');
    echo "<p>Payments table created.</p>";

    // Create Health Incidents table
    $db->exec('CREATE TABLE IF NOT EXISTS health_incidents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        child_id INTEGER,
        incident_date DATETIME NOT NULL,
        incident_type TEXT NOT NULL,
        description TEXT NOT NULL,
        action_taken TEXT NOT NULL,
        reported_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (child_id) REFERENCES children(id),
        FOREIGN KEY (reported_by) REFERENCES users(id)
    )');
    echo "<p>Health Incidents table created.</p>";

    // Insert default admin user
    $default_password = password_hash("admin123", PASSWORD_DEFAULT);
    $db->exec("INSERT OR IGNORE INTO users (username, password, role, name) 
               VALUES ('admin', '$default_password', 'admin', 'Administrator')");
    echo "<p>Default admin user created.</p>";
    echo "<p>Username: admin<br>Password: admin123</p>";

    echo "<h2>Database initialized successfully!</h2>";
    echo "<p>You can now <a href='../index.php'>login to the system</a>.</p>";

} catch (Exception $e) {
    echo "<h2>Error initializing database:</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<h3>Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Make sure the SQLite3 extension is enabled in your PHP installation.</li>";
    echo "<li>Make sure the 'db' directory has write permissions.</li>";
    echo "<li>Check if the database file already exists and is not corrupted.</li>";
    echo "</ul>";
}
?>