# 配置选项 #

## 入口文件配置(index.php) ##

```
<?php
/**
 * 
 * 是否测试模式，控制错误输出和允许调试、测试、记录日志, 不定义则表示 TEST_MODEL 为 FALSE
 * @var Boolean
 */

define("TEST_MODEL", TRUE);
/**
 * 固定配置：文件本身 *必须
 * @var String
 */
define("SELF", __FILE__);
/**
 * 固定配置：应用的路径 *必须
 * @var String
 */
define("DOC_ROOT", dirname(__FILE__));
define("APP_PATH", realpath(dirname(__FILE__) . "/application"));
/**
 * 定义环境，如果不定义则使用 /application/config/目录下配置，否则使用 /application/config/ENV/目录下配置
 * @var String
 */
define('ENV', '');
/**
 * 定义渲染引擎，如果不定义则默认为Html, 否则使用相应的引擎进行渲染输出
 * 允许的值请参考文档
 * @var String
 */
define('RENDER', 'Html');

require 'system/atomcode.php';
```

注释：
```
- 如果修改了应用目录，则需要修改 APP_PATH 配置
- ENV 常量表示了配置目录，提交SVN代码时，请保证此项为生产环境配置
- 不同的入口，可以默认使用不同的渲染器，即可以指定 RENDER 的值
```

## 主配置文件(application/config/ENV/config.php) ##

**URL设置**

```
$config['base_url']	= 'http://www.example.com/';
$config['index_page'] = 'index.php?';
```

这两项会影响 URL 的生成，比如要生成 welcome/index 这个URL，并附加上 id=1 这个查询。以上配置会生成： `http://www.example.com/index.php?/welcome/index&id=1`。

而如果配置修改为：
```
$config['base_url']	= 'http://www.example.com/';
$config['index_page'] = '';
```

会生成： `http://www.example.com/welcome/index?id=1`。

**语言设置**

```
$config['default_language']	= 'zh';
$config['language']	= $config['default_language'];
$config['language_decision'] = '';
$config['language_decision_key'] = '';
```

以上设置决定了如何使用语言。

语言文件存放目录为：
`application/language/$config['language']/`


language\_decision 的可选值有：

| 值 | 含义 |
|:----|:-------|
| cookie | 从Cookie中自动判断语言，在Cookie中的键名由language\_decision\_key指定 |
| session |  从Session中自动判断语言，在Session中的键名由language\_decision\_key指定 |
| segment | 从URL中自动判断语言，由language\_decision\_key指定所在位置，如： URL为 /zh/welcome/index, language\_decision\_key为0, 则语言会被设置为 zh，此选项需要可用语言列表，详细情况请参考配置文件中的说明 |
| query | 从查询中获取语言，也是有language\_decision\_key指定键名 |

**日志设置**

```
$config['log_threshold'] = 1;
```

log记录级别，分为5个级别：
| 0 | 关掉日志 |
|:--|:-------------|
| 1 | 仅记录 Error 级别 |
| 2 | 记录到 Debug 级别 |
| 3 | 记录到 Warning 级别 |
| 4 | 记录到 Notice 级别 |

**Session设置**

```

$config['session']['driver'] = '';

$config['session']['match_ip'] = FALSE;

$config['session']['table'] = '';

$config['session']['expiration'] = 7200;

// memcache configuration
$config['session']['mem_host'] = '';

$config['session']['mem_port'] = '';
```

sess\_driver 可选的值有：session, database, memcache；如果是database 则需要使用 SessionModel ， 并在数据库中建 Session 表：

```
CREATE TABLE IF NOT EXISTS `session` (
  `sessionid` char(32) NOT NULL COMMENT 'Session ID',
  `starttime` int(11) NOT NULL COMMENT '开始时间',
  `lastactivity` int(11) NOT NULL COMMENT '上次活跃时间',
  `data` varchar(5000) default NULL COMMENT 'Session数据',
  `ip` int(11) NOT NULL COMMENT 'IP地址',
  KEY `sessionid` (`sessionid`,`lastactivity`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8 COMMENT='在线状态表';
```

**视图设置**

```
$config['view_path_decision'] = 'query';
$config['view_path_decision_key'] = 'style';
$config['default_view_path'] = 'default';
$config['view_path'] = 'default';
$config['view_ext'] = '.htm';
```

用法与语言相同。配置方法请参见注释。