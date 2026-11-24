
<?php

header("Content-Type: text/plain; charset=UTF-8");

error_reporting(E_ALL);
ini_set("display_errors", "1");

echo "PHP VERSION: " . PHP_VERSION . "\n";

echo "PDO: ";

if (extension_loaded("pdo")) {
    echo "AVAILABLE\n";
} else {
    echo "NOT AVAILABLE\n";
}

echo "PDO MySQL: ";

if (extension_loaded("pdo_mysql")) {
    echo "AVAILABLE\n";
} else {
    echo "NOT AVAILABLE\n";
}

echo "MySQLi: ";

if (extension_loaded("mysqli")) {
    echo "AVAILABLE\n";
} else {
    echo "NOT AVAILABLE\n";
}

?>
