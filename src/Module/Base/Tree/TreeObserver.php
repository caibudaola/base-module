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

        $treeParentIdName = $model->treeParentIdName ?? 'parent_id';
        $treeLftName = $model->treeLftName ?? 'lgt';
        $treeRgtName = $model->treeRgtName ?? 'rgt';

        if (is_null($model->$treeParentIdName)) {
            $model->$treeParentIdName = 0;
        }
        $parentId = $model->$treeParentIdName;

        // 获取父节点
        // 保证父记录一定存在, 表初始化需要添加一条默认记录: [id => 0, lft => 1, rgt => 1000000000]
        $parentModel = $model->newQuery()
            ->lockForUpdate()
            ->select(['id', $treeParentIdName, $treeLftName, $treeRgtName])
            ->where('id', $parentId)
            ->first();
        if (is_null($parentModel)) { // 父节点不存在
            $parentModel = new \stdClass();
            $parentModel->$treeLftName = 1;
            $parentModel->$treeRgtName = 1000000000;

            $model->$treeParentIdName = 0;
            $parentId = 0;
        }

        // 查找最大兄弟节点
        $brotherModel = $model->newQuery()
            ->lockForUpdate()
            ->select(['id', $treeParentIdName, $treeLftName, $treeRgtName])
            ->where([
                [$treeParentIdName, $parentId],
                [$treeRgtName, '<', $parentModel->$treeRgtName]
            ])
            ->orderByDesc($treeRgtName)
            ->first();
        if (is_null($brotherModel)) { // 不存在兄弟节点
            $brotherModel = new \stdClass();
            $brotherModel->$treeLftName = $parentModel->$treeLftName;
            $brotherModel->$treeRgtName = $parentModel->$treeLftName;
        }

        if (($parentModel->$treeRgtName - $brotherModel->$treeRgtName) < 3) { // 父节点剩下的位置不够存放该节点
            // 父节点右边位置 + 一个步长
            $model->newQuery()->where($treeRgtName, $parentModel->$treeRgtName)
                ->update([$treeRgtName => new Expression($treeRgtName . " + $step")]);
            // 其余节点的左右节点也需要扩张一个步长，即，集体右移一个步长
            $model->newQuery()->where($treeLftName, '>', $parentModel->$treeRgtName)
                ->update([
                    $treeLftName => new Expression($treeLftName . " + $step"),
                    $treeRgtName => new Expression($treeRgtName . " + $step"),
                ]);
            $parentModel->$treeRgtName += $step;
        }
        $model->$treeLftName = $brotherModel->$treeRgtName + 1;
        $model->$treeRgtName = min(
            $parentModel->$treeRgtName - 1,
            $brotherModel->$treeRgtName + max(
                2,
                (($evenNum = intval(min(
                        ceil(($parentModel->$treeRgtName - $parentModel->$treeLftName - 1) / $treeBranchUtmostNum),
                        $parentId == 0 ? $step : $step / $treeBranchUtmostNum
                    ))) % 2) ? $evenNum - 1 : $evenNum
            )
        );
    }
}