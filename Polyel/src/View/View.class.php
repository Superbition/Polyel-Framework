<?php

namespace Polyel\View;

use Polyel\Storage\Facade\Storage;

class View
{
    private $resource;

    private $data;

    private $resourceDir = ROOT_DIR . "/app/resources";

    public function __construct()
    {

    }

    public function render(ViewBuilder $resource)
    {
        $this->resource = $resource->resource;
        $this->data = $resource->data;

        if($resource->isValid())
        {
            $type = $resource->type;
            $this->resource = Storage::access('local')->read($this->resourceDir . "/${type}s/" . $this->resource . ".${type}.html");

            return $this->resource;
        }

        return null;
    }
}