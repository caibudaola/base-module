# 树状结构（无线分类）数据库存储实现扩展

## 数据表必须的额外字段

| 字段 | 类型 | 描述 |
| ---- | ---- | ---- |
| parent_id | int | 当前节点对应的父节点ID |
| lft | int | 当前节点左边值|
| rgt |int | 当前节点右边值 |

## 如何使用

模型类(instanceof \Eloquent) 必须引用 `\Zaya\Lib\Tree\TreeTrait`，并在构造函数中调用`$this->treeBoot();`。