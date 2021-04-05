<?php

namespace Polyel\Email;

use Polyel\View\ViewTools;

abstract class Email
{
    use ViewTools;

    public string $fromName;

    public string $subject;

    public string $message;

    public bool $usingHTML = false;

    abstract public function setFromName();

    abstract public function setSubject();

    abstract public function setMessage();

    protected function name(string $fromName)
    {
        $this->fromName = $fromName;

        return $this;
    }

    protected function subject(string $subject)
    {
        $this->subject = $subject;

        return $this;
    }

    protected function text(string $message)
    {
        $this->message = $message;

        return $this;
    }

    protected function html(string $message)
    {
        $this->message = $message;

        $this->usingHTML = true;

        return $this;
    }

    protected function view(string $template)
    {
        // Replace any dot syntax within the template name
        $template = str_replace(".", "/", $template);
        $template = APP_DIR . '/resources/' . $template . '.html';

        // Check if the email template exists first...
        if(file_exists($template))
        {
            $emailTemplateContent = file_get_contents($template);

            $emailTemplateVars = $this->getStringsBetween(
                $emailTemplateContent,
                '{{', '}}'
            );

            // Get publicly accessible class properties which are used for passing in view data
            $emailViewData = get_object_vars($this);

            // Loop through each view key and replace any that are found
            foreach($emailViewData as $viewKey => $viewData)
            {
                /*
                 * The tag is checked if it is there within this function,
                 * before injecting any data, that is why the $emailTemplateVars
                 * are passed in. XSS filtering also takes place inside this
                 * function as well.
                 */
                $this->replaceTag($viewKey, $viewData, $emailTemplateVars, $emailTemplateContent);
            }

            // Set the email body to a HTML message
            $this->html($emailTemplateContent);
        }

        return $this;
    }
}