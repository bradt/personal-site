<?php
header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
require '../../../wp-config.php';
require TEMPLATEPATH.'/404.php';
