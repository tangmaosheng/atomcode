# 如何入门 #

要使用AtomCode进行开发，请先跟随教程开发出第一个简单程序，在此过程中对框架的工作原理进行理解。

如果您有 CodeIgniter 的开发经验，可以直接跳过本节，然后进入[控制器](Controller.md)等节进行对比此框架和 CodeIgniter 的区别。


# 基本环境 #

## 检出代码 ##

在我们进行程序开发前，您需要首先使用 SVN 工具，将代码检出：
```shell

svn checkout http://atomcode.googlecode.com/svn/trunk/ atomcode```

进入 atomcode 目录后，您将会看到2个目录和1个文件：

![https://lh5.googleusercontent.com/-7s6ZpEiapNQ/T6aTSe_yHYI/AAAAAAAAAG0/ZKeet7QjB3I/s0-d/1.png](https://lh5.googleusercontent.com/-7s6ZpEiapNQ/T6aTSe_yHYI/AAAAAAAAAG0/ZKeet7QjB3I/s0-d/1.png)

AtomCode 框架的内容就存入在 system 目录中，而您即将写的程序将全部位于 application 中。用户访问所有需要PHP执行的内容都需要通过 index.php

## 目录含义 ##

```
application/
   block # 块
   cache # 缓存目录，请给 0777 权限
   config #配置文件
   controller # 控制器文件
   helper # 辅助类
   language # 语言目录
   library # 用户及第三方类库
   log # 日志目录，请给 0777 权限
   model # 模型
   view # 模板文件，即视图
```

## 初始配置 ##

请参考：[如何安装](Installation.md)

## Hello World ##

创建一个文件：

application/controller/IndexController.php

输入以下代码：

```
<?php

class IndexController extends Controller {
	public function index() {
		echo "Hello world!";
	}
}
```

现在你可以访问 http://localhost/atomcode/

是不是已经有内容输出？

# 第一个真正的程序 #

相信上面的 **Hello world** 只能证明框架可以运行了，但是你却仍然不知道如何开发。因为没有数据库也没有用到视图，也没有一个可以证明有逻辑的地方说明这个程序受了你的控制。

下面我们来实现一个简单的文章列表和查看页面来学习如何使用 AtomCode 进行开发。

## URL设计 ##

列表页：http://localhost/atomcode/article/lists

文章页：http://localhost/atomcode/article/view?id=1

## 创建数据表 ##

```
create database atomcode;
use atomcode;

create table article (
id int not null auto_increment,
title varchar(20) not null,
content varchar(3000) not null,
index id(id)) Engine=MyIsam;

insert article values (1, "first", "first content"), (2, "second", "this is another article for testing");
```

## 修改数据库配置信息 ##

1. 复制 system/config/database.php 到 application/config/database.php

2. 修改配置为实际配置：

```
$config['database']['type'] = 'mysql';
$config['database']['host'] = 'localhost';
$config['database']['port'] = '';
$config['database']['user'] = 'root';
$config['database']['pass'] = '';
$config['database']['name'] = 'atomcode';
```

## 建立模型 ##

application/model/ArticleModel.php

代码如下：

```
<?php
class ArticleModel extends Model {
	/**
	 * @return ArticleModel
	 */
	public static function & instance() {
		return parent::getInstance(__CLASS__);
	}
	
	public function getList() {
		return $this->get();
	}

	public function getArticle($id) {
		$this->where('id', $id);
		return $this->getOne();
	}
}
```

## 建立Block ##

本项目较为简单，Block这一步可以跳过直接通过控制器进行调用。但是为了演示Block在整框架运行中扮演的角色，所以我们还是建立了一个Block。

application/block/ArticleBlock.php

代码如下：

```
<?php

class ArticleBlock {

	private static $instance;

	/**
	 * 
	 * @return ArticleBlock
	 */
	public static function &instance() {
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}

	public function all() {
		return ArticleModel::instance()->getList();
	}

	public function view($id) {
		return ArticleModel::instance()->getArticle($id);
	}
}
```


## 建立控制器 ##


application/controller/ArticleController.php

代码如下：

```
<?php

class ArticleController extends Controller {
        public function lists() {
                $this->_assign('list', ArticleBlock::instance()->all());
				return $this->_display('list');
        }


		public function view() {
			$id = intval($_GET['id']);
			$article = ArticleBlock::instance()->view($id);
			if (!$article) {
				show_404();
			}
			$this->_assign('article', $article);
			return $this->_display('detail');
		}
}
```

## 建立视图 ##

application/view/default/list.tpl

代码如下：

```
<ul>
{foreach $list $item}
	<li><a href="{url url="article/view?id=$item.id"}">{$item.title}</a></li>
{/foreach}
</ul>
```

application/view/default/detail.tpl

代码如下：

```
<h1>{$article.title}</h1>
<p>{$article.content}</p>
```

## Bingo ##

现在你访问： http://localhost/atomcode/article/lists 然后点击其中的链接，你会发现这个小程序已经完成了。

怎么样？就算没有其他的教程，是不是仍然可以轻松写出程序来？相信你，根据上述示例你可以学到简单的数据库访问方法、调用模型和块的方法、URL与控制器的关系、视图与控制器交互数据的方法。有了这些，做出更复杂的程序也不会太难了。