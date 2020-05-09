<?php

namespace Polyel\View\Element;

class PolyelElement
{
    // Holds the final rendered element content
    private $element = '';

    private $data;

    private $elementTemplateDir = ROOT_DIR . "/app/resources/elements";

    public function __construct()
    {

    }

    protected function setData($tag, $data = null)
    {
        if(is_array($tag))
        {
            foreach($tag as $key => $value)
            {
                $this->data[$key] = $value;
            }
        }
        else if(exists($tag) && exists($data))
        {
            $this->data[$tag] = $data;
        }
    }

    protected function appendLine($start, $data, $end, $xssFilter = true)
    {
        if($xssFilter)
        {
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }

        $append = $start . $data . $end;

        $this->element .= $append;
    }
}