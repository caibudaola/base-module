<?php
namespace Module\Base\Tree;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;

/**
 * Class TreeTrait
 * @package App\Models\Observers
 * @mixin Model
 */
trait TreeTrait
{
    public $treeMaxNum = 1000000000;

    public $treeParentIdName = 'parent_id';
    public $treeLftName = 'lft';
    public $treeRgtName = 'rgt';

    public function treeBoot()
    {
        $this->registerObserver(TreeObserver::class);
    }

    /**
     * 获取指定树å
     *
     * @param int $id
     * @return mixed
     */
    public function getTree(int $id)
    {
        $class = get_class($this);
        $rows = $this->newQuery()
            ->where(function(Builder $query) use ($id, $class) {
                if ($id > 0) {
                    $rootModel = $this->newQuery()
                        ->where('id', $id)
                        ->first();
                    if (!$rootModel) {
                        throw new \Exception('节点有错误！');
                    }
                    $query->whereBetween($this->treeLftName, [$rootModel[$this->treeLftName], $rootModel[$this->treeRgtName]]);
                }
            })
            ->orderBy($this->treeLftName)
            ->get()->toArray();

        if ($id == 0) {
            $tree[0] = [
                'id' => 0,
                $this->treeLftName => 0,
                $this->treeRgtName => $this->treeMaxNum,
                'sub_tree' => [],
            ];
        } else {
            $tree = [];
        }
        foreach ($rows as &$row) {
            $tree[$row['id']] = &$row;
            if (isset($tree[$row[$this->treeParentIdName]])) {
                $tree[$row[$this->treeParentIdName]]['sub_tree'][] = &$tree[$row['id']];
            }
        }
        return $tree[$id];
    }

    /**
     * 移动节点，只有同一个棵树下的节点可以移动
     * $beforeBrotherId 和 $afterBrotherId 至少一个有值
     *
     * @param $moveId
     * @param null $beforeBrotherId
     * @param null $afterBrotherId
     * @return bool
     * @throws \Exception
     */
    public function treeMove($moveId, $beforeBrotherId = null, $afterBrotherId = null)
    {
        if (is_null($beforeBrotherId) && is_null($afterBrotherId)) {
            throw new \RuntimeException('MissingParam');
        }

        $collection = $this->newQuery()
            ->lockForUpdate()
            ->select(['id', $this->treeParentIdName, $this->treeLftName, $this->treeRgtName])
            ->whereIn('id', array_filter([$moveId, $beforeBrotherId, $afterBrotherId]))
            ->get();
        $moveItem = [];
        $leftBrotherItem = [];
        $rightBrotherItem = [];
        foreach ($collection->toArray() as $item) {
            if ($item['id'] == $moveId) {
                $moveItem = $item;
            }
            if ($item['id'] == $beforeBrotherId) {
                $leftBrotherItem = $item;
            }
            if ($item['id'] == $afterBrotherId) {
                $rightBrotherItem = $item;
            }
        }
        if (!$moveItem || (!$leftBrotherItem && !$rightBrotherItem)) {
            throw new \Exception('移动的节点有误或者左右兄弟节点都不存在！');
        }
        if (!$leftBrotherItem) {
            $leftBrotherItem = [
                $this->treeParentIdName => $rightBrotherItem[$this->treeParentIdName],
                $this->treeLftName => $rightBrotherItem[$this->treeLftName] - 1,
                $this->treeRgtName => $rightBrotherItem[$this->treeLftName] - 1,
            ];
        }
        if ($moveItem[$this->treeParentIdName] != $leftBrotherItem[$this->treeParentIdName]) {
            // 超过移动区间
            throw new \RuntimeException('MoveOver');
        }

        return $this->moveBase($moveItem, $leftBrotherItem);
    }
    public function updateParent($moveId, $newParentId)
    {
        $moveItem = $this->newQuery()
            ->lockForUpdate()
            ->select(['id', $this->treeParentIdName, $this->treeLftName, $this->treeRgtName])
            ->where('id', $moveId)
            ->firstOrFail();
        if ($moveItem[$this->treeParentIdName] == $newParentId) {
            return true;
        }
        // 找出新父节点
        if ($newParentId == 0) {
            $newParentItem = [
                'id' => 0,
                $this->treeLftName => 0,
                $this->treeRgtName => $this->treeMaxNum
            ];
        } else {
            $newParentItem = $this->newQuery()
                ->lockForUpdate()
                ->select(['id', $this->treeParentIdName, $this->treeLftName, $this->treeRgtName])
                ->where('id', $newParentId)
                ->firstOrFail();
        }
        if ($moveItem[$this->treeLftName] < $newParentItem[$this->treeLftName] && $moveItem[$this->treeRgtName] > $newParentItem[$this->treeRgtName]) {
            // 不能移动到自己的子树
            throw new \RuntimeException('Forbid:MoveToSubTree');
        }
        // 找出新父节点的最右子节点
        $leftBrotherItem = $this->newQuery()
            ->lockForUpdate()
            ->select(['id', $this->treeParentIdName, $this->treeLftName, $this->treeRgtName])
            ->where($this->treeParentIdName, $newParentId)
            ->orderByDesc($this->treeLftName)
            ->first();
        if (is_null($leftBrotherItem)) {
            // 构造一个假的子节点
            $leftBrotherItem = [
                $this->treeParentIdName => $newParentItem['id'],
                $this->treeLftName => $newParentItem[$this->treeLftName],
                $this->treeRgtName => $newParentItem[$this->treeLftName],
            ];
        }
        return $this->moveBase($moveItem, $leftBrotherItem);
    }

