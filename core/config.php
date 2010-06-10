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

$config['CONTAINER_PREFIX'] = 'Ctn'; #container prefix
$config['CONTAINER_SUFFIX'] = ''; #container suffix

$config['CONTROLLER_CLASS_PREFIX'] = 'Ctrl'; #controller prefix
$config['CONTROLLER_CLASS_SUFFIX'] = ''; #controller suffix
# compiler config
$config['COMPILE']['URL_REFRESH_PARAM'] = 'refresh'; # disabled if null
$config['COMPILE']['URL_DEBUG_PARAM'] = '';	# disabled if null
$config['COMPILE']['AUTO_CHECK'] = true;
$config['VIEW_EXT'] = '.html';
/*
The following settings will affect the performance of your web server lightly
ROUTER MODE:
0: Get parameter from query string, URL like: ?a=a&b=b&c=d or rewrite /a/b/c/d to ?a=a&b=b&c=d
1: static url, Use 404 page, URL like: /a/b/c/d.html or /a/b/c/d. Dangerous!!! Donot recommend!!!!!
2: static and query, URL like: /a/b?c=d.html, and you have rewrote this to index.php/a/b?c=d, and make sure your 
   server support it.
3: query string as a path info, URL like: index.php?/a/b/c/d or index.php/a/b/c/d
ROUTER STATIC:
you can enable static page when ROUTER MODE is 1, 2, 3, Notice that the '?' will be transform to '-', '?' can
be included only once.
STATIC EXT:
same to ROUTER STATIC
ROUTER PATTERN:
only be used when mode = 1, 2, 3

 */
$config['ROUTER']['MODE'] = 2;
$config['ROUTER']['CONTROLLER'] = 'a';
$config['ROUTER']['METHOD'] = 'b';
$config['ROUTER']['PATTERN'] = array(
# Example regular => array(replacement, is_static)
#'^/(\w+)/(\w+)(\?(.*))?$' => array('a=$1&b=$2& $4', 0),
'|^news/list/([a-z]+)(-page-(\d+))?\.html|' => array('a=news&b=list&area=$1&page=$3', 1)
);
$config['ROUTER']['STATIC'] = true;
$config['STATIC']['EXT'] = '.html';
$config['LOG_TYPE']['DEBUG'] = 'ERROR|DEBUG|SQL';
$config['LOG_TYPE']['RUN'] = 'ERROR|DEBUG|SQL';
