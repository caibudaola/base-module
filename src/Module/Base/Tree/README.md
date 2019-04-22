# 树状结构（无线分类）数据库存储实现扩展

## 数据表必须的额外字段

| 字段 | 类型 | 描述 |
| ---- | ---- | ---- |
| parent_id | int | 当前节点对应的父节点ID |
| lft | int | 当前节点左边值|
| rgt |int | 当前节点右边值 |
```$xslt

alter table table_name add parent_id int(10) default 0 comment '当前节点对应的父节点ID';

alter table table_name add lft int(10) default 0 comment '当前节点左边值';

alter table table_name add rgt int(10) default 0 comment '当前节点右边值';
```
## 如何使用

模型类(instanceof \Eloquent) 必须引用 `\Module\Base\Tree\TreeTrait`，并在构造函数中调用`$this->treeBoot();`。

自定义字段：
模型类(instanceof \Eloquent) 中的构造函数：
```php
public function __construct(array $attributes = [])
{
    $this->treeParentIdName = 'custom_parent_id';
    $this->treeLftName = 'custom_lft';
    $this->treeRgtName = 'custom_rgt';

    parent::__construct($attributes);
}
```

### 数据已经存在
初始化数据：

    $this->treeInit();
    

### 注意

在创建或者移动节点的时候，有可能大范围更新 lft/rgt，所以需要在调用外层加上事务