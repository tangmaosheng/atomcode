<?php
define('APP_PATH', dirname(__FILE__));

require "../system/core/Core.php";
Core::start();
echo Core::exec_time();