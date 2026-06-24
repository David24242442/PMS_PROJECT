<?php
echo "Loaded php.ini: " . php_ini_loaded_file() . "\n";
echo "Extension Dir: " . ini_get('extension_dir') . "\n";
echo "PDO Drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n";
echo "pdo_mysql loaded: " . (extension_loaded('pdo_mysql') ? 'Yes' : 'No') . "\n";
echo "openssl loaded: " . (extension_loaded('openssl') ? 'Yes' : 'No') . "\n";
