<?php

namespace Polyel\System;

abstract class ServiceSupplier
{
    public function __construct()
    {

    }

    abstract public function register();
}