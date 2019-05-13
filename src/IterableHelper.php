<?php
declare(strict_types=1);

namespace Functional;

/**
 * Class Helper for all iterable types
 *
 * @package UtilityBundle\Utils
 */
class IterableHelper
{
    public static function sequentialKeyFunc(int $start = 0): \Closure
    {
        $currentIteration = $start;

        return function () use (&$currentIteration) : int { return $currentIteration++; };
    }

    public static function passThroughFunc(): \Closure
    {
        return function ($key, $item) { return $item; };
    }

    public static function simplePredicateFunc(bool $return): \Closure
    {
        return function ($key, $item) use ($return) : bool { return $return; };
    }

    /**
     * @return \Closure
     * @see IterableHelper::toArray()
     */
    public static function passThroughKeyFunc(): \Closure
    {
        return function ($key, $item) { return $key; };
    }

    public static function sumReduceFunc(): \Closure
    {
        return function ($start, $key, $item) { return $start + $item; };
    }

    /**
     * Functional analog of \array_column() but not limited to array. It accept all iterable types.
     * Elements stored in iterable collection can has various types.
     *
     * @param iterable $items
     * @param callable $columnFunc Func definition function($item)
     *
     * @return array
     */
    public static function extractField(iterable $items, callable $columnFunc): array
    {
        $res = [];
        foreach ($items as $item) {
            $res[] = $columnFunc($item);
        }

        return $res;
    }

    /**
     * Functional analog of \array_map() but not limited to array and/or iterator. It accept all
     * iterable types. Elements stored in iterable collection can has various types.
     *
     * @param iterable $items
     * @param callable $mapFunc Func definition function($key, $item)
     * @param bool     $keepKey true - original keys from iterable will be kept, otherwise sequence of integer numbers
     *                          will be used and started from 0
     *
     * @return \Generator
     */
    public static function map(iterable $items, callable $mapFunc, bool $keepKey = true): \Generator
    {
        if ($keepKey) {
            foreach ($items as $key => $item) {
                yield $key => $mapFunc($key, $item);
            }

        } else {
            foreach ($items as $key => $item) {
                yield $mapFunc($key, $item);
            }
        }
    }

    /**
     * Map both key and value
     *
     * Functional analog of \array_map() but not limited to array and/or iterator. It accept all
     * iterable types. Elements stored in iterable collection can has various types.
     *
     * biMap generates new values for both key and item.
     * If 'keyfunc' return null, current element will be skipped. Its good to use for filtering elements.
     *
     * With call
     * <code>
     *
     * biMap(
     *    $items,
     *    \UtilityBundle\Utils\Functional\IterableVH::sequentialKeyFunc()
     *    \UtilityBundle\Utils\Functional\IterableVH::passThroughFunc()
     * )
     * </code>
     * it equivalent to call
     * <code>
     * map($items, \UtilityBundle\Utils\Functional\IterableVH::passThroughFunc(), false)
     * </code>
     *
     * @param iterable $items
     * @param callable $keyFunc  Function definition function($key, $item). If returns null current element will
     *                           skipped. Be carefully with keys. Actually yur func should produce unique keys for
     *                           whole iterable collection. Otherwise you can get unpredictable behavior of your code.
     * @param callable $itemFunc Function definition function($key, $item).
     *
     * @return \Generator
     * @see \UtilityBundle\Utils\Functional\IterableHelper::passThroughFunc()
     * @see \UtilityBundle\Utils\Functional\IterableHelper::sequentialKeyFunc()
     */
    public static function biMap(iterable $items, callable $keyFunc, callable $itemFunc): \Generator
    {
        foreach ($items as $key => $item) {
            $newKey  = $keyFunc($key, $item);
            $newItem = $itemFunc($key, $item);
            if ($newKey !== null) {
                yield $newKey => $newItem;
            }
        }
    }


