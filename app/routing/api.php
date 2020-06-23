<?php

use Polyel\Router\Facade\Route;

/*
│------------------------------------------------------------------------------
│ API Routes
│------------------------------------------------------------------------------
│
│
*/

Route::group(['prefix' => '/api', 'middleware' => ''], function()
{
    Route::get("/test", function()
    {
        return 'api test route in a group';
    });
});