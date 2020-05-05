<?php

namespace Polyel\View;

use Polyel\Storage\Storage;

class View
{
    private $view;

    private $resourceDir = ROOT_DIR . "/app/resources";

    private $storage;

    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }

    public function render($requestedView)
    {
        $this->view = null;

        if(file_exists($requestedView))
        {
            $this->view = file_get_contents($requestedView);
        }

        return $this->view;
    }
}