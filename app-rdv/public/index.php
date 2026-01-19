<?php
declare(strict_types=1);

// ini_set('display_errors', '1');
// ini_set('log_errors', '1');
// ini_set('error_log', __DIR__ . '/../var/logs/php_errors.log');
// error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

/* application boostrap */
$appli = require_once __DIR__ . '/../config/bootstrap.php';

$appli->run();
