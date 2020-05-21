##### init.php 初始化框架的各项配置

##### autoload.php 框架的自动加载组件

##### index.php 作为mvc的框架入口

##### App.php 初始化生成容器实例化对象

##### .htaccess 重写规则配置

#### config 目录是配置变量 和 服务组件的地方

#### ueditor 目录是百度富文本编辑器，这里添加了模块和权限限制

#### user 目录是处理用户以及各种身份权限管理的模块

#### vendor 是各种组件的使用

#### script php指定运行的定时脚本

#### app 是授权第三方登录的接口

#### upload 是上传文件存储的

#### posts是新闻管理目前只放这一个模块如果有不同业务，建议模块化

#### mvc.sql 是数据库基础
 

#### 指定登录方登录接口  127.0.0.1/mvc/index.php/site/login  
```
{
	"school_alias":"mvc",
	"username": "whldk",
	"password":"123456",
	"remember":1
}
```

