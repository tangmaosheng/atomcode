# 服务器运行要求 #

**PHP 5.3**

需要 PHP 5.3 以上版本，最好是 5.3.10或者更高。因为之前PHP漏洞，最好可以升级到最新版本。

**Mysql**

Mysql版本不限。如果使用查询参数，例如 Insert Ignore， 则请使用相应版本或者更高版本的数据库。

_当前框架的数据库驱动程序并不完整，如果要用数据库则请使用Mysql_

**Memcached**

如果希望使用这个缓存技术的话，可以安装这个服务器和PHP相应模块，可以实现缓存和Session存储。

**Web 服务器**

如果您用此框架实现一个Web框架，则需要 Nginx、Apache或者其他Web服务器，此框架可以运行在CGI模式或者Apache模块模式下。

其他必要程序则根据需要决定。