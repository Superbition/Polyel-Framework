<?php

namespace Polyel\Database\Query\Support;

use Polyel\Database\Query\QueryBuilder;

trait ClosureSupport
{
    private function processClosure($closure)
    {
        $queryClosureBuilder = new QueryBuilder(null, 1);

        $closure = $closure->bindTo($queryClosureBuilder);

        $closure($queryClosureBuilder);

        $closureResult = $queryClosureBuilder->get();

        $this->data = array_merge($this->data, $closureResult['data']);

        return '(' . $closureResult['query'] . ')';
    }
}