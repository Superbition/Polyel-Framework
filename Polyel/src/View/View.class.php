<?php

namespace Polyel\View;

class View
{
    private $view;

    public function __construct()
    {

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