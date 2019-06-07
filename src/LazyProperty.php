<?php

declare(strict_types=1);

namespace HTC\ViewMapper;

use BadMethodCallException;
use function property_exists;

class LazyProperty
{
    private $entityProvider;

    private $viewBuilder;

    private $initialized = false;

    private $view;

    public function __construct(callable $entityProvider, callable $viewBuilder)
    {
        $this->entityProvider = $entityProvider;
        $this->viewBuilder = $viewBuilder;
    }

    public function __get($name)
    {
        $this->init();

        return $this->view->$name;
    }

    public function __set($name, $value)
    {
        throw new BadMethodCallException('View classes cannot have setters.');
    }

    public function __isset($name)
    {
        $this->init();

        return property_exists($this->view, $name);
    }

    public function __call($name, $arguments)
    {
        $this->init();

        return $this->view->$name($arguments);
    }

    private function init(): void
    {
        if ($this->initialized) {
            return;
        }

        $entityProvider = $this->entityProvider;
        $viewBuilder = $this->viewBuilder;
        $entity = $entityProvider();

        $this->view = $viewBuilder($entity);

        $this->initialized = true;
    }
}
