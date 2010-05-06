<?php

$config['time_zone'] = 'PRC'; # like Etc/GMT-8 see http://cn2.php.net/manual/en/res/timezones.others.html
$config['CHARSET'] = 'utf-8';
$config['MODEL_PREFIX'] = ''; #model prefix
$config['MODEL_SUFFIX'] = 'Model'; #model suffix, for example, Combined as: UserModel
$config['CONTAINER_PREFIX'] = ''; #container prefix
$config['CONTAINER_SUFFIX'] = ''; #container suffix

$config['CONTROLLER_CLASS_PREFIX'] = ''; #controller prefix
$config['CONTROLLER_CLASS_SUFFIX'] = ''; #controller suffix

$config['COMPILE']['URL_REFRESH_PARAM'] = 'refresh'; # disabled if null
$config['COMPILE']['URL_DEBUG_PARAM'] = '';	# disabled if null
$config['COMPILE']['AUTO_CHECK'] = true;