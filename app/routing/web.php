<?php

use Polyel\Router\Facade\Route;

Route::get("/", "WelcomeController@welcome");

Route::addAuthRoutes();