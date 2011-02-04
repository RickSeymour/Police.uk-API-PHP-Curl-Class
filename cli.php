#!/usr/bin/env php
<?php 
ini_set("display_errors",true);
require_once("police.php");

$POLICE->returnraw=true;

if(count($argv)<=1){
    die("Enter <API CALL> <OPTIONS>".PHP_EOL);
}


if(!method_exists($POLICE,$argv[1])){
    echo "API Call does not exist";
    echo PHP_EOL;
    exit();
}


array_shift($argv);
$call=array_shift($argv);

echo call_user_func_array(array($POLICE,$call),$argv);

