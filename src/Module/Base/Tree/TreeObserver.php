<?php
namespace Module\Base\Tree;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;

class TreeObserver
{
    public function creating(Model $model)
    {
        // 每次增长步长
        $step = 100;
        // 最有可能为几叉树
        $treeBranchUtmostNum = 3;

        if (is_null($model->parent_id)) {
            $model->parent_id = 0;
        }
        $parentId = $model->parent_id;

        // 获取父节点
        // 保证父记录一定存在, 表初始化需要添加一条默认记录: [id => 0, lft => 1, rgt => 1000000000]
        $parentModel = $model->newQuery()
            ->where('id', $parentId)
            ->first();
        if (is_null($parentModel)) { // 父节点不存在
            $parentModel = new \stdClass();
            $parentModel->lft = 1;
            $parentModel->rgt = 1000000000;

            $model->parent_id = 0;
            $parentId = 0;
        }
        // 查找最大兄弟节点
        $brotherModel = $model->newQuery()
            ->where([
                ['parent_id', $parentId],
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
    }
}