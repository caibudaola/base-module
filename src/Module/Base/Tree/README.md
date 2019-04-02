# 树状结构（无线分类）数据库存储实现扩展

## 数据表必须的额外字段

| 字段 | 类型 | 描述 |
| ---- | ---- | ---- |
| parent_id | int | 当前节点对应的父节点ID |
| lft | int | 当前节点左边值|
| rgt |int | 当前节点右边值 |
```$xslt

alter table users add parent_id int(10) default 0 comment '当前节点对应的父节点ID';

alter table users add lft int(10) default 0 comment '当前节点左边值';

alter table users add rgt int(10) default 0 comment '当前节点右边值';
```
## 如何使用


模型类(instanceof \Eloquent) 必须引用 `\Zaya\Lib\Tree\TreeTrait`;。
在`AppServiceProvider` 中的`boot()`添加

    User::observe(\Module\Base\Tree\TreeObserverTreeObserver::class);


自定义字段：
模型类(instanceof \Eloquent) 中的构造函数：
```php

  public function __construct(array $attributes = [])
    {
        $this->treeParentIdName = 'referrer_id';

        parent::__construct($attributes);
    }
```

### 数据已经存在
初始化数据：
```mysql

update users set parent_id=IFNULL(`referrer_id`, 0);
update users set lft=0, rgt=0;
```

初始化代码：

    this->treeInit();
    

// 如果有错误，则重置数据，重新开始计算
update users set parent_id=IFNULL(`referrer_id`, 0);
update users set lft=0, rgt=0;