    protected function moveBase($moveItem, $leftBrotherItem)
    {
        // 计算当前要操作树的长度
        $moveTreeLength = $moveItem[$this->treeRgtName] - $moveItem[$this->treeLftName] + 1;
        // 是否向左移动
        $isMoveLeft = $moveItem[$this->treeRgtName] > $leftBrotherItem[$this->treeRgtName] ? true : false;
        // 计算操作数和对标树的距离
        $distance = $isMoveLeft ? $leftBrotherItem[$this->treeRgtName] - $moveItem[$this->treeLftName] + 1 : $leftBrotherItem[$this->treeRgtName] - $moveItem[$this->treeRgtName];
        // 先将要操作树，移出操作区间
        $this->newQuery()
            ->whereBetween($this->treeRgtName, [$moveItem[$this->treeLftName], $moveItem[$this->treeRgtName]])
            ->update([
                $this->treeLftName => new Expression($this->treeLftName . ' - ' . $this->treeMaxNum),
                $this->treeRgtName => new Expression($this->treeRgtName . ' - ' . $this->treeMaxNum),
            ]);
        // 移动两颗树之间的树
        if ($isMoveLeft) {
            $this->newQuery()
                ->whereBetween($this->treeRgtName, [$leftBrotherItem[$this->treeRgtName] + 1, $moveItem[$this->treeRgtName] - 1])
                ->whereBetween($this->treeLftName, [$leftBrotherItem[$this->treeLftName] + 1, $moveItem[$this->treeLftName] - 1])
                ->update([
                    $this->treeLftName => new Expression($this->treeLftName . " + $moveTreeLength"),
                    $this->treeRgtName => new Expression($this->treeRgtName . " + $moveTreeLength"),
                ]);
            $this->newQuery()
                ->whereBetween($this->treeLftName, [$leftBrotherItem[$this->treeLftName] + 1, $moveItem[$this->treeLftName] - 1])
                ->where($this->treeRgtName, '>', $moveItem[$this->treeRgtName])
                ->update([
                    $this->treeLftName => new Expression($this->treeLftName . " + $moveTreeLength"),
                ]);
            $this->newQuery()
                ->whereBetween($this->treeRgtName, [$leftBrotherItem[$this->treeRgtName] + 1, $moveItem[$this->treeRgtName] - 1])
                ->where($this->treeLftName, '<', $leftBrotherItem[$this->treeLftName])
                ->update([
                    $this->treeRgtName => new Expression($this->treeRgtName . " + $moveTreeLength"),
                ]);
        } else {
            $this->newQuery()
                ->whereBetween($this->treeRgtName, [$moveItem[$this->treeRgtName] + 1, $leftBrotherItem[$this->treeRgtName]])
                ->whereBetween($this->treeLftName, [$moveItem[$this->treeLftName] + 1, $leftBrotherItem[$this->treeLftName]])
                ->update([
                    $this->treeLftName => new Expression($this->treeLftName . " - $moveTreeLength"),
                    $this->treeRgtName => new Expression($this->treeRgtName . " - $moveTreeLength"),
                ]);
            $this->newQuery()
                ->whereBetween($this->treeRgtName, [$moveItem[$this->treeRgtName] + 1, $leftBrotherItem[$this->treeRgtName]])
                ->where($this->treeLftName, '<', $moveItem[$this->treeLftName])
                ->update([
                    $this->treeRgtName => new Expression($this->treeRgtName . " - $moveTreeLength"),
                ]);
            $this->newQuery()
                ->whereBetween($this->treeLftName, [$moveItem[$this->treeLftName] + 1, $leftBrotherItem[$this->treeLftName]])
                ->where($this->treeRgtName, '>', $leftBrotherItem[$this->treeRgtName])
                ->update([
                    $this->treeLftName => new Expression($this->treeLftName . " - $moveTreeLength"),
                ]);
        }
        // 移动当前要操作的树
        $this->newQuery()
            ->whereBetween($this->treeRgtName, [$moveItem[$this->treeLftName] - $this->treeMaxNum, $moveItem[$this->treeRgtName] - $this->treeMaxNum])
            ->update([
                $this->treeLftName => new Expression($this->treeLftName . ' + ' . $this->treeMaxNum . " + $distance"),
                $this->treeRgtName => new Expression($this->treeRgtName . ' + ' . $this->treeMaxNum . " + $distance"),
            ]);
        if ($moveItem[$this->treeParentIdName] != $leftBrotherItem[$this->treeParentIdName]) {
            $this->newQuery()
                ->where('id', $moveItem['id'])
                ->update([
                    $this->treeParentIdName => $leftBrotherItem[$this->treeParentIdName],
                ]);
        }
        return true;
    }

