<?php

namespace Polyel\View;

trait DisplaysFlashMessages
{
    protected function processFlashMessages()
    {
        if($flashMessages = $this->getStringsBetween($this->resource, "{{ @flash(", ") }}"))
        {
            foreach($flashMessages as $flashMessageType)
            {
                $this->renderFlashMessage($flashMessageType);
            }
        }
    }

    protected function renderFlashMessage(string $flashMessageType)
    {
        if($flashMessage = $this->HttpKernel->session->get("flashMessages.$flashMessageType"))
        {
            $flashTemplate = new ViewBuilder("$flashMessageType:flash");

            if(is_string($flashMessage) && $flashTemplate->isValid())
            {
                $flashTemplate = $flashTemplate->__toString();

                $flashTemplate = str_replace('@message', $flashMessage, $flashTemplate);

                $this->resource = str_replace("{{ @flash($flashMessageType) }}", $flashTemplate, $this->resource);
            }
        }
        else
        {
            $this->resource = str_replace("{{ @flash($flashMessageType) }}", '', $this->resource);
        }

        $this->HttpKernel->session->remove('flashMessages');
    }
}