<?php
$mysqli = new mysqli('localhost', 'root', '', 'karma');
$result = $mysqli->query('SELECT id, settings_name, content FROM settings ORDER BY id');
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']}, Name: {$row['settings_name']}\n";
    echo "Content Preview: " . substr($row['content'], 0, 100) . "\n\n";
}
?>
