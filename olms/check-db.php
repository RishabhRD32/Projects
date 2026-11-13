<?php

echo "<h2>OLMS PHP Environment Check</h2>";

echo "PHP Version: " . PHP_VERSION . "<br>";

echo "PDO Enabled: ";
var_dump(extension_loaded('pdo'));

echo "<br>PDO MySQL Enabled: ";
var_dump(extension_loaded('pdo_mysql'));

echo "<br>Available PDO Drivers: ";
echo implode(', ', PDO::getAvailableDrivers());

echo "<br><br>Port 3307: ";

$connection = @fsockopen(
    '127.0.0.1',
    3307,
    $errno,
    $errstr,
    3
);

if ($connection) {
    echo "AVAILABLE";
    fclose($connection);
} else {
    echo "FAILED: " . htmlspecialchars($errstr);
}