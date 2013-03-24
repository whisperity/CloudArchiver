<?php
header("Content-type: text/html; charset=utf-8");

require_once 'config.php';
require_once 'chan_archiver.php';

$database = new mysqli($archiver_config['mysql_host'], $archiver_config['mysql_user'], $archiver_config['mysql_pass'], $archiver_config['mysql_db']);

$t = new chan_archiver();
$t->injectDatabase($database);