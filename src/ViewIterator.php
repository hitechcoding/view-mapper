<?php

declare(strict_types=1);

namespace HTC\ViewMapper;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use function array_map;
use function count;

class ViewIterator implements IteratorAggregate, Countable
{
    private $dataProvider;

    private $viewBuilder;

    private $initialized = false;

    private $views;

    public function __construct(callable $dataProvider, callable $viewBuilder)
    {
        $this->dataProvider = $dataProvider;
        $this->viewBuilder = $viewBuilder;
    }

    public function getIterator(): iterable
    {
        $this->init();

        return new ArrayIterator($this->views);
    }

    public function count(): int
    {
        $this->init();

        return count($this->views);
    }

    private function init(): void
    {
        if (true === $this->initialized) {
            return;
        }

        $dataProvider = $this->dataProvider;
        // run the closure to get entities
        $entities = $dataProvider();

        // run view builder for all entities
        $this->views = array_map($this->viewBuilder, $entities);
        $this->initialized = true;
    }
}
