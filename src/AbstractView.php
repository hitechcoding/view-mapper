<?php

declare(strict_types=1);

namespace HTC\ViewMapper;

use LogicException;
use function get_class;
use function is_object;
use function gettype;
use function sprintf;

/**
 * Commented code is here until new version of phpstan comes with this bug merged @see https://github.com/phpstan/phpstan/issues/2160.
 */
abstract class AbstractView
{
//    private function __construct($entity)
//    {
//        $type = is_object($entity) ? get_class($entity) : gettype($entity);
//        throw new LogicException(sprintf('You must create child constructor with argument of type "%s".', $type));
//    }

    /** @return static[] */
    public static function fromIterable(iterable $entities): array
    {
        $views = [];
        foreach ($entities as $entity) {
            /* @noinspection PhpMethodParametersCountMismatchInspection */
            $views[] = new static($entity);
        }

        return $views;
    }

    /**  @return LazyCollection|static[] */
    public static function lazyCollection(callable $collectionProvider)
    {
        $viewBuilder = static function ($entity) {
            /* @noinspection PhpMethodParametersCountMismatchInspection */
            return new static($entity);
        };

        return new LazyCollection($collectionProvider, $viewBuilder);
    }

    /** @return static|LazyProperty */
    public static function lazy(callable $entityProvider)
    {
        $viewBuilder = static function ($entity) {
            /* @noinspection PhpMethodParametersCountMismatchInspection */
            return new static($entity);
        };

        return new LazyProperty($entityProvider, $viewBuilder);
    }
}
