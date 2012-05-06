#!/usr/bin/php
<?php
require_once '../classes/Spawnd.php';
$spawnd = new Spawnd();
$spawnd->addProcess( 'du', array( 'cmd' => 'du -h /' ) );
$spawnd->run();
