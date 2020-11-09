<?php

namespace App\View\Elements;

use Polyel\Auth\AuthManager;

class WelcomeHeader extends Element
{
    // Set the element template relative to /resources/elements/
    public $element = 'welcomeHeader';

    private $auth;

    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }

    public function build()
    {
        if($this->auth->check() === false)
        {
            $this->appendHtmlTag('<a href="/login">', 'Login', '</a>');
            $this->appendHtmlTag('<a href="/register">', 'Register', '</a>');
        }
        else
        {
            $this->appendHtmlTag('<p>', $this->auth->user()->get('email'), '</p>');

            $logout = <<<HTML
                <form action="/logout" method="post">
                    
                    <button type="submit">Logout</button>
                    
                    {{ @csrfToken }}
                    
                </form>
            HTML;

            $this->appendHtmlBlock($logout);
        }

        return $this->renderElement();
    }
}