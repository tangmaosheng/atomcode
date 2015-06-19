# 框架机制 #

经过上一节的一个简单教程，你可能靠猜差不多猜到框架是怎么加载你的程序的，又是怎么把结果给输出的。好吧，差不多你都猜对了，但框架的能力当然不仅限于此，请跟随本章进一步了解 AtomCode。

## 框架运行过程 ##

  1. 执行开始会首先访问入口文件，一般会是： index.php ，入口文件在定义了常量后即开始加载框架内核文件。
  1. 框架会根据URL自动判断要加载哪一个控制器，并预先指定语言和样式目录。
  1. 在控制器执行时，根据需要再加载 Block、模型、助手程序、用户类库等。控制器执行结束，则将数据返回给框架。
  1. 框架再根据需要调用渲染引擎，以决定是输出HTML还是JSON等格式。至此，整个程序运行结束。

综上所述，你要做的就是在控制器中调用一切你需要的资源即可。框架会通过下面的机制帮助你加载它们。

## 类加载机制 ##

使用 AtomCode 开发的过程中，你不需要再写 include 或者 require ，只要遵守命名和文件存放规范，框架就会自动帮你按需加载类。

### 文件与类命名规范 ###

所有的目录名需要小写。类名需要与文件名一致。例如：

`application/controller/IndexController.php` 中存放的类一定是： `class IndexController`

如果要使用命名空间，则需要将文件放在同名的目录中。 **控制器类** 除外。例如，你需要定义:

```
namespace \samples\demo;

class Image {
}
```

则此类存放位置应是： application/library/samples/demo/Image.php

为了编程风格一致，请务必命名类时各单词首字母大写( **控制器类** 除外，控制器类只需要首字母大写+Controller即可)。

```
注意：模型的命名空间和目录都有特殊含义，不要随便命名！
```

```
注意：控制器只能放在根命名空间内！
```

## 配置规则 ##

每个开发人员的配置文件请自己维护。并将新添加的配置项反应到生产环境配置中。

主配置文件为： `config.php`，存放位置：`application/config/config.php` 任何主要配置和常量、全局配置都请写在此配置中。

> 上述假定了 ENV 的值为空。
> 如果 ENV 的值为 abc，则还要多一个配置路径为： application/config/abc/config.php。因为ENV不同，要加载的配置目录也不同。

独立配置文件，比如： `database.php`，存放位置：`application/config/database.php`，
要加载独立配置文件，则通过以下方式：

database.php 文件内容：
```
<?php

$config['database']['host'] = "localhost";
$config['database']['port'] = 3306;
```

调用代码
```
<?php
// $config = array(
//    'host' => "localhost",
//    'port' => 3306);
$config = load_config("database");
```