<?php
$archiver_config = array();
// -----------------------------------------------------------
// FOLDER CONFIG
// e.g. if your script is at \4arch\ these should be set to \4arch\data\
// -----------------------------------------------------------

// where to store files, this folder should probably get made by you with 777 perms
$archiver_config[ 'storage' ] = "C:\\xampp\\htdocs\\4arch\\data\\";

// the publicly accessible link to the file store
$archiver_config[ 'pubstorage' ] = "http://***.***.***.***/4arch/data/";

// -----------------------------------------------------------
// MYSQL CONFIG
// self explanatory
// -----------------------------------------------------------

$archiver_config[ 'mysql_host' ] = "localhost";
$archiver_config[ 'mysql_user' ] = "root";
$archiver_config[ 'mysql_pass' ] = "loljkstrongpasswordsarefortripfags";
$archiver_config[ 'mysql_db' ]   = "CloudArchiver";

// -----------------------------------------------------------
// ACCESS CONTROL
// if all these are false login is disabled
// -----------------------------------------------------------

// if this is set to true you need to login to manually check threads
$archiver_config[ 'login_chk' ] = true;

// if this is set to true you need to login to add threads
$archiver_config[ 'login_add' ] = true;

// if this is set to true you need to login to delete or change description of threads
$archiver_config[ 'login_del' ] = true;

// is registration enabled?
$archiver_config[ 'register_enabled' ] = true;

// can you only remove and check your own threads?
$archiver_config[ 'restrict_actions' ] = true;

// -----------------------------------------------------------
// ADVANCED STUFF
// you should probably leave this alone
// -----------------------------------------------------------

$archiver_config[ 'updater_enabled' ] = false; //NOTE: This is broken. Do not enable.
$archiver_config[ 'login_enabled' ]   = $archiver_config[ 'login_del' ] || $archiver_config[ 'login_add' ] || $archiver_config[ 'login_chk' ];
?>