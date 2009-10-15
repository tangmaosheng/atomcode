<?php
#常规设置
$config['log'] = 0;
$config['time_zone'] = 'PRC'; # like Etc/GMT-8 see http://cn2.php.net/manual/en/res/timezones.others.html

$config['disguise'] = true; #URL 伪装
$config['default_document'] = 'index'; #默认文档
$config['query_start'] = '?';
$config['query_delimeter'] = '&';
$config['CHARSET'] = 'utf-8';

$config['MODEL_CLASS_PREFIX'] = ''; #模型前缀
$config['MODEL_CLASS_SUFFIX'] = 'Model'; #模型后缀 组成以下样子: UserModel

#数据库部分
$config['DATA_RESULT_TYPE'] = 0; // 默认数据返回格式 1 对象 0 数组
$config['TABLE_NAME_IDENTIFY'] = true;
$config['DB'] =   array(
	'TYPE'=>'mysql',
	'HOST'=>'localhost',
	'NAME'=>'test',
	'USER'=>'root',
	'PWD'=>'ec',
	'PORT'=>'3306',
	'PREFIX'=>'ac_',
	'CHARSET' => 'utf-8',
	'DEPLOY_TYPE'			=>	0,			// 数据库部署方式 0 集中式（单一服务器） 1 分布式（主从服务器）
	'FIELDS_CACHE'			=>	true,			// 缓存数据表字段信息
	'SQL_DEBUG_LOG'			=>	false,			// 记录SQL语句到日志文件
    'FIELDS_DEPR'                 =>   ',',   // 多字段查询的分隔符
    'TABLE_DESCRIBE_SQL'     =>   '',             //  取得数据表的字段信息的SQL语句
    /*  下面的数据库配置参数是为Oracle提供 */
    'TRIGGER_PREFIX'	=>	'tr_',   //触发器前缀，其后与表名一致
    'SEQUENCE_PREFIX'	=>	'seq_',  //序列前缀，其后与表名一致
    'CASE_LOWER' =>	true, //隐式参数，ORACLE返回数据集，键名大小写，默认强制为true小写，以适应TP Model类如count方法等
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