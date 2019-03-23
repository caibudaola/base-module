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
    protected static $treeMaxNum = 1000000000;

    public function treeBoot()
    {
        $this->registerObserver(TreeObserver::class);
    }

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
                    $query->whereBetween('lft', [$rootModel['lft'], $rootModel['rgt']]);
                }
            })
            ->orderBy('lft')
            ->get()->toArray();

        if ($id == 0) {
            $tree[0] = [
                'id' => 0,
                'lft' => 0,
                'rgt' => self::$treeMaxNum,
                'sub_tree' => [],
            ];
        } else {
            $tree = [];
        }
        foreach ($rows as &$row) {
            $tree[$row['id']] = &$row;
            if (isset($tree[$row['parent_id']])) {
                $tree[$row['parent_id']]['sub_tree'][] = &$tree[$row['id']];
            }
        }
        return $tree[$id];
    }

    /**
     * 移动节点，只有同一个棵树下的节点可以移动
     * $beforeBrotherId 和 $afterBrotherId 至少一个有值
     *
     * @param int $moveId
     * @param int|null $beforeBrotherId
     * @param int|null $afterBrotherId
     * @return true
     * @throws \RuntimeException|ModelNotFoundException
     */
    public function move($moveId, $beforeBrotherId = null, $afterBrotherId = null)
    {
        if (is_null($beforeBrotherId) && is_null($afterBrotherId)) {
            throw new \RuntimeException('MissingParam');
        }

        $collection = $this->newQuery()
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
                'parent_id' => $rightBrotherItem['parent_id'],
                'lft' => $rightBrotherItem['lft'] - 1,
                'rgt' => $rightBrotherItem['lft'] - 1,
            ];
        }
        if ($moveItem['parent_id'] != $leftBrotherItem['parent_id']) {
            // 超过移动区间
            throw new \RuntimeException('MoveOver');
        }

        return $this->moveBase($moveItem, $leftBrotherItem);
    }

    public function updateParent($moveId, $newParentId)
    {
        $moveItem = $this->newQuery()
            ->where('id', $moveId)
            ->firstOrFail();
        if ($moveItem['parent_id'] == $newParentId) {
            return true;
        }
        // 找出新父节点
        if ($newParentId == 0) {
            $newParentItem = [
                'id' => 0,
                'lft' => 0,
                'rgt' => self::$treeMaxNum
            ];
        } else {
            $newParentItem = $this->newQuery()
                ->where('id', $newParentId)
                ->firstOrFail();
        }
        if ($moveItem['lft'] < $newParentItem['lft'] && $moveItem['rgt'] > $newParentItem['rgt']) {
            // 不能移动到自己的子树
            throw new \RuntimeException('Forbid:MoveToSubTree');
        }
        // 找出新父节点的最右子节点
        $leftBrotherItem = $this->newQuery()
            ->where('parent_id', $newParentId)
            ->orderByDesc('lft')
            ->first();
        if (is_null($leftBrotherItem)) {
            // 构造一个假的子节点
            $leftBrotherItem = [
                'parent_id' => $newParentItem['id'],
                'lft' => $newParentItem['lft'],
                'rgt' => $newParentItem['lft'],
            ];
        }
        return $this->moveBase($moveItem, $leftBrotherItem);
    }

    protected function moveBase($moveItem, $leftBrotherItem)
    {
        // 计算当前要操作树的长度
        $moveTreeLength = $moveItem['rgt'] - $moveItem['lft'] + 1;
        // 是否向左移动
        $isMoveLeft = $moveItem['rgt'] > $leftBrotherItem['rgt'] ? true : false;
        // 计算操作数和对标树的距离
        $distance = $isMoveLeft ? $leftBrotherItem['rgt'] - $moveItem['lft'] + 1 : $leftBrotherItem['rgt'] - $moveItem['rgt'];
        // 先将要操作树，移出操作区间
        $this->newQuery()
            ->whereBetween('rgt', [$moveItem['lft'], $moveItem['rgt']])
            ->update([
                'lft' => new Expression("lft - " . self::$treeMaxNum),
                'rgt' => new Expression("rgt - " . self::$treeMaxNum),
            ]);
        // 移动两颗树之间的树
        if ($isMoveLeft) {
            $this->newQuery()
                ->whereBetween('rgt', [$leftBrotherItem['rgt'] + 1, $moveItem['rgt'] - 1])
                ->whereBetween('lft', [$leftBrotherItem['lft'] + 1, $moveItem['lft'] - 1])
                ->update([
                    'lft' => new Expression("lft + $moveTreeLength"),
                    'rgt' => new Expression("rgt + $moveTreeLength"),
                ]);
            $this->newQuery()
                ->whereBetween('lft', [$leftBrotherItem['lft'] + 1, $moveItem['lft'] - 1])
                ->where('rgt', '>', $moveItem['rgt'])
                ->update([
                    'lft' => new Expression("lft + $moveTreeLength"),
                ]);
            $this->newQuery()
                ->whereBetween('rgt', [$leftBrotherItem['rgt'] + 1, $moveItem['rgt'] - 1])
                ->where('lft', '<', $leftBrotherItem['lft'])
                ->update([
                    'rgt' => new Expression("rgt + $moveTreeLength"),
                ]);
        } else {
            $this->newQuery()
                ->whereBetween('rgt', [$moveItem['rgt'] + 1, $leftBrotherItem['rgt']])
                ->whereBetween('lft', [$moveItem['lft'] + 1, $leftBrotherItem['lft']])
                ->update([
                    'lft' => new Expression("lft - $moveTreeLength"),
                    'rgt' => new Expression("rgt - $moveTreeLength"),
                ]);
            $this->newQuery()
                ->whereBetween('rgt', [$moveItem['rgt'] + 1, $leftBrotherItem['rgt']])
                ->where('lft', '<', $moveItem['lft'])
                ->update([
                    'rgt' => new Expression("rgt - $moveTreeLength"),
                ]);
            $this->newQuery()
                ->whereBetween('lft', [$moveItem['lft'] + 1, $leftBrotherItem['lft']])
                ->where('rgt', '>', $leftBrotherItem['rgt'])
                ->update([
                    'lft' => new Expression("lft - $moveTreeLength"),
                ]);
        }
        // 移动当前要操作的树
        $this->newQuery()
            ->whereBetween('rgt', [$moveItem['lft'] - self::$treeMaxNum, $moveItem['rgt'] - self::$treeMaxNum])
            ->update([
                'lft' => new Expression("lft + " . self::$treeMaxNum . " + $distance"),
                'rgt' => new Expression("rgt + " . self::$treeMaxNum . " + $distance"),
            ]);
        if ($moveItem['parent_id'] != $leftBrotherItem['parent_id']) {
            $this->newQuery()
                ->where('id', $moveItem['id'])
                ->update([
                    'parent_id' => $leftBrotherItem['parent_id'],
                ]);
        }
        return true;
    }
}