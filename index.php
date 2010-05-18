<?php
define('APP_PATH',realpath(dirname(__FILE__) . '/' . APP));
require "../../projects/system/atomcode/core/core.php";
$app = new Application();
$app->setDefault('welcome','index');
$app->display();