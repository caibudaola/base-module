# base-module

础组件
======

## 安装

打开 `composer.json` 找到或创建 `repositories` 键，添加VCS资源库。

```
	// ...
	"repositories": [
		// ...
		{
			"type": "vcs",
			"url": "git@github.com:caibudaola/base-module.git"
		}
	],
	// ...
```

添加依赖包。

```
composer require module/base dev-master
```