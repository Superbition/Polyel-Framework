<?php

use Polyel\Router\Facade\Route;

/*
│------------------------------------------------------------------------------
│ API Routes
│------------------------------------------------------------------------------
│ This is the API routes for your application, here you can register routes
| that are a part of your API, and these routes are then loaded during server
| boot after registering web routes.
| You may use the group method to assign a prefix or a set of middleware
| to multiple API routes at a time. An example has been defined below...
│
*/

Route::group(['prefix' => '/api', 'middleware' => ''], function()
{
    Route::get("/test", function()
    {
        return 'api test route in a group';
    });
});