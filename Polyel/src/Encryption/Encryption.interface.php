<?php

namespace Polyel\Encryption;

interface Encryption
{
    public function encrypt();

    public function decrypt();

    public function encryptString();

    public function decryptString();
}