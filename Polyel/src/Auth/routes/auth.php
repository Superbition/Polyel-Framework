<?php

use Polyel\Router\Facade\Route;

function registerAuthRoutes()
{
    Route::group(['middleware' => 'RedirectIfAuthenticated'], function()
    {
        Route::get('/login', 'Auth\LoginController@displayLoginView');
        Route::post('/login', 'Auth\LoginController@login');

        Route::get('/register', 'Auth\RegisterController@displayRegistrationView');
        Route::post('/register', 'Auth\RegisterController@register');
    });

    Route::post('/logout', 'Auth\LoginController@logout');
    Route::get('/password/confirm', 'Auth\ConfirmPasswordController@displayConfirmView');
    Route::post('/password/confirm', 'Auth\ConfirmPasswordController@confirmPassword');
}