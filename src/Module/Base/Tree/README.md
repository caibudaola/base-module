# 树状结构（无线分类）数据库存储实现扩展

## 数据表必须的额外字段

| 字段 | 类型 | 描述 |
| ---- | ---- | ---- |
| parent_id | int | 当前节点对应的父节点ID |
| lft | int | 当前节点左边值|
| rgt |int | 当前节点右边值 |

## 如何使用

模型类(instanceof \Eloquent) 必须引用 `\Zaya\Lib\Tree\TreeTrait`，并在构造函数中调用`$this->treeBoot();`。
### 数据已经存在
初始化数据：

update users set parent_id=referrer_id;

    public function execInit() {

        User::where('referrer_id', 0)/*where('id', 1088)*/->orWhereNull('referrer_id')->select([
            'id',
            'referrer_id'
        ])->chunk(100, function ($users){
            foreach ($users as $user) {
                $this->initData($user);
                $this->initUserInfo($user->id);
            }
        });

        $data = (new User)->getTree(1088);
        return $data;
    }

    private function initUserInfo($id) {
        $arrData = User::where('referrer_id', $id)
            ->get([
                'id',
                'referrer_id'
            ]);
        foreach ($arrData as $item ) {
            $this->initData($item);
            $this->initUserInfo($item->id);

        }
    }


    public function initData(Model $model)
    {
        Logger('===', [$model->id, $model->referrer_id] );
        if ( $model->lft != 0 && $model->rgt != 0 ) {

            return;
        }
        // 每次增长步长
        $step = 100;
        // 最有可能为几叉树
        $treeBranchUtmostNum = 3;

        if (is_null($model->referrer_id) || 0 == $model->referrer_id) {
            $model->referrer_id = 0;
        }
        $parentId = $model->referrer_id;

        // 获取父节点
        // 保证父记录一定存在, 表初始化需要添加一条默认记录: [id => 0, lft => 1, rgt => 1000000000]
        $parentModel = $model->newQuery()
            ->where('id', $parentId)
            ->first();
        if (is_null($parentModel)) { // 父节点不存在
            $parentModel = new \stdClass();
            $parentModel->lft = 1;
            $parentModel->rgt = 1000000000;

            $model->referrer_id = 0;
            $parentId = 0;
        }
        // 查找最大兄弟节点
        $brotherModel = $model->newQuery()
            ->where('lft', '!=', 0)
            ->where('rgt', '!=', 0)
            ->where([
                ['referrer_id', $parentId],
                ['rgt', '<', $parentModel->rgt]
            ])
            ->orderByDesc('rgt')
            ->first();
        if (is_null($brotherModel)) { // 不存在兄弟节点
            $brotherModel = new \stdClass();
            $brotherModel->lft = $parentModel->lft;
            $brotherModel->rgt = $parentModel->lft;
        }
        if (($parentModel->rgt - $brotherModel->rgt) < 3) { // 父节点剩下的位置不够存放该节点
            // 父节点右边位置 +100
            $model->newQuery()->where('rgt', '>=', $parentModel->rgt)
                ->update(['rgt' => new Expression("rgt + $step")]);
            $parentModel->rgt += $step;
        }
        $model->lft = $brotherModel->rgt + 1;
        $model->rgt = min(
            $parentModel->rgt - 1,
            $brotherModel->rgt + max(
                2,
                (($evenNum = intval(min(
                        ceil(($parentModel->rgt - $parentModel->lft - 1) / $treeBranchUtmostNum),
                        $parentId == 0 ? $step : $step / $treeBranchUtmostNum
                    ))) % 2) ? $evenNum - 1 : $evenNum
            )
        );
        $model->parent_id = $parentId;
 
        $model->save();
    }

// 如果有错误，则重置数据，重新开始计算
update users set parent_id=0, lft=0, rgt=0;