<?php
require_once('../../config.php');
require_login();

echo "<h1>Database Test</h1>";

echo "<h2>Method 1: Direct Query Test</h2>";
// Test by directly querying the tables
$tables = ['local_myplugin_sessions', 'local_myplugin_alerts', 'local_myplugin_screenshots'];

foreach ($tables as $table) {
    try {
        $count = $DB->count_records($table);
        echo "<p>✅ Table '$table': EXISTS (has $count records)</p>";
    } catch (Exception $e) {
        echo "<p>❌ Table '$table': ERROR - " . $e->getMessage() . "</p>";
    }
}

echo "<hr><h2>Method 2: Table Manager Check</h2>";
foreach ($tables as $table) {
    try {
        $exists = $DB->get_manager()->table_exists($table);
        echo "<p>Table '$table': " . ($exists ? '✅ EXISTS' : '❌ NOT FOUND') . "</p>";
    } catch (Exception $e) {
        echo "<p>Table '$table': ❌ ERROR - " . $e->getMessage() . "</p>";
    }
}

echo "<hr><h2>Method 3: Raw SQL Check</h2>";
try {
    $prefix = $CFG->prefix;
    echo "<p>Table prefix: <strong>$prefix</strong></p>";
    
    $sql = "SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name LIKE '{$prefix}local_myplugin%'";
    
    $result = $DB->get_records_sql($sql);
    
    if (!empty($result)) {
        echo "<p>✅ Found " . count($result) . " tables in database:</p><ul>";
        foreach ($result as $row) {
            echo "<li>" . $row->table_name . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>❌ No tables found</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ ERROR: " . $e->getMessage() . "</p>";
}

echo "<hr><h2>Database Connection Info</h2>";
echo "<p>Database type: " . $CFG->dbtype . "</p>";
echo "<p>Database name: " . $CFG->dbname . "</p>";
echo "<p>Database host: " . $CFG->dbhost . "</p>";
echo "<p>Database prefix: " . $CFG->prefix . "</p>";