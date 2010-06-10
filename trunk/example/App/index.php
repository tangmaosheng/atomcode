<?php
define('APP_PATH', 'E:/PHPProject/Erp');
require "E:/PHPProject/AtomCode/core/~atomcode.php";

$app = new Application();
$app->setDefault('welcome','index');
$app->display();