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

    public function chunkById($count, Closure $callback, $column = null)
    {
        // Default column for chunk by id is 'id'
        $column = $column ?? 'id';

        // At first we don't have or know the last id value
        $lastId = null;

        do
        {
            // Clone to avoid duplicate queries and query data for every chunk round
            $clone = clone $this;

            // Use the id column if it exists, first chunk will grab the last id...
            if(exists($lastId))
            {
                // Once we have the last id from the first chunk, we continue onwards to chunk by id
                $clone->where($column, '>', $lastId);
            }

            // Order by the id column and limit by the chunk count, this also gets us the first id in order
            $clone->orderBy($column, 'ASC')->limit($count);

            $results = $clone->get();

            // Get the chunk result set count
            $chunkCount = count($results);

            // Break out of the loop if there are no results to process
            if($chunkCount === 0)
            {
                break;
            }

            // Keep calling the anonymous function until chunking completes or false is returned
            if($callback($results) === false)
            {
                // Means we should stop chunking on a false return
                return false;
            }

            // Grab the last id from the chunk results, so we can continue to chunk by id
            $lastId = end($results)[$column];

            // Free up memory
            unset($results);

        }while($chunkCount === $count);

        // Chunking complete
        return true;
    }

    public function deferAndChunkById($count, Closure $callback, $column = null)
    {
        \Swoole\Event::defer(function() use($count, $callback, $column)
        {
            go(function() use($count, $callback, $column)
            {
                $this->chunkById($count, $callback, $column);
            });
        });
    }
}