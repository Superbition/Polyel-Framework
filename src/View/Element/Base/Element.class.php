<?php

namespace Polyel\View\Element\Base;

use Polyel\View\ViewTools;
use Polyel\Storage\Facade\Storage;

class Element
{
    use ViewTools;

    // Holds the final rendered element content
    private $elementContent = '';

    private $data;

    private $elementTemplate;

    // When a new element is created, its data is stored here for rendering later
    private $elementBlockData;

    private $elementTemplateDir = ROOT_DIR . "/resources/elements";

    public function __construct()
    {

    }

    private function getElementTemplate()
    {
        if(!exists($this->elementTemplate))
        {
            // Convert file name from dot syntax to a real file path if they are used
            $elementFilePath = str_replace(".", "/", $this->element);
            $elementLocation = $this->elementTemplateDir . '/';

            if(file_exists($elementLocation . $elementFilePath . '.html'))
            {
                $this->elementTemplate = Storage::access('local', $elementLocation)->read($elementFilePath . '.html');
            }
        }
    }

    public function reset()
    {
        $this->elementContent = '';
        $this->data = null;
        $this->elementTemplate = null;
        $this->elementBlockData = [];
    }

    protected function setElementData($tag, $data = null)
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

    protected function appendHtmlTag($start, $data, $end, $xssFilter = true)
    {
        if($xssFilter === true)
        {
            $data = $this->xssFilter($data);
        }

        $append = $start . $data . $end;

        $this->elementContent .= $append;
    }

    protected function appendHtmlBlock($block, $data = null): void
    {
        $blockTags = $this->getStringsBetween($block, "{{", "}}");

        if(exists($data))
        {
            foreach($data as $key => $value)
            {
                $this->replaceTag($key, $value, $blockTags, $block);
            }
        }

        $this->elementContent .= $block;
    }

    protected function newElement($tag, $data = null)
    {
        if(is_array($tag))
        {
            $elementBlock = [];
            foreach($tag as $key => $value)
            {
                $elementBlock[$key] = $value;
            }

            $this->elementBlockData[] = $elementBlock;
        }
        else if(exists($tag) && exists($data))
        {
            $this->elementBlockData[][$tag] = $data;
        }
    }

    protected function renderElements()
    {
        $this->getElementTemplate();

        $elementBlockTags = $this->getStringsBetween($this->elementTemplate, '{{', '}}');

        if(exists($this->elementBlockData))
        {
            foreach($this->elementBlockData as $block)
            {
                $elementBlock = $this->elementTemplate;

                foreach($block as $key => $value)
                {
                    $this->replaceTag($key, $value, $elementBlockTags, $elementBlock);
                }

                $this->elementContent .= $elementBlock;
            }
        }

        return $this->elementContent;
    }

    protected function renderElement()
    {
        $this->getElementTemplate();

        $elementTags = $this->getStringsBetween($this->elementTemplate, '{{', '}}');

        if(exists($this->data))
        {
            foreach($this->data as $key => $value)
            {
                $this->replaceTag($key, $value, $elementTags, $this->elementTemplate);
            }
        }

        $this->elementTemplate = str_replace('{{ @elementContent }}', $this->elementContent, $this->elementTemplate);

        return $this->elementTemplate;
    }
}