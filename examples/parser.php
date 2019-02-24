<?php
require_once __DIR__."/../src/PHPErrorLog.php";

$parser = new PHPErrorLogParser\PHPErrorLog();
$parser->loadFile(__DIR__."/error.log");
$parser->render();