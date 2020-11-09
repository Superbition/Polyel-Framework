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
|
| API registered routes are treated as stateless, so they do not have
| access to the Session System. By default '/api/*' is added to the CSRF
| middleware protection as well.
|
| All API registered routes are automatically assigned the 'api'
| middleware group which can be found in your applications HTTP Kernel.
│
*/

Route::group(['prefix' => '/api', 'middleware' => 'Auth:api'], function()
{
    // ...
});