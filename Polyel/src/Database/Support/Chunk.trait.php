<?php

namespace Polyel\Database\Support;

use Closure;
use RuntimeException;

trait Chunk
{
    public function chunk($count, Closure $callback)
    {
        if(!exists($this->order))
        {
            throw new RuntimeException('You must provide an orderBy clause when chunking results.');
        }

        $page = 1;

        do
        {
            $clone = clone $this;

            $clone->offset(($page - 1) * $count)->limit($count);

            $results = $clone->get();

            $chunkCount = count($results);

            if($chunkCount === 0)
            {
                break;
            }

            if($callback($results, $page) === false)
            {
                return false;
            }

            unset($results);

            $page++;

        }while($chunkCount === $count);

        return true;
    }

    public function deferAndChunk($count, Closure $callback)
    {
        \Swoole\Event::defer(function() use($count, $callback)
        {
            go(function() use($count, $callback)
            {
                $this->chunk($count, $callback);
            });
        });
    }
}