    /**
     * 如果是以后数据，那么有必要的话，可以调用此函数进行初始化
     *
     * @param null $parentId
     * @param int $nextLft
     * @return int
     */
    public function treeInit($parentId=null, $nextLft=1)
    {
        // 每次增长步长
        $step = 100;

        $treeParentIdName = $this->treeParentIdName;
        $treeLftName = $this->treeLftName;
        $treeRgtName = $this->treeRgtName;

        if (!$parentId) {
            $query = $this->newQuery()
                ->where(function(Builder $query){
                    return $query->whereNull($this->treeParentIdName)
                        ->orWhere($this->treeParentIdName, 0);
                });
        } else {
            $query = $this->newQuery()
                ->where($this->treeParentIdName, $parentId);
        }

        $nodes = $query
            ->lockForUpdate()
            ->select(['id', $treeParentIdName, $treeLftName, $treeRgtName])
            ->orderBy($treeLftName)
            ->get();

        if (!$nodes->toArray()) {
            return $nextLft + $step - 2;
        }

        foreach ($nodes as $node) {
            $isHasSub = true;
            $node->$treeLftName = $nextLft;
            $lastRgt = $this->treeInit($node->id, $nextLft + 1);
            $node->$treeRgtName = $lastRgt + 1;
            $node->save();

            $nextLft = $lastRgt + 2;
        }

        return $nextLft + $step;
    }
}