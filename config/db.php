<?php
// config/db.php
$host = 'localhost';
$dbname = 'crm_db';
$username = 'root';
$password = ''; // Default password for WAMP is empty

try {
    // 1. Create the connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // 2. Set error mode to "Exception" (shows real error messages)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 3. Set default fetch mode to "Associative Array" (easier to use data)
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // If connection fails, stop and show error
    die("❌ Database Connection Failed: " . $e->getMessage());
}
?>