    /**
     * Functional analog of \array_walk() or \iterator_apply but not limited to array and/or iterator. It accept all
     * iterable types. Elements stored in iterable collection can has various types.
     *
     * @param iterable $items
     * @param callable $applyFunc Func definition function($key, $item). Should return false if no further processing
     *                            (applying) required
     */
    public static function apply(iterable $items, callable $applyFunc): void
    {
        foreach ($items as $key => $item) {
            $res = $applyFunc($key, $item);
            if (!$res) {
                break;
            }
        }
    }

    /**
     * Functional analog of \array_filter() but not limited to array. It accept all iterable types.
     * Elements stored in iterable collection can has various types.
     *
     * @param iterable $items
     * @param callable $predicate Func definition function($key, $item)
     *
     * @return \Generator
     */
    public static function filter(iterable $items, callable $predicate): \Generator
    {
        foreach ($items as $key => $item) {
            if ($predicate($key, $item)) {
                yield $item;
            }
        }
    }

    /**
     * Functional analog of \iterator_to_array() but not limited to array. It accept all iterable types.
     * Elements stored in iterable collection can has various types.
     *
     * @param iterable $items
     * @param callable $idFunc Func definition function($key, $item), generate new array key. Should return null if new
     *                         key cannot be generated and current element will be skipped
     *
     * @return array
     */
    public static function toArray(iterable $items, callable $idFunc): array
    {
        $res = [];
        foreach ($items as $key => $item) {
            $key = $idFunc($key, $item);
            if ($key === null) {
                continue;
            }
            $res[$key] = $item;
        }

        return $res;
    }

    /**
     * Functional analog of \array_reduce() but not limited to array. It accept all iterable types.
     * Elements stored in iterable collection can has various types.
     *
     * @param iterable $items
     * @param callable $reduceFunc Function definition function($startValue, $key, $item)
     * @param mixed    $start
     *
     * @return mixed
     */
    public static function reduce(iterable $items, callable $reduceFunc, $start)
    {
        foreach ($items as $key => $item) {
            $start = $reduceFunc($start, $key, $item);
        }

        return $start;
    }

    /**
     * Wrap huge iterable collection into series of small pieces with  $batchSize elements in each.
     * Last piece might has $batchSize items or less.
     *
     * @param iterable $items
     * @param int      $batchSize
     *
     * @return \Generator
     */
    public static function wrapBatch(iterable $items, int $batchSize): \Generator
    {
        $batchNum = 0;
        $batch    = [];
        $cnt      = 0;
        foreach ($items as $key => $item) {
            if ($cnt === $batchSize) {
                yield $batchNum => $batch;
                $cnt   = 0;
                $batch = [];
                ++$batchNum;
            }
            $batch[$key] = $item;
            ++$cnt;
        }

        if ($batch !== []) {
            yield $batchNum => $batch;
        }
    }

    /**
     * Batch processing of huge iterable collection of $items
     *
     * @param iterable $items
     * @param int      $batchSize
     * @param callable $batchFunc     Function definition function(int $currentBatchNum, array $batchedItems) : void
     * @param callable $itemBiMapFunc Function definition function(string|int $key, mixed $item) : array [$newKey,
     *                                $newItem], if ($newKey === null) item will be skipped from further processing.
     *
     */
    public static function batchApply(iterable $items,
                                      int $batchSize,
                                      callable $batchFunc,
                                      callable $itemBiMapFunc = null): void
    {
        $cnt      = 0;
        $batchNum = 0;
        $batch    = [];
        foreach ($items as $key => $item) {
            if ($cnt === $batchSize) {
                $batchFunc($batchNum, $batch);
                $cnt = 0;
                ++$batchNum;
                $batch = [];
            }
            if ($itemBiMapFunc !== null) {
                [$key, $item] = $itemBiMapFunc($key, $item);
                if ($key === null) {
                    continue;
                }
            }
            $batch[$key] = $item;
            ++$cnt;
        }
        if ($batch !== []) {
            $batchFunc($batchNum, $batch);
        }
    }
}
