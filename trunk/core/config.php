<?php
/**
 * Core configure
 * Used by atomcode,
 * You can modify it, but you CANNOT delete any of these,
 * Of course, override configure in APP_PATH/config.cfg.php is better
 * 
 * If you want to know the exact meaning of each item, please see the manual book 
 */
# base config
$config['time_zone'] = 'PRC'; # like Etc/GMT-8 see http://cn2.php.net/manual/en/res/timezones.others.html
$config['CHARSET'] = 'utf-8';
# prefix config
$config['MODEL_PREFIX'] = ''; #model prefix
$config['MODEL_SUFFIX'] = 'Model'; #model suffix, for example, Combined as: UserModel

$config['CONTAINER_PREFIX'] = 'Tn'; #container prefix
$config['CONTAINER_SUFFIX'] = ''; #container suffix

$config['CONTROLLER_CLASS_PREFIX'] = 'Ctrl'; #controller prefix
$config['CONTROLLER_CLASS_SUFFIX'] = ''; #controller suffix
# compiler config
$config['COMPILE']['URL_REFRESH_PARAM'] = 'refresh'; # disabled if null
$config['COMPILE']['URL_DEBUG_PARAM'] = '';	# disabled if null
$config['COMPILE']['AUTO_CHECK'] = true;
$config['VIEW_EXT'] = '.html';
# url router config.0:default, 1:friendly, 2:complex
# @see manual book
$config['ROUTER']['MODE'] = 0;
$config['ROUTER']['CONTROLLER'] = 'a';
$config['ROUTER']['METHOD'] = 'b';
$config['ROUTER']['PATTERN'] = array(
# Example regular => array(replacement, is_static)
'^/(\w+)/(\w+)(\?(.*))?$' => array('a=$1&b=$2& $4', 0),
'^/news/list/([a-z]+)(-page-(\d+))?\.html' => array('a=news&b=list&area=$1&page=$3', 1)
);