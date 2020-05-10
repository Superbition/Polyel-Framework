<?php

namespace Polyel\View\Element\Base;

use Polyel\View\ViewTools;
use Polyel\Storage\Facade\Storage;

class Element
{
    use ViewTools;

    // Holds the final rendered element content
    private $element = '';

    private $data;

    private $elementTemplateDir = ROOT_DIR . "/app/resources/elements";

    public function __construct()
    {

    }

    public function reset()
    {
        $this->element = '';
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

    protected function renderElement()
    {
        $elementLocation = $this->elementTemplateDir . '/' . $this->elementTemplate . ".html";
        $elementTemplate = Storage::access('local')->read($elementLocation);

        $elementTags = $this->getStringsBetween($elementTemplate, '{{', '}}');

        if(exists($this->data))
        {
            foreach($this->data as $key => $value)
            {
                if(in_array($key, $elementTags, true))
                {
                    // Automatically filter data tags for XSS prevention
                    $xssEscapedData = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    $elementTemplate = str_replace("{{ $key }}", $xssEscapedData, $elementTemplate);
                }
                else if(in_array("!$key!", $elementTags, true))
                {
                    // Else raw input has been requested by using {{ !data! }}
                    $elementTemplate = str_replace("{{ !$key! }}", $value, $elementTemplate);
                }
            }
        }

        $elementTemplate = str_replace('{{ @elementContent }}', $this->element, $elementTemplate);

        return $elementTemplate;
    }
}