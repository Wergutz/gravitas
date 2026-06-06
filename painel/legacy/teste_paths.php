<?php
echo '<pre>';
echo __DIR__ . PHP_EOL;
echo file_exists(__DIR__ . '/app/helpers/auth.php') ? 'auth OK' : 'auth NÃO encontrado';
echo PHP_EOL;
echo file_exists(__DIR__ . '/app/config/database.php') ? 'db OK' : 'db NÃO encontrado';
echo '</pre>';

