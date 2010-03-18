<?php
define('SCRIPTFILE',__FILE__);
define('APP','app');
define('APP_PATH',realpath(dirname(__FILE__) . '/' . APP));

require "../system/core/core.php";

$app = new Application();
$app->setDefault('welcome','index');
$app->display();