<?php  if ( ! defined('BASE_PATH')) exit('No direct script access allowed');
/**
 * AtomCode
 *
 * A open source application,welcome to join us to develop it.
 *
 * @package		AtomCode
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/doc/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource
 */
$config['database']['log'] = TRUE;

/*
 * 允许同时使用多个数据库，而每个数据库有自己的配置。
 */
$config['database']['multiple'] = FALSE;
/*
 * 指定默认数据库连接，仅在支持多库时有效
 */
$config['database']['default'] = '';

/*
 * 多数据库模式：none, master/slave,
 */
// 第一个数据库
$config['database']['mode'] = 'none';

$config['database']['type'] = 'mysql';
$config['database']['host'] = '192.168.213.16';
$config['database']['port'] = '';
$config['database']['user'] = 'zjs_doc';
$config['database']['pass'] = 'root';
$config['database']['name'] = '';
$config['database']['charset'] = 'utf8';
$config['database']['table_prefix'] = '';
$config['database']['pconnect'] = TRUE;
$config['database']['cache'] = TRUE;
$config['database']['db_debug'] = FALSE;
/*
// 第一个数据库的第一个主库
$i = 0;
$config['database']['master'][$i]['type'] = 'mysql';
$config['database']['master'][$i]['host'] = 'localhost';
$config['database']['master'][$i]['port'] = '';
$config['database']['master'][$i]['user'] = 'root';
$config['database']['master'][$i]['pass'] = '';
$config['database']['master'][$i]['name'] = 'atomcode';
$config['database']['master'][$i]['charset'] = 'utf8';
$config['database']['master'][$i]['table_prefix'] = '';
$config['database']['master'][$i]['pconnect'] = TRUE;
$config['database']['master'][$i]['cache'] = TRUE;

// 第一个数据库的第一个从库
$i = 0;
$config['database']['slave'][$i]['type'] = 'mysql';
$config['database']['slave'][$i]['host'] = 'localhost';
$config['database']['slave'][$i]['port'] = '';
$config['database']['slave'][$i]['user'] = 'root';
$config['database']['slave'][$i]['pass'] = '';
$config['database']['slave'][$i]['name'] = 'atomcode';


// 以下是多库情况配置
$config['database']['multiple'] = TRUE;
$key = 'first';
// 第一个数据库
$config['database'][$key]['mode'] = 'master/slave';
// 第一个数据库的第一个主库
$i = 0;
$config['database'][$key]['master'][$i]['type'] = 'mysql';
$config['database'][$key]['master'][$i]['host'] = 'localhost';
$config['database'][$key]['master'][$i]['port'] = '';
$config['database'][$key]['master'][$i]['user'] = 'root';
$config['database'][$key]['master'][$i]['pass'] = '';
$config['database'][$key]['master'][$i]['name'] = 'atomcode';
// 第一个数据库的第一个从库
$i = 0;
$config['database'][$key]['slave'][$i]['type'] = 'mysql';
$config['database'][$key]['slave'][$i]['host'] = 'localhost';
$config['database'][$key]['slave'][$i]['port'] = '';
$config['database'][$key]['slave'][$i]['user'] = 'root';
$config['database'][$key]['slave'][$i]['pass'] = '';
$config['database'][$key]['slave'][$i]['name'] = 'atomcode';
*/