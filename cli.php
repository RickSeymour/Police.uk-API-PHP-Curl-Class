#!/usr/bin/env php
<?php
ini_set("display_errors",true);
require_once(__DIR__."/police.php");
require_once(__DIR__."/police.db.php");

if(count($argv)<=1){
    die("Enter <API CALL> <OPTIONS>".PHP_EOL);
}

array_shift($argv); //script name
$call=array_shift($argv);

if(!method_exists($POLICE,$call)){
    echo "API Call does not exist";
    echo PHP_EOL;
    exit();
}

// Use returnraw to prevent json decoding
//$POLICE->returnraw=true;

echo call_user_func_array(array($POLICE,$call),$argv);
