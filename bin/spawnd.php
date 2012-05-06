#!/usr/bin/php
<?php
require_once '../classes/Spawnd.php';

$command1 = new StdClass;
$command1->cmd = 'du -h /';

$procs = array();
$procs[] = $command1;


$spawnd = new Spawnd();
$spawnd->run( $procs );
