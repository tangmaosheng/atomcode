<?php
#常规设置
$config['log'] = 0;

$config['disguise'] = false; #URL 伪装
$config['uri']['scheme'] = '';
$config['uri']['query']['controller'] = 'c';
$config['uri']['query']['method'] = 'm';

$config['gzip'] = false;

#数据库部分
$config['DATA_RESULT_TYPE'] = 0; // 默认数据返回格式 1 对象 0 数组
$config['TABLE_NAME_IDENTIFY'] = true;
$config['DB'] =   array(
	'TYPE'=>'mysql',
	'HOST'=>'localhost',
	'NAME'=>'',
	'USER'=>'root',
	'PWD'=>'',
	'PORT'=>'3306',
	'PREFIX'=>'',
	'CHARSET' => 'utf8',
	'DEPLOY_TYPE'			=>	0			// 数据库部署方式 0 集中式（单一服务器） 1 分布式（主从服务器）
);

$config['SESSION'] = array(
	/* SESSION设置 */
	'NAME'				=>	'AcID',		// 默认Session_name 如果需要不同项目共享SESSION 可以设置相同
	'PATH'				=>	'',			// 采用默认的Session save path
	'TYPE'				=>	'File',		// 默认Session类型 支持 DB 和 File
	'EXPIRE'			=>	'300000',	// 默认Session有效期
	'TABLE'				=>	'ac_session',// 数据库Session方式表名
	'CALLBACK'			=>	''			// 反序列化对象的回调方法
);

$config['CACHE'] = array(
	/* 数据缓存设置 */
	'TIME'					=>	-1,			// 数据缓存有效期
	'COMPRESS'				=>	false,		// 数据缓存是否压缩缓存
	'CHECK'					=>	false,		// 数据缓存是否校验缓存
	'TYPE'					=>	'File',		// 数据缓存类型 支持 File Db Apc Memcache Shmop Sqlite Xcache Apachenote Eaccelerator
	'SUBDIR'				=>	false,		// 使用子目录缓存 （自动根据缓存标识的哈希创建子目录）
	'TABLE'					=>	'ac_cache',	// 数据缓存表 当使用数据库缓存方式时有效
	'CACHE_SERIAL_HEADER'	=>	"<?php\n//",// 文件缓存开始标记
	'CACHE_SERIAL_FOOTER'	=>	"\n?".">",	// 文件缓存结束标记
	'SHARE_MEM_SIZE'		=>	1048576,	// 共享内存分配大小
	'ACTION_CACHE_ON'		=>	false		// 默认关闭Action 缓存
);

$config['COOKIE'] = array(
	/* Cookie设置 */
	'EXPIRE'	=>	3600,	// Coodie有效期
	'DOMAIN'	=>	'',		// Cookie有效域名
	'PATH'		=>	'/',	// Cookie路径
	'PREFIX'	=>	'', 	// Cookie前缀 避免冲突
	//'SECRET_KEY'=>  'OsIlyCyq' 	// Cookie 加密Key
	'SECRET_KEY'=>  '' 	// Cookie 加密Key
);

$config['gacl'] = array(
"debug" 			=> false, //调试模式

//Database,此处可独立设置,也可以与上面设定相同

"db_type" 		=> "mysql",
"db_host"			=> "localhost",
"db_user"			=> "root",
"db_password"		=> "ec",
"db_name"			=> "gacl",
"db_table_prefix"		=> "",


//Caching

"caching"			=> FALSE,
"force_cache_expire"	=> TRUE,
"cache_dir"		=> "phpgacl",
"cache_expire_time"	=> 600,

//Admin interface
"items_per_page" 		=> 100,
"max_select_box_items" 	=> 100,
"max_search_return_items" => 200
);