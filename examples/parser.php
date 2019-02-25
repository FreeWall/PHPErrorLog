<?php
require_once __DIR__."/../src/PHPErrorLog.php";

$errorLog = new PHPErrorLog\PHPErrorLog("D:\\Backups");
$errorLog->loadFromFile(__DIR__."/error.log");
$errorLog->render();