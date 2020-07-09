<?php

use Polyel\Router\Facade\Route;

function registerAuthRoutes()
{
    Route::group(['middleware' => 'RedirectIfAuthenticated'], function()
    {
        Route::get('/login', 'Auth\LoginController@displayLoginView');
        Route::post('/login', 'Auth\LoginController@login');

        Route::get('/password/reset', 'Auth\ForgotPasswordController@displayForgotPasswordView');
        Route::post('/password/email/reset', 'Auth\ForgotPasswordController@sendPasswordResetEmail');
        Route::get('/password/reset/{token}', 'Auth\ResetPasswordController@displayPasswordResetView');
        Route::post('/password/reset', 'Auth\ResetPasswordController@resetPassword');

        Route::get('/register', 'Auth\RegisterController@displayRegistrationView');
        Route::post('/register', 'Auth\RegisterController@register');
    });

    Route::post('/logout', 'Auth\LoginController@logout');

    Route::get('/password/confirm', 'Auth\ConfirmPasswordController@displayConfirmView');
    Route::post('/password/confirm', 'Auth\ConfirmPasswordController@confirmPassword');

    Route::get('/email/verify', 'Auth\VerificationController@displayEmailVerificationView');
    Route::get('/email/verify/{id}', 'Auth\VerificationController@verify');
    Route::post('/email/verify/resend', 'Auth\VerificationController@resendVerifyEmail');
}