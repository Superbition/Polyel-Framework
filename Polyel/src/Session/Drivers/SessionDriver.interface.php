<?php

namespace Polyel\Session\Drivers;

interface SessionDriver
{
    public function isValid($sessionID, $sessionData = false);

    public function collisionCheckID($sessionID);

    public function createNewSession($sessionID, $request);

    public function getSessionData($sessionID);

    public function destroySession($sessionID);
}