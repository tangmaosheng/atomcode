<?php  if ( ! defined('BASE_PATH')) exit('No direct script access allowed');

$config['cache'] = array();
$config['cache']['drivers'] = array('file', 'db', 'memcache');
$config['cache']['default'] = 'file';

$config['cache']['db']['name'] = '';
$config['cache']['db']['host'] = '';
$config['cache']['db']['user'] = '';
$config['cache']['db']['pass'] = '';

$config['cache']['file']['dir'] = APP_PATH . '/cache/';

$config['cache']['memcache']['server'] = '';
$config['cache']['memcache']['port'] = '';