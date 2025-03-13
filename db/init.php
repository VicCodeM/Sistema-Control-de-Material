<?php

try {
    // Create or connect to SQLite database
    $db = new SQLite3(__DIR__ . '/daycare.db');

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

    // Create Child-Guardian relationship table
    $db->exec('CREATE TABLE IF NOT EXISTS child_guardian (
        child_id INTEGER,
        guardian_id INTEGER,
        is_primary BOOLEAN DEFAULT 0,
        FOREIGN KEY (child_id) REFERENCES children(id),
        FOREIGN KEY (guardian_id) REFERENCES guardians(id),
        PRIMARY KEY (child_id, guardian_id)
    )');

    // Create Attendance table for children
    $db->exec('CREATE TABLE IF NOT EXISTS child_attendance (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        child_id INTEGER,
        check_in DATETIME NOT NULL,
        check_out DATETIME,
        notes TEXT,
        FOREIGN KEY (child_id) REFERENCES children(id)
    )');

    // Create Staff Attendance table
    $db->exec('CREATE TABLE IF NOT EXISTS staff_attendance (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        check_in DATETIME NOT NULL,
        check_out DATETIME,
        notes TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )');

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

    // Insert default admin user
    $default_password = password_hash("admin123", PASSWORD_DEFAULT);
    $db->exec("INSERT OR IGNORE INTO users (username, password, role, name) 
               VALUES ('admin', '$default_password', 'admin', 'Administrator')");

    echo "Database initialized successfully!\n";
    echo "Default admin credentials:\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";

} catch (Exception $e) {
    echo "Error initializing database: " . $e->getMessage() . "\n";
}

?>