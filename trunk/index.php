<?php
define('SCRIPTFILE',__FILE__);
define('APP','app');
require "system/core/core.php";

$app = new Application();
$app->Cache = false;
$app->set_default('index');
$app->